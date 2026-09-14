<?php

if (!defined('APP_BOOTSTRAPPED')) {
    http_response_code(403);
    exit;
}

class MaterialGroupModel extends Model
{
    /** @return array<int,array<string,mixed>> */
    public function all(): array
    {
        return $this->db()->query('SELECT * FROM material_groups ORDER BY sort_order')->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db()->prepare('SELECT * FROM material_groups WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }
}
