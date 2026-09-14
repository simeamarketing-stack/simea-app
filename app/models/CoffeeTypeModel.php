<?php

if (!defined('APP_BOOTSTRAPPED')) {
    http_response_code(403);
    exit;
}

class CoffeeTypeModel extends Model
{
    /** @return array<int,array<string,mixed>> */
    public function allActive(): array
    {
        return $this->db()->query('SELECT * FROM coffee_types WHERE is_active = 1 ORDER BY name')->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db()->prepare('SELECT * FROM coffee_types WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function codeExists(string $normalizedCode, ?int $excludeId = null): bool
    {
        $sql = 'SELECT id FROM coffee_types WHERE code = :code';
        $params = ['code' => $normalizedCode];
        if ($excludeId !== null) {
            $sql .= ' AND id != :excludeId';
            $params['excludeId'] = $excludeId;
        }
        $stmt = $this->db()->prepare($sql . ' LIMIT 1');
        $stmt->execute($params);
        return (bool) $stmt->fetch();
    }

    public function create(string $code, string $name, int $createdBy): int
    {
        $stmt = $this->db()->prepare(
            'INSERT INTO coffee_types (code, name, created_by) VALUES (:code, :name, :created_by)'
        );
        $stmt->execute(['code' => $code, 'name' => $name, 'created_by' => $createdBy]);
        return (int) $this->db()->lastInsertId();
    }

    public function update(int $id, string $code, string $name): void
    {
        $stmt = $this->db()->prepare('UPDATE coffee_types SET code = :code, name = :name WHERE id = :id');
        $stmt->execute(['code' => $code, 'name' => $name, 'id' => $id]);
    }
}
