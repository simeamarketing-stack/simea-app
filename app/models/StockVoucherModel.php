<?php

if (!defined('APP_BOOTSTRAPPED')) {
    http_response_code(403);
    exit;
}

class StockVoucherModel extends Model
{
    /**
     * Create one voucher plus its material lines in a single transaction.
     * $lines is a list of ['material_id' => int, 'quantity' => float].
     * The sign applied to the ledger depends on $type:
     *   nhap, tra       -> positive (stock in)
     *   hong            -> negative (write-off / loss)
     *   dieu_chinh      -> whatever sign the user typed (can be +/-)
     *
     * @param array<int,array{material_id:int,quantity:float}> $lines
     */
    public function createWithLines(string $type, ?string $voucherNo, ?string $note, array $lines, int $createdBy): int
    {
        $db = $this->db();
        $db->beginTransaction();
        try {
            $stmt = $db->prepare(
                'INSERT INTO stock_vouchers (voucher_type, voucher_no, note, created_by) VALUES (:type, :voucher_no, :note, :created_by)'
            );
            $stmt->execute(['type' => $type, 'voucher_no' => $voucherNo, 'note' => $note, 'created_by' => $createdBy]);
            $voucherId = (int) $db->lastInsertId();

            $ledger = new StockLedgerModel();
            foreach ($lines as $line) {
                $signedQty = $line['quantity'];
                if ($type === 'hong') {
                    $signedQty = -abs($signedQty);
                } elseif (in_array($type, ['nhap', 'tra'], true)) {
                    $signedQty = abs($signedQty);
                }
                // 'dieu_chinh' keeps whatever sign the user entered.
                $ledger->record((int) $line['material_id'], $type, $signedQty, $voucherId, null, null, null, $createdBy);
            }

            $db->commit();
            return $voucherId;
        } catch (Throwable $e) {
            $db->rollBack();
            throw $e;
        }
    }

    /** @return array<int,array<string,mixed>> */
    public function allRecent(int $limit = 50): array
    {
        $stmt = $this->db()->prepare(
            'SELECT v.*, u.full_name AS created_by_name
             FROM stock_vouchers v
             JOIN users u ON u.id = v.created_by
             ORDER BY v.created_at DESC, v.id DESC
             LIMIT :lim'
        );
        $stmt->bindValue('lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db()->prepare(
            'SELECT v.*, u.full_name AS created_by_name FROM stock_vouchers v JOIN users u ON u.id = v.created_by WHERE v.id = :id'
        );
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    /** @return array<int,array<string,mixed>> */
    public function linesForVoucher(int $voucherId): array
    {
        $stmt = $this->db()->prepare(
            'SELECT sl.*, m.code AS material_code, m.name AS material_name, m.unit_of_measure
             FROM stock_ledger sl
             JOIN materials m ON m.id = sl.material_id
             WHERE sl.voucher_id = :id
             ORDER BY sl.id'
        );
        $stmt->execute(['id' => $voucherId]);
        return $stmt->fetchAll();
    }
}
