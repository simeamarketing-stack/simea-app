<?php

if (!defined('APP_BOOTSTRAPPED')) {
    http_response_code(403);
    exit;
}

class StockLedgerModel extends Model
{
    /** current_stock(material) is ALWAYS this SUM — never a stored running total. */
    public function currentStock(int $materialId): float
    {
        $stmt = $this->db()->prepare('SELECT COALESCE(SUM(quantity), 0) FROM stock_ledger WHERE material_id = :id');
        $stmt->execute(['id' => $materialId]);
        return (float) $stmt->fetchColumn();
    }

    public function reservedActive(int $materialId): float
    {
        $stmt = $this->db()->prepare(
            "SELECT COALESCE(SUM(quantity_reserved), 0) FROM material_reservations WHERE material_id = :id AND status = 'active'"
        );
        $stmt->execute(['id' => $materialId]);
        return (float) $stmt->fetchColumn();
    }

    public function available(int $materialId): float
    {
        return $this->currentStock($materialId) - $this->reservedActive($materialId);
    }

    /** @return array<int,array<string,mixed>> One row per active material with stock/reserved/available. */
    public function stockSummary(): array
    {
        $sql = "SELECT m.id, m.code, m.name, m.unit_of_measure, m.unit_type, g.name AS group_name,
                COALESCE((SELECT SUM(sl.quantity) FROM stock_ledger sl WHERE sl.material_id = m.id), 0) AS stock,
                COALESCE((SELECT SUM(mr.quantity_reserved) FROM material_reservations mr WHERE mr.material_id = m.id AND mr.status = 'active'), 0) AS reserved
                FROM materials m
                JOIN material_groups g ON g.id = m.material_group_id
                WHERE m.is_active = 1
                ORDER BY g.sort_order, m.name";
        return $this->db()->query($sql)->fetchAll();
    }

    /** @return array<int,array<string,mixed>> */
    public function historyForMaterial(int $materialId, int $limit = 30): array
    {
        $stmt = $this->db()->prepare(
            "SELECT sl.*, u.full_name AS created_by_name, po.order_code
             FROM stock_ledger sl
             LEFT JOIN users u ON u.id = sl.created_by
             LEFT JOIN production_orders po ON po.id = sl.production_order_id
             WHERE sl.material_id = :id
             ORDER BY sl.created_at DESC, sl.id DESC
             LIMIT :lim"
        );
        $stmt->bindValue('id', $materialId, PDO::PARAM_INT);
        $stmt->bindValue('lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /** Insert one append-only ledger line. Never call UPDATE/DELETE on this table. */
    public function record(
        int $materialId,
        string $transactionType,
        float $signedQuantity,
        ?int $voucherId,
        ?int $productionOrderId,
        ?int $reservationId,
        ?string $note,
        int $createdBy
    ): int {
        $stmt = $this->db()->prepare(
            'INSERT INTO stock_ledger (material_id, transaction_type, quantity, voucher_id, production_order_id, reservation_id, note, created_by)
             VALUES (:material_id, :type, :quantity, :voucher_id, :order_id, :reservation_id, :note, :created_by)'
        );
        $stmt->execute([
            'material_id' => $materialId,
            'type' => $transactionType,
            'quantity' => $signedQuantity,
            'voucher_id' => $voucherId,
            'order_id' => $productionOrderId,
            'reservation_id' => $reservationId,
            'note' => $note,
            'created_by' => $createdBy,
        ]);
        return (int) $this->db()->lastInsertId();
    }
}
