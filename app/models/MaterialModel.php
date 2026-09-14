<?php

if (!defined('APP_BOOTSTRAPPED')) {
    http_response_code(403);
    exit;
}

class MaterialModel extends Model
{
    /** @return array<int,array<string,mixed>> */
    public function allActiveWithGroup(): array
    {
        return $this->db()->query(
            'SELECT m.*, g.name AS group_name FROM materials m
             JOIN material_groups g ON g.id = m.material_group_id
             WHERE m.is_active = 1
             ORDER BY g.sort_order, m.name'
        )->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db()->prepare('SELECT * FROM materials WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function codeExists(string $normalizedCode, ?int $excludeId = null): bool
    {
        $sql = 'SELECT id FROM materials WHERE code = :code';
        $params = ['code' => $normalizedCode];
        if ($excludeId !== null) {
            $sql .= ' AND id != :excludeId';
            $params['excludeId'] = $excludeId;
        }
        $stmt = $this->db()->prepare($sql . ' LIMIT 1');
        $stmt->execute($params);
        return (bool) $stmt->fetch();
    }

    public function create(
        int $groupId,
        string $code,
        string $name,
        string $unitOfMeasure,
        string $unitType,
        ?float $minStockAlert,
        ?string $notes,
        int $createdBy
    ): int {
        $stmt = $this->db()->prepare(
            'INSERT INTO materials (material_group_id, code, name, unit_of_measure, unit_type, min_stock_alert, notes, created_by)
             VALUES (:group_id, :code, :name, :uom, :unit_type, :min_stock_alert, :notes, :created_by)'
        );
        $stmt->execute([
            'group_id' => $groupId,
            'code' => $code,
            'name' => $name,
            'uom' => $unitOfMeasure,
            'unit_type' => $unitType,
            'min_stock_alert' => $minStockAlert,
            'notes' => $notes,
            'created_by' => $createdBy,
        ]);
        return (int) $this->db()->lastInsertId();
    }

    public function update(
        int $id,
        int $groupId,
        string $code,
        string $name,
        string $unitOfMeasure,
        string $unitType,
        ?float $minStockAlert,
        ?string $notes
    ): void {
        $stmt = $this->db()->prepare(
            'UPDATE materials SET material_group_id = :group_id, code = :code, name = :name,
             unit_of_measure = :uom, unit_type = :unit_type, min_stock_alert = :min_stock_alert, notes = :notes WHERE id = :id'
        );
        $stmt->execute([
            'group_id' => $groupId,
            'code' => $code,
            'name' => $name,
            'uom' => $unitOfMeasure,
            'unit_type' => $unitType,
            'min_stock_alert' => $minStockAlert,
            'notes' => $notes,
            'id' => $id,
        ]);
    }
}
