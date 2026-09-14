<?php

if (!defined('APP_BOOTSTRAPPED')) {
    http_response_code(403);
    exit;
}

class CustomerModel extends Model
{
    /** @return array<int,array<string,mixed>> */
    public function allActive(): array
    {
        return $this->db()->query('SELECT * FROM customers WHERE is_active = 1 ORDER BY name')->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db()->prepare('SELECT * FROM customers WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function codeExists(string $normalizedCode, ?int $excludeId = null): bool
    {
        $sql = 'SELECT id FROM customers WHERE code = :code';
        $params = ['code' => $normalizedCode];
        if ($excludeId !== null) {
            $sql .= ' AND id != :excludeId';
            $params['excludeId'] = $excludeId;
        }
        $stmt = $this->db()->prepare($sql . ' LIMIT 1');
        $stmt->execute($params);
        return (bool) $stmt->fetch();
    }

    public function create(string $code, string $name, ?string $market, int $createdBy): int
    {
        $stmt = $this->db()->prepare(
            'INSERT INTO customers (code, name, market, created_by) VALUES (:code, :name, :market, :created_by)'
        );
        $stmt->execute(['code' => $code, 'name' => $name, 'market' => $market, 'created_by' => $createdBy]);
        return (int) $this->db()->lastInsertId();
    }

    public function update(int $id, string $code, string $name, ?string $market): void
    {
        $stmt = $this->db()->prepare(
            'UPDATE customers SET code = :code, name = :name, market = :market WHERE id = :id'
        );
        $stmt->execute(['code' => $code, 'name' => $name, 'market' => $market, 'id' => $id]);
    }
}
