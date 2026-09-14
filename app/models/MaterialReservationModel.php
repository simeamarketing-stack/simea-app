<?php

if (!defined('APP_BOOTSTRAPPED')) {
    http_response_code(403);
    exit;
}

/**
 * Thrown when a reservation/issue request cannot be satisfied — carries a
 * ready-to-display Vietnamese message.
 */
class InsufficientStockException extends RuntimeException
{
}

class MaterialReservationModel extends Model
{
    /**
     * Reserve $quantity of $materialId for $productionOrderId.
     *
     * Concurrency-safe: locks the materials row with SELECT ... FOR UPDATE so
     * two simultaneous requests against the same material serialize against
     * each other — the second cannot compute "available" until the first's
     * transaction has committed. Never call this outside a fresh PDO
     * connection per request (the framework already gives each HTTP request
     * its own PDO instance via Model::db()).
     */
    public function createReservation(int $productionOrderId, int $materialId, float $quantity, int $createdBy): int
    {
        $db = $this->db();
        $db->beginTransaction();
        try {
            $lock = $db->prepare('SELECT id FROM materials WHERE id = :id FOR UPDATE');
            $lock->execute(['id' => $materialId]);
            if (!$lock->fetch()) {
                throw new RuntimeException('Vật tư không tồn tại.');
            }

            $stockStmt = $db->prepare('SELECT COALESCE(SUM(quantity), 0) FROM stock_ledger WHERE material_id = :id');
            $stockStmt->execute(['id' => $materialId]);
            $stock = (float) $stockStmt->fetchColumn();

            $reservedStmt = $db->prepare(
                "SELECT COALESCE(SUM(quantity_reserved), 0) FROM material_reservations WHERE material_id = :id AND status = 'active'"
            );
            $reservedStmt->execute(['id' => $materialId]);
            $reserved = (float) $reservedStmt->fetchColumn();

            $available = $stock - $reserved;
            if ($quantity > $available) {
                throw new InsufficientStockException(
                    "Không đủ tồn kho khả dụng (còn lại: {$available})"
                );
            }

            $insert = $db->prepare(
                'INSERT INTO material_reservations (production_order_id, material_id, quantity_reserved, created_by)
                 VALUES (:order_id, :material_id, :quantity, :created_by)'
            );
            $insert->execute([
                'order_id' => $productionOrderId,
                'material_id' => $materialId,
                'quantity' => $quantity,
                'created_by' => $createdBy,
            ]);
            $id = (int) $db->lastInsertId();

            $db->commit();
            return $id;
        } catch (Throwable $e) {
            $db->rollBack();
            if ($e instanceof PDOException && str_contains($e->getMessage(), 'lock wait timeout')) {
                throw new InsufficientStockException('Vật tư đang được xử lý bởi người khác, vui lòng thử lại.');
            }
            throw $e;
        }
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db()->prepare(
            'SELECT mr.*, m.code AS material_code, m.name AS material_name, m.unit_of_measure, m.unit_type
             FROM material_reservations mr JOIN materials m ON m.id = mr.material_id WHERE mr.id = :id'
        );
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    /** @return array<int,array<string,mixed>> */
    public function allForOrder(int $productionOrderId): array
    {
        $stmt = $this->db()->prepare(
            "SELECT mr.*, m.code AS material_code, m.name AS material_name, m.unit_of_measure, m.unit_type,
             (SELECT COALESCE(SUM(-sl.quantity), 0) FROM stock_ledger sl WHERE sl.reservation_id = mr.id AND sl.transaction_type = 'xuat') AS issued_qty
             FROM material_reservations mr
             JOIN materials m ON m.id = mr.material_id
             WHERE mr.production_order_id = :order_id
             ORDER BY mr.created_at"
        );
        $stmt->execute(['order_id' => $productionOrderId]);
        return $stmt->fetchAll();
    }

    /** @return array<int,array<string,mixed>> */
    public function activeForMaterial(int $materialId): array
    {
        $stmt = $this->db()->prepare(
            "SELECT mr.*, po.order_code, u.full_name AS created_by_name
             FROM material_reservations mr
             JOIN production_orders po ON po.id = mr.production_order_id
             JOIN users u ON u.id = mr.created_by
             WHERE mr.material_id = :material_id AND mr.status = 'active'
             ORDER BY mr.created_at"
        );
        $stmt->execute(['material_id' => $materialId]);
        return $stmt->fetchAll();
    }

    public function release(int $id, int $releasedBy, string $reason): void
    {
        $stmt = $this->db()->prepare(
            "UPDATE material_reservations SET status = 'released', released_by = :by, released_at = NOW(), released_reason = :reason
             WHERE id = :id AND status = 'active'"
        );
        $stmt->execute(['by' => $releasedBy, 'reason' => $reason, 'id' => $id]);
    }

    /**
     * Issue (xuất) $quantity of material against a reservation. Concurrency-safe
     * the same way as createReservation: locks the reservation row so two
     * simultaneous issue attempts against it serialize.
     */
    public function issue(int $reservationId, float $quantity, int $createdBy): void
    {
        $db = $this->db();
        $db->beginTransaction();
        try {
            $lock = $db->prepare('SELECT * FROM material_reservations WHERE id = :id FOR UPDATE');
            $lock->execute(['id' => $reservationId]);
            $reservation = $lock->fetch();
            if (!$reservation || $reservation['status'] !== 'active') {
                throw new RuntimeException('Phiếu giữ chỗ không còn hiệu lực.');
            }

            $issuedStmt = $db->prepare(
                "SELECT COALESCE(SUM(-quantity), 0) FROM stock_ledger WHERE reservation_id = :id AND transaction_type = 'xuat'"
            );
            $issuedStmt->execute(['id' => $reservationId]);
            $alreadyIssued = (float) $issuedStmt->fetchColumn();

            $remaining = (float) $reservation['quantity_reserved'] - $alreadyIssued;
            if ($quantity > $remaining) {
                throw new InsufficientStockException(
                    "Không thể xuất vượt phần đã giữ chỗ (còn lại: {$remaining})"
                );
            }

            $ledger = new StockLedgerModel();
            $ledger->record(
                (int) $reservation['material_id'],
                'xuat',
                -abs($quantity),
                null,
                (int) $reservation['production_order_id'],
                $reservationId,
                null,
                $createdBy
            );

            if ($quantity >= $remaining) {
                $db->prepare("UPDATE material_reservations SET status = 'consumed' WHERE id = :id")->execute(['id' => $reservationId]);
            }

            $db->commit();
        } catch (Throwable $e) {
            $db->rollBack();
            if ($e instanceof PDOException && str_contains($e->getMessage(), 'lock wait timeout')) {
                throw new InsufficientStockException('Vật tư đang được xử lý bởi người khác, vui lòng thử lại.');
            }
            throw $e;
        }
    }
}
