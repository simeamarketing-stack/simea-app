<?php

if (!defined('APP_BOOTSTRAPPED')) {
    http_response_code(403);
    exit;
}

class CannotLockException extends RuntimeException
{
}

class LockedReportException extends RuntimeException
{
}

class ShiftReportModel extends Model
{
    public function findByOrderDateLine(int $orderId, string $reportDate, string $line): ?array
    {
        $stmt = $this->db()->prepare(
            'SELECT * FROM shift_reports WHERE production_order_id = :order_id AND report_date = :date AND line = :line'
        );
        $stmt->execute(['order_id' => $orderId, 'date' => $reportDate, 'line' => $line]);
        return $stmt->fetch() ?: null;
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db()->prepare(
            'SELECT sr.*, po.order_code, lb.full_name AS logged_by_name, qc.full_name AS qc_confirmed_by_name, lk.full_name AS locked_by_name
             FROM shift_reports sr
             JOIN production_orders po ON po.id = sr.production_order_id
             LEFT JOIN users lb ON lb.id = sr.logged_by
             LEFT JOIN users qc ON qc.id = sr.qc_confirmed_by
             LEFT JOIN users lk ON lk.id = sr.locked_by
             WHERE sr.id = :id'
        );
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    /** @return array<int,array<string,mixed>> */
    public function allRecent(int $limit = 100): array
    {
        $stmt = $this->db()->prepare(
            'SELECT sr.*, po.order_code
             FROM shift_reports sr
             JOIN production_orders po ON po.id = sr.production_order_id
             ORDER BY sr.report_date DESC, sr.id DESC
             LIMIT :lim'
        );
        $stmt->bindValue('lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function create(
        int $orderId,
        string $reportDate,
        string $line,
        ?int $outputQty,
        ?int $finishedQty,
        ?int $workerCount,
        ?string $incidents,
        ?int $targetQty,
        ?int $catchUpTarget,
        ?string $remediationPlan,
        int $loggedBy
    ): int {
        $stmt = $this->db()->prepare(
            'INSERT INTO shift_reports (production_order_id, report_date, line, output_qty, finished_qty, worker_count,
             incidents, target_qty, catch_up_target_tomorrow, remediation_plan, logged_by, logged_at)
             VALUES (:order_id, :date, :line, :output, :finished, :workers, :incidents, :target, :catchup, :plan, :logged_by, NOW())'
        );
        $stmt->execute([
            'order_id' => $orderId, 'date' => $reportDate, 'line' => $line, 'output' => $outputQty,
            'finished' => $finishedQty, 'workers' => $workerCount, 'incidents' => $incidents,
            'target' => $targetQty, 'catchup' => $catchUpTarget, 'plan' => $remediationPlan, 'logged_by' => $loggedBy,
        ]);
        return (int) $this->db()->lastInsertId();
    }

    /**
     * Update editable fields, requiring a reason and appending one edit-log
     * row per field that actually changed. Rejects outright if locked.
     *
     * @param array<string,mixed> $newValues Keyed by column name.
     */
    public function update(int $id, array $newValues, string $reason, int $editedBy): void
    {
        $db = $this->db();
        $db->beginTransaction();
        try {
            $current = $db->prepare('SELECT * FROM shift_reports WHERE id = :id FOR UPDATE');
            $current->execute(['id' => $id]);
            $row = $current->fetch();
            if (!$row) {
                throw new RuntimeException('Không tìm thấy báo cáo ca.');
            }
            if ((int) $row['is_locked'] === 1) {
                throw new LockedReportException('Báo cáo đã khóa, không thể sửa.');
            }

            $logStmt = $db->prepare(
                'INSERT INTO shift_report_edit_log (shift_report_id, field_name, old_value, new_value, reason, edited_by)
                 VALUES (:id, :field, :old, :new, :reason, :editor)'
            );

            $setParts = [];
            $params = ['id' => $id];
            foreach ($newValues as $field => $value) {
                $oldValue = $row[$field] ?? null;
                if ((string) $oldValue !== (string) $value) {
                    $logStmt->execute([
                        'id' => $id, 'field' => $field, 'old' => $oldValue, 'new' => $value,
                        'reason' => $reason, 'editor' => $editedBy,
                    ]);
                }
                $setParts[] = "{$field} = :{$field}";
                $params[$field] = $value;
            }

            if ($setParts) {
                $sql = 'UPDATE shift_reports SET ' . implode(', ', $setParts) . ' WHERE id = :id';
                $db->prepare($sql)->execute($params);
            }

            $db->commit();
        } catch (Throwable $e) {
            $db->rollBack();
            throw $e;
        }
    }

    public function confirmQc(int $id, int $confirmedBy): void
    {
        $stmt = $this->db()->prepare(
            "UPDATE shift_reports SET qc_confirmed_by = :by, qc_confirmed_at = NOW() WHERE id = :id AND is_locked = 0"
        );
        $stmt->execute(['by' => $confirmedBy, 'id' => $id]);
    }

    /**
     * Tỷ lệ lỗi trong N ngày gần nhất = (thực tế − thành phẩm) / thực tế.
     * Chỉ tính ca đã khai cả hai số — ca chưa khai thành phẩm không bị suy
     * diễn thành "không lỗi".
     *
     * @return array{checked:int,defect:int,rate:?float}
     */
    public function qcDefectRate(int $days = 30): array
    {
        $stmt = $this->db()->prepare(
            'SELECT COALESCE(SUM(output_qty), 0) AS checked, COALESCE(SUM(output_qty - finished_qty), 0) AS defect
             FROM shift_reports
             WHERE output_qty IS NOT NULL AND finished_qty IS NOT NULL
               AND report_date >= DATE_SUB(CURDATE(), INTERVAL :days DAY)'
        );
        $stmt->bindValue('days', $days, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch();
        $checked = (int) $row['checked'];
        $defect = (int) $row['defect'];
        return ['checked' => $checked, 'defect' => $defect, 'rate' => $checked > 0 ? $defect / $checked : null];
    }

    /**
     * Danh sách NGÀY có hoạt động (có báo cáo ca), kèm số liệu tổng — dùng
     * cho màn hình danh sách báo cáo ca theo ngày.
     *
     * @return array<int,array<string,mixed>>
     */
    public function dayList(int $limit = 60): array
    {
        $stmt = $this->db()->prepare(
            'SELECT report_date,
                    COUNT(*) AS row_count,
                    COUNT(DISTINCT production_order_id) AS order_count,
                    COALESCE(SUM(output_qty), 0) AS output_total,
                    COALESCE(SUM(finished_qty), 0) AS finished_total,
                    COALESCE(SUM(target_qty), 0) AS target_total,
                    COUNT(finished_qty) AS finished_rows,
                    COALESCE(SUM(CASE WHEN finished_qty IS NOT NULL AND output_qty IS NOT NULL THEN output_qty END), 0) AS defect_base,
                    COALESCE(SUM(CASE WHEN finished_qty IS NOT NULL AND output_qty IS NOT NULL THEN output_qty - finished_qty END), 0) AS defect_total,
                    SUM(is_locked) AS locked_count,
                    SUM(CASE WHEN qc_confirmed_by IS NOT NULL THEN 1 ELSE 0 END) AS qc_count
             FROM shift_reports
             GROUP BY report_date
             ORDER BY report_date DESC
             LIMIT :lim'
        );
        $stmt->bindValue('lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /** Báo cáo ca của đúng một ngày, gồm cả thông tin lệnh để dựng bảng. */
    public function forDate(string $date): array
    {
        return $this->reportsBetween($date, $date);
    }

    /**
     * Tổng sản lượng thực tế vs chỉ tiêu trong N ngày gần nhất, chỉ tính
     * các ca đã có cả 2 số liệu.
     *
     * @return array{output:int,target:int,pct:?float}
     */
    public function productivitySummary(int $days = 30): array
    {
        $stmt = $this->db()->prepare(
            'SELECT COALESCE(SUM(output_qty), 0) AS output, COALESCE(SUM(target_qty), 0) AS target
             FROM shift_reports
             WHERE output_qty IS NOT NULL AND target_qty IS NOT NULL
             AND report_date >= DATE_SUB(CURDATE(), INTERVAL :days DAY)'
        );
        $stmt->bindValue('days', $days, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch();
        $output = (int) $row['output'];
        $target = (int) $row['target'];
        return ['output' => $output, 'target' => $target, 'pct' => $target > 0 ? $output / $target : null];
    }

    /**
     * Toàn bộ báo cáo ca trong khoảng ngày — một truy vấn phục vụ cả lịch
     * tháng lẫn trang chi tiết ngày, thay vì query lặp theo từng ngày.
     *
     * @return array<int,array<string,mixed>>
     */
    public function reportsBetween(string $startDate, string $endDate): array
    {
        $stmt = $this->db()->prepare(
            'SELECT sr.*, po.order_code, po.customer_id, c.name AS customer_name
             FROM shift_reports sr
             JOIN production_orders po ON po.id = sr.production_order_id
             JOIN customers c ON c.id = po.customer_id
             WHERE sr.report_date BETWEEN :start AND :end
             ORDER BY sr.report_date, sr.line, sr.id'
        );
        $stmt->execute(['start' => $startDate, 'end' => $endDate]);
        return $stmt->fetchAll();
    }

    /**
     * Các ca hụt chỉ tiêu trong N ngày gần nhất, kèm sẵn định mức năng suất
     * đã đóng băng của lệnh để tính đề xuất bù mà không phải query lặp.
     *
     * @return array<int,array<string,mixed>>
     */
    public function shortfallReports(int $days): array
    {
        $stmt = $this->db()->prepare(
            'SELECT sr.*, po.order_code, po.planned_quantity, c.name AS customer_name, s.code AS sku_code,
                    yn.standard_worker_count, yn.hours_per_day, yn.boxes_per_hour
             FROM shift_reports sr
             JOIN production_orders po ON po.id = sr.production_order_id
             JOIN customers c ON c.id = po.customer_id
             LEFT JOIN skus s ON s.id = po.sku_id
             LEFT JOIN yield_norms yn ON yn.id = po.released_yield_norm_id
             WHERE sr.output_qty IS NOT NULL AND sr.target_qty IS NOT NULL
               AND sr.output_qty < sr.target_qty
               AND sr.report_date >= DATE_SUB(CURDATE(), INTERVAL :days DAY)
             ORDER BY sr.report_date DESC, sr.id DESC'
        );
        $stmt->bindValue('days', $days, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /** Tổng sản lượng đã ghi nhận cho 1 lệnh sản xuất (để tính % hoàn thành so với planned_quantity). */
    public function totalOutputForOrder(int $orderId): int
    {
        $stmt = $this->db()->prepare(
            'SELECT COALESCE(SUM(output_qty), 0) FROM shift_reports WHERE production_order_id = :id AND output_qty IS NOT NULL'
        );
        $stmt->execute(['id' => $orderId]);
        return (int) $stmt->fetchColumn();
    }

    /** Enforces all three lock conditions server-side regardless of who calls it. */
    public function lock(int $id, int $lockedBy): void
    {
        $report = $this->find($id);
        if (!$report) {
            throw new RuntimeException('Không tìm thấy báo cáo ca.');
        }
        if ((int) $report['is_locked'] === 1) {
            return;
        }
        if (!$report['logged_by']) {
            throw new CannotLockException('Chưa có người ghi nhận báo cáo.');
        }
        if (!$report['qc_confirmed_by']) {
            throw new CannotLockException('QC chưa xác nhận báo cáo này.');
        }
        $shortfall = $report['output_qty'] !== null && $report['target_qty'] !== null && (int) $report['output_qty'] < (int) $report['target_qty'];
        if ($shortfall && ($report['catch_up_target_tomorrow'] === null || $report['remediation_plan'] === null || $report['remediation_plan'] === '')) {
            throw new CannotLockException('Ca không đạt chỉ tiêu — cần điền chỉ tiêu bù và kế hoạch khắc phục trước khi khóa.');
        }

        $stmt = $this->db()->prepare(
            'UPDATE shift_reports SET is_locked = 1, locked_by = :by, locked_at = NOW() WHERE id = :id'
        );
        $stmt->execute(['by' => $lockedBy, 'id' => $id]);
    }

    /** @return array<int,array<string,mixed>> */
    public function editHistory(int $id): array
    {
        $stmt = $this->db()->prepare(
            'SELECT l.*, u.full_name AS edited_by_name
             FROM shift_report_edit_log l
             JOIN users u ON u.id = l.edited_by
             WHERE l.shift_report_id = :id
             ORDER BY l.edited_at DESC'
        );
        $stmt->execute(['id' => $id]);
        return $stmt->fetchAll();
    }
}
