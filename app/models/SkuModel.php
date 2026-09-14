<?php

if (!defined('APP_BOOTSTRAPPED')) {
    http_response_code(403);
    exit;
}

class SkuModel extends Model
{
    /** @return array<int,array<string,mixed>> */
    public function allActiveWithRelations(): array
    {
        return $this->db()->query(
            'SELECT s.*, c.name AS customer_name, t.name AS coffee_type_name
             FROM skus s
             JOIN customers c ON c.id = s.customer_id
             JOIN coffee_types t ON t.id = s.coffee_type_id
             WHERE s.is_active = 1
             ORDER BY s.code'
        )->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db()->prepare(
            'SELECT s.*, c.name AS customer_name, t.name AS coffee_type_name
             FROM skus s
             JOIN customers c ON c.id = s.customer_id
             JOIN coffee_types t ON t.id = s.coffee_type_id
             WHERE s.id = :id'
        );
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function codeExists(string $normalizedCode, ?int $excludeId = null): bool
    {
        $sql = 'SELECT id FROM skus WHERE code = :code';
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
        string $code,
        int $customerId,
        int $coffeeTypeId,
        int $unitsPerBox,
        ?string $description,
        int $createdBy
    ): int {
        $stmt = $this->db()->prepare(
            'INSERT INTO skus (code, customer_id, coffee_type_id, units_per_box, description, created_by)
             VALUES (:code, :customer_id, :coffee_type_id, :units_per_box, :description, :created_by)'
        );
        $stmt->execute([
            'code' => $code,
            'customer_id' => $customerId,
            'coffee_type_id' => $coffeeTypeId,
            'units_per_box' => $unitsPerBox,
            'description' => $description,
            'created_by' => $createdBy,
        ]);
        return (int) $this->db()->lastInsertId();
    }

    public function update(
        int $id,
        string $code,
        int $customerId,
        int $coffeeTypeId,
        int $unitsPerBox,
        ?string $description
    ): void {
        $stmt = $this->db()->prepare(
            'UPDATE skus SET code = :code, customer_id = :customer_id, coffee_type_id = :coffee_type_id,
             units_per_box = :units_per_box, description = :description WHERE id = :id'
        );
        $stmt->execute([
            'code' => $code,
            'customer_id' => $customerId,
            'coffee_type_id' => $coffeeTypeId,
            'units_per_box' => $unitsPerBox,
            'description' => $description,
            'id' => $id,
        ]);
    }
}
