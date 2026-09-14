<?php

if (!defined('APP_BOOTSTRAPPED')) {
    http_response_code(403);
    exit;
}

class UserModel extends Model
{
    public function findActiveByUsername(string $username): ?array
    {
        $stmt = $this->db()->prepare('SELECT * FROM users WHERE username = :username AND is_active = 1 LIMIT 1');
        $stmt->execute(['username' => $username]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db()->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /** @return array<int,array<string,mixed>> */
    public function allActive(): array
    {
        return $this->db()->query('SELECT id, full_name, role FROM users WHERE is_active = 1 ORDER BY full_name')->fetchAll();
    }
}
