<?php

if (!defined('APP_BOOTSTRAPPED')) {
    http_response_code(403);
    exit;
}

class BomVersionModel extends Model
{
    /** @return array<int,array<string,mixed>> */
    public function allForSku(int $skuId): array
    {
        $stmt = $this->db()->prepare(
            'SELECT bv.*, u.full_name AS approved_by_name
             FROM bom_versions bv
             LEFT JOIN users u ON u.id = bv.approved_by
             WHERE bv.sku_id = :sku_id
             ORDER BY bv.version_number DESC'
        );
        $stmt->execute(['sku_id' => $skuId]);
        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db()->prepare(
            'SELECT bv.*, s.code AS sku_code, u.full_name AS approved_by_name
             FROM bom_versions bv
             JOIN skus s ON s.id = bv.sku_id
             LEFT JOIN users u ON u.id = bv.approved_by
             WHERE bv.id = :id'
        );
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function latestForSku(int $skuId): ?array
    {
        $stmt = $this->db()->prepare(
            'SELECT * FROM bom_versions WHERE sku_id = :sku_id ORDER BY version_number DESC LIMIT 1'
        );
        $stmt->execute(['sku_id' => $skuId]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Create a new draft version for the SKU, copying every line + the case
     * spec from the latest existing version (draft or approved), if any.
     */
    public function createDraftCopiedFromLatest(int $skuId, int $createdBy): int
    {
        $db = $this->db();
        $db->beginTransaction();
        try {
            $latest = $this->latestForSku($skuId);
            $nextVersion = $latest ? ((int) $latest['version_number'] + 1) : 1;

            $stmt = $db->prepare(
                'INSERT INTO bom_versions (sku_id, version_number, status, created_by) VALUES (:sku_id, :version_number, :status, :created_by)'
            );
            $stmt->execute([
                'sku_id' => $skuId,
                'version_number' => $nextVersion,
                'status' => 'draft',
                'created_by' => $createdBy,
            ]);
            $newVersionId = (int) $db->lastInsertId();

            if ($latest) {
                $copyLines = $db->prepare(
                    'INSERT INTO bom_lines (bom_version_id, material_id, basis_unit, quantity, buffer_pct, waste_pct)
                     SELECT :new_id, material_id, basis_unit, quantity, buffer_pct, waste_pct
                     FROM bom_lines WHERE bom_version_id = :old_id'
                );
                $copyLines->execute(['new_id' => $newVersionId, 'old_id' => $latest['id']]);

                $copyCase = $db->prepare(
                    'INSERT INTO case_specs (bom_version_id, case_code, length_cm, width_cm, height_cm, boxes_per_case)
                     SELECT :new_id, case_code, length_cm, width_cm, height_cm, boxes_per_case
                     FROM case_specs WHERE bom_version_id = :old_id'
                );
                $copyCase->execute(['new_id' => $newVersionId, 'old_id' => $latest['id']]);
            }

            $db->commit();
            return $newVersionId;
        } catch (Throwable $e) {
            $db->rollBack();
            throw $e;
        }
    }

    public function approve(int $id, int $approvedBy): void
    {
        $stmt = $this->db()->prepare(
            "UPDATE bom_versions SET status = 'approved', approved_by = :approved_by, approved_at = NOW() WHERE id = :id"
        );
        $stmt->execute(['approved_by' => $approvedBy, 'id' => $id]);
    }
}
