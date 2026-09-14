<?php

if (!defined('APP_BOOTSTRAPPED')) {
    http_response_code(403);
    exit;
}

class BomLineModel extends Model
{
    /** @return array<int,array<string,mixed>> */
    public function allForVersion(int $bomVersionId): array
    {
        $stmt = $this->db()->prepare(
            'SELECT bl.*, m.code AS material_code, m.name AS material_name, m.unit_of_measure, m.unit_type
             FROM bom_lines bl
             JOIN materials m ON m.id = bl.material_id
             WHERE bl.bom_version_id = :id
             ORDER BY m.name'
        );
        $stmt->execute(['id' => $bomVersionId]);
        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db()->prepare('SELECT * FROM bom_lines WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function addLine(int $bomVersionId, int $materialId, string $basisUnit): int
    {
        $stmt = $this->db()->prepare(
            'INSERT INTO bom_lines (bom_version_id, material_id, basis_unit) VALUES (:version_id, :material_id, :basis_unit)'
        );
        $stmt->execute(['version_id' => $bomVersionId, 'material_id' => $materialId, 'basis_unit' => $basisUnit]);
        return (int) $this->db()->lastInsertId();
    }

    public function updateLine(int $id, ?float $quantity, ?float $bufferPct, ?float $wastePct): void
    {
        $stmt = $this->db()->prepare(
            'UPDATE bom_lines SET quantity = :quantity, buffer_pct = :buffer_pct, waste_pct = :waste_pct WHERE id = :id'
        );
        $stmt->execute(['quantity' => $quantity, 'buffer_pct' => $bufferPct, 'waste_pct' => $wastePct, 'id' => $id]);
    }

    public function deleteLine(int $id): void
    {
        $stmt = $this->db()->prepare('DELETE FROM bom_lines WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    /**
     * Itemized list of what's missing before this version can be approved:
     * [['material_name' => ..., 'missing' => ['quantity', 'buffer_pct', ...]], ...]
     *
     * @return array<int,array{material_name:string,missing:string[]}>
     */
    public function missingFieldsForVersion(int $bomVersionId): array
    {
        $issues = [];
        foreach ($this->allForVersion($bomVersionId) as $line) {
            $missing = [];
            if ($line['quantity'] === null) {
                $missing[] = 'định lượng';
            }
            if ($line['buffer_pct'] === null) {
                $missing[] = '% dự phòng';
            }
            if ($line['waste_pct'] === null) {
                $missing[] = '% hao hụt';
            }
            if ($missing) {
                $issues[] = ['material_name' => $line['material_name'], 'missing' => $missing];
            }
        }
        return $issues;
    }
}
