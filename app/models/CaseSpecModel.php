<?php

if (!defined('APP_BOOTSTRAPPED')) {
    http_response_code(403);
    exit;
}

class CaseSpecModel extends Model
{
    public function findByVersion(int $bomVersionId): ?array
    {
        $stmt = $this->db()->prepare('SELECT * FROM case_specs WHERE bom_version_id = :id');
        $stmt->execute(['id' => $bomVersionId]);
        return $stmt->fetch() ?: null;
    }

    public function isComplete(int $bomVersionId): bool
    {
        $spec = $this->findByVersion($bomVersionId);
        if (!$spec) {
            return false;
        }
        return $spec['case_code'] !== null && $spec['case_code'] !== ''
            && $spec['boxes_per_case'] !== null;
    }

    public function upsert(
        int $bomVersionId,
        ?string $caseCode,
        ?float $lengthCm,
        ?float $widthCm,
        ?float $heightCm,
        ?int $boxesPerCase
    ): void {
        $existing = $this->findByVersion($bomVersionId);
        if ($existing) {
            $stmt = $this->db()->prepare(
                'UPDATE case_specs SET case_code = :case_code, length_cm = :length_cm, width_cm = :width_cm,
                 height_cm = :height_cm, boxes_per_case = :boxes_per_case WHERE bom_version_id = :bom_version_id'
            );
        } else {
            $stmt = $this->db()->prepare(
                'INSERT INTO case_specs (bom_version_id, case_code, length_cm, width_cm, height_cm, boxes_per_case)
                 VALUES (:bom_version_id, :case_code, :length_cm, :width_cm, :height_cm, :boxes_per_case)'
            );
        }
        $stmt->execute([
            'bom_version_id' => $bomVersionId,
            'case_code' => $caseCode,
            'length_cm' => $lengthCm,
            'width_cm' => $widthCm,
            'height_cm' => $heightCm,
            'boxes_per_case' => $boxesPerCase,
        ]);
    }
}
