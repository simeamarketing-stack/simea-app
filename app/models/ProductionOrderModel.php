<?php

if (!defined('APP_BOOTSTRAPPED')) {
    http_response_code(403);
    exit;
}

class ProductionOrderModel extends Model
{
    public function codeExists(string $normalizedCode, ?int $excludeId = null): bool
    {
        $sql = 'SELECT id FROM production_orders WHERE order_code = :code';
        $params = ['code' => $normalizedCode];
        if ($excludeId !== null) {
            $sql .= ' AND id != :excludeId';
            $params['excludeId'] = $excludeId;
        }
        $stmt = $this->db()->prepare($sql . ' LIMIT 1');
        $stmt->execute($params);
        return (bool) $stmt->fetch();
    }

    public function create(string $orderCode, int $customerId, int $createdBy): int
    {
        $stmt = $this->db()->prepare(
            'INSERT INTO production_orders (order_code, customer_id, created_by) VALUES (:code, :customer_id, :created_by)'
        );
        $stmt->execute(['code' => $orderCode, 'customer_id' => $customerId, 'created_by' => $createdBy]);
        return (int) $this->db()->lastInsertId();
    }

    private function baseSelect(): string
    {
        return "SELECT po.*, c.name AS customer_name, s.code AS sku_code, s.units_per_box,
                bv.version_number AS bom_version_number, bv.status AS bom_status,
                yn.version_number AS yield_version_number, yn.status AS yield_status,
                u.full_name AS phu_trach_name
                FROM production_orders po
                JOIN customers c ON c.id = po.customer_id
                LEFT JOIN skus s ON s.id = po.sku_id
                LEFT JOIN bom_versions bv ON bv.id = po.bom_version_id
                LEFT JOIN yield_norms yn ON yn.id = po.yield_norm_id
                LEFT JOIN users u ON u.id = po.phu_trach_user_id";
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db()->prepare($this->baseSelect() . ' WHERE po.id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    /** @return array<int,array<string,mixed>> */
    public function allList(): array
    {
        $stmt = $this->db()->query($this->baseSelect() . " WHERE po.status <> 'cancelled' ORDER BY po.created_at DESC");
        return $stmt->fetchAll();
    }

    public function updateInfo(int $id, ?int $skuId, ?int $bomVersionId, ?int $yieldNormId, ?int $plannedQuantity, ?string $plannedStartDate): void
    {
        $stmt = $this->db()->prepare(
            'UPDATE production_orders SET sku_id = :sku_id, bom_version_id = :bom_version_id, yield_norm_id = :yield_norm_id,
             planned_quantity = :qty, planned_start_date = :start_date
             WHERE id = :id AND status = \'draft\''
        );
        $stmt->execute([
            'sku_id' => $skuId, 'bom_version_id' => $bomVersionId, 'yield_norm_id' => $yieldNormId,
            'qty' => $plannedQuantity, 'start_date' => $plannedStartDate, 'id' => $id,
        ]);
    }

    /** @return string[] What's missing before this order can be released. Empty = ready. */
    public function missingForRelease(array $order): array
    {
        $missing = [];
        if (!$order['sku_id']) {
            $missing[] = 'chưa chọn SKU';
        }
        if (!$order['bom_version_id']) {
            $missing[] = 'chưa chọn BOM';
        } elseif ($order['bom_status'] !== 'approved') {
            $missing[] = 'BOM chưa được duyệt';
        }
        if (!$order['yield_norm_id']) {
            $missing[] = 'chưa chọn định mức năng suất';
        } elseif ($order['yield_status'] !== 'approved') {
            $missing[] = 'định mức năng suất chưa được duyệt';
        }
        if (!$order['planned_quantity']) {
            $missing[] = 'chưa nhập số lượng kế hoạch';
        }
        if (!$order['planned_start_date']) {
            $missing[] = 'chưa chọn ngày bắt đầu dự kiến';
        }
        return $missing;
    }

    /** Freezes bom_version_id/yield_norm_id into the released_* columns permanently. */
    public function releaseOrder(int $id, int $releasedBy): void
    {
        $stmt = $this->db()->prepare(
            "UPDATE production_orders
             SET status = 'released', released_bom_version_id = bom_version_id, released_yield_norm_id = yield_norm_id,
                 released_at = NOW(), released_by = :by
             WHERE id = :id AND status = 'draft'"
        );
        $stmt->execute(['by' => $releasedBy, 'id' => $id]);
    }

    /** Sets original_due_date ONCE — guarded so a second call can never overwrite it. */
    public function confirmScheduleFirstTime(int $id, string $dueDate, int $confirmedBy): void
    {
        $stmt = $this->db()->prepare(
            'UPDATE production_orders
             SET original_due_date = :due1, current_due_date = :due2, schedule_confirmed_at = NOW(), schedule_confirmed_by = :by
             WHERE id = :id AND original_due_date IS NULL'
        );
        $stmt->execute(['due1' => $dueDate, 'due2' => $dueDate, 'by' => $confirmedBy, 'id' => $id]);
    }

    /** Only ever changes current_due_date; original_due_date is untouched. Logged append-only. */
    public function reschedule(int $id, string $newDate, string $reason, int $changedBy): void
    {
        $db = $this->db();
        $db->beginTransaction();
        try {
            $current = $db->prepare('SELECT current_due_date FROM production_orders WHERE id = :id FOR UPDATE');
            $current->execute(['id' => $id]);
            $oldDate = $current->fetchColumn();

            $db->prepare('UPDATE production_orders SET current_due_date = :new_date WHERE id = :id')
                ->execute(['new_date' => $newDate, 'id' => $id]);

            $db->prepare(
                'INSERT INTO schedule_change_log (production_order_id, old_date, new_date, reason, changed_by)
                 VALUES (:order_id, :old_date, :new_date, :reason, :changed_by)'
            )->execute([
                'order_id' => $id, 'old_date' => $oldDate ?: null, 'new_date' => $newDate,
                'reason' => $reason, 'changed_by' => $changedBy,
            ]);

            $db->commit();
        } catch (Throwable $e) {
            $db->rollBack();
            throw $e;
        }
    }

    public function assign(int $id, ?string $chuyen, ?int $phuTrachUserId): void
    {
        $stmt = $this->db()->prepare('UPDATE production_orders SET chuyen = :chuyen, phu_trach_user_id = :user_id WHERE id = :id');
        $stmt->execute(['chuyen' => $chuyen, 'user_id' => $phuTrachUserId, 'id' => $id]);
    }

    public function updateStatus(int $id, string $status): void
    {
        $stmt = $this->db()->prepare('UPDATE production_orders SET status = :status WHERE id = :id');
        $stmt->execute(['status' => $status, 'id' => $id]);
    }

    /**
     * Single highest-priority warning badge for this order, per the fixed
     * priority order. "đã đủ QC" is stubbed/skipped — the QC module is a
     * later phase.
     *
     * @return array{key:string,label:string,css:string}
     */
    public function computeBadge(array $order): array
    {
        if (in_array($order['status'], ['completed', 'cancelled'], true)) {
            return ['key' => 'done', 'label' => 'Hoàn tất', 'css' => 'badge-good'];
        }
        if (!$order['planned_quantity']) {
            return ['key' => 'missing_qty', 'label' => 'Thiếu số lượng', 'css' => 'badge-risk'];
        }
        if ($order['status'] !== 'draft' && !$this->hasEnoughMaterial($order)) {
            return ['key' => 'missing_material', 'label' => 'Chưa đủ vật tư', 'css' => 'badge-risk'];
        }
        if ($order['status'] !== 'draft' && $order['schedule_confirmed_at'] === null) {
            return ['key' => 'schedule_unconfirmed', 'label' => 'Chưa xác nhận lịch', 'css' => 'badge-warn'];
        }
        if ($order['current_due_date'] !== null) {
            $risk = new DateTime($order['current_due_date']);
            $risk->modify('-' . DUE_DATE_RISK_DAYS . ' days');
            if (new DateTime('today') >= $risk) {
                return ['key' => 'due_risk', 'label' => 'Nguy cơ trễ hạn', 'css' => 'badge-risk'];
            }
        }
        if ($order['status'] === 'in_progress' && !$this->hasRecentReport((int) $order['id'])) {
            return ['key' => 'stale_report', 'label' => 'Thiếu cập nhật báo cáo', 'css' => 'badge-warn'];
        }
        return ['key' => 'normal', 'label' => 'Bình thường', 'css' => 'badge-good'];
    }

    /**
     * Nhu cầu vật tư của 1 lệnh, tính từ BOM đã đóng băng lúc phát hành.
     * Nguồn duy nhất cho cả badge "chưa đủ vật tư" lẫn số liệu dashboard —
     * đừng nhân bản công thức này ở chỗ khác.
     *
     * Dòng BOM chưa điền định lượng bị bỏ qua (không suy diễn thành 0).
     * `reserved` tính cả phiếu giữ chỗ đã 'consumed': vật tư đã xuất dùng rồi
     * thì không được báo là còn thiếu.
     *
     * @return array<int,array{material_id:int,material_code:string,material_name:string,unit_of_measure:string,unit_type:string,required:float,reserved:float,shortfall:float}>
     */
    public function materialRequirements(array $order): array
    {
        if (empty($order['released_bom_version_id']) || empty($order['planned_quantity'])) {
            return [];
        }
        $lines = (new BomLineModel())->allForVersion((int) $order['released_bom_version_id']);
        $unitsPerBox = (int) ($order['units_per_box'] ?? 1);
        $plannedQty = (int) $order['planned_quantity'];

        $reservedStmt = $this->db()->prepare(
            "SELECT COALESCE(SUM(quantity_reserved), 0) FROM material_reservations
             WHERE production_order_id = :order_id AND material_id = :material_id AND status IN ('active', 'consumed')"
        );

        $rows = [];
        foreach ($lines as $line) {
            if ($line['quantity'] === null) {
                continue;
            }
            $totalUnits = $line['basis_unit'] === 'per_box' ? $plannedQty : $plannedQty * $unitsPerBox;
            $factor = 1 + ((float) ($line['buffer_pct'] ?? 0)) / 100 + ((float) ($line['waste_pct'] ?? 0)) / 100;
            $required = roundQuantity((float) $line['quantity'] * $factor * $totalUnits, $line['unit_type']);

            $reservedStmt->execute(['order_id' => $order['id'], 'material_id' => $line['material_id']]);
            $reserved = (float) $reservedStmt->fetchColumn();

            $rows[] = [
                'material_id' => (int) $line['material_id'],
                'material_code' => $line['material_code'],
                'material_name' => $line['material_name'],
                'unit_of_measure' => $line['unit_of_measure'],
                'unit_type' => $line['unit_type'],
                'required' => $required,
                'reserved' => $reserved,
                'shortfall' => max(0, $required - $reserved),
            ];
        }
        return $rows;
    }

    private function hasEnoughMaterial(array $order): bool
    {
        foreach ($this->materialRequirements($order) as $row) {
            if ($row['shortfall'] > 0) {
                return false;
            }
        }
        return true;
    }

    /** Lệnh đã phát hành và đang chạy — cơ sở tính nhu cầu vật tư thực tế. */
    public function activeOrders(): array
    {
        return $this->db()->query(
            $this->baseSelect() . " WHERE po.status IN ('released', 'in_progress')
             ORDER BY po.current_due_date IS NULL, po.current_due_date, po.order_code"
        )->fetchAll();
    }

    /**
     * Orders whose [planned_start_date, current_due_date] range overlaps
     * [$rangeStart, $rangeEnd] — used to render the monthly calendar. Orders
     * with no dates at all are excluded (nothing to plot).
     *
     * @return array<int,array<string,mixed>>
     */
    public function ordersOverlappingRange(string $rangeStart, string $rangeEnd): array
    {
        $stmt = $this->db()->prepare(
            $this->baseSelect() . " WHERE po.status <> 'cancelled'
             AND po.planned_start_date IS NOT NULL
             AND COALESCE(po.current_due_date, po.planned_start_date) >= :range_start
             AND po.planned_start_date <= :range_end
             ORDER BY po.planned_start_date"
        );
        $stmt->execute(['range_start' => $rangeStart, 'range_end' => $rangeEnd]);
        return $stmt->fetchAll();
    }

    /** Lệnh cần chú ý (badge khác 'bình thường'/'hoàn tất'), kèm badge đã tính sẵn. */
    public function attentionList(): array
    {
        $orders = $this->allList();
        $attention = [];
        foreach ($orders as $order) {
            $badge = $this->computeBadge($order);
            if (!in_array($badge['key'], ['normal', 'done'], true)) {
                $order['badge'] = $badge;
                $attention[] = $order;
            }
        }
        return $attention;
    }

    private function hasRecentReport(int $orderId): bool
    {
        $stmt = $this->db()->prepare(
            'SELECT MAX(report_date) FROM shift_reports WHERE production_order_id = :id'
        );
        $stmt->execute(['id' => $orderId]);
        $lastDate = $stmt->fetchColumn();
        if (!$lastDate) {
            return false;
        }
        $stale = new DateTime('today');
        $stale->modify('-' . REPORT_STALE_DAYS . ' days');
        return new DateTime($lastDate) >= $stale;
    }
}
