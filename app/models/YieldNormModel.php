<?php

if (!defined('APP_BOOTSTRAPPED')) {
    http_response_code(403);
    exit;
}

class YieldNormModel extends Model
{
    /** @return array<int,array<string,mixed>> */
    public function allForSku(int $skuId): array
    {
        $stmt = $this->db()->prepare(
            'SELECT yn.*, u.full_name AS approved_by_name
             FROM yield_norms yn
             LEFT JOIN users u ON u.id = yn.approved_by
             WHERE yn.sku_id = :sku_id
             ORDER BY yn.version_number DESC'
        );
        $stmt->execute(['sku_id' => $skuId]);
        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db()->prepare(
            'SELECT yn.*, s.code AS sku_code, u.full_name AS approved_by_name
             FROM yield_norms yn
             JOIN skus s ON s.id = yn.sku_id
             LEFT JOIN users u ON u.id = yn.approved_by
             WHERE yn.id = :id'
        );
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function latestForSku(int $skuId): ?array
    {
        $stmt = $this->db()->prepare(
            'SELECT * FROM yield_norms WHERE sku_id = :sku_id ORDER BY version_number DESC LIMIT 1'
        );
        $stmt->execute(['sku_id' => $skuId]);
        return $stmt->fetch() ?: null;
    }

    public function createDraftCopiedFromLatest(int $skuId, int $createdBy): int
    {
        $latest = $this->latestForSku($skuId);
        $nextVersion = $latest ? ((int) $latest['version_number'] + 1) : 1;

        $stmt = $this->db()->prepare(
            'INSERT INTO yield_norms (sku_id, version_number, status, standard_worker_count, hours_per_day,
             boxes_per_hour, setup_time_minutes, qc_buffer_pct, created_by)
             VALUES (:sku_id, :version_number, :status, :workers, :hours, :boxes_per_hour, :setup, :qc_buffer, :created_by)'
        );
        $stmt->execute([
            'sku_id' => $skuId,
            'version_number' => $nextVersion,
            'status' => 'draft',
            'workers' => $latest['standard_worker_count'] ?? null,
            'hours' => $latest['hours_per_day'] ?? null,
            'boxes_per_hour' => $latest['boxes_per_hour'] ?? null,
            'setup' => $latest['setup_time_minutes'] ?? null,
            'qc_buffer' => $latest['qc_buffer_pct'] ?? null,
            'created_by' => $createdBy,
        ]);
        return (int) $this->db()->lastInsertId();
    }

    public function update(
        int $id,
        ?int $workers,
        ?float $hoursPerDay,
        ?float $boxesPerHour,
        ?int $setupMinutes,
        ?float $qcBufferPct
    ): void {
        $stmt = $this->db()->prepare(
            'UPDATE yield_norms SET standard_worker_count = :workers, hours_per_day = :hours,
             boxes_per_hour = :boxes_per_hour, setup_time_minutes = :setup, qc_buffer_pct = :qc_buffer WHERE id = :id'
        );
        $stmt->execute([
            'workers' => $workers, 'hours' => $hoursPerDay, 'boxes_per_hour' => $boxesPerHour,
            'setup' => $setupMinutes, 'qc_buffer' => $qcBufferPct, 'id' => $id,
        ]);
    }

    public function approve(int $id, int $approvedBy): void
    {
        $stmt = $this->db()->prepare(
            "UPDATE yield_norms SET status = 'approved', approved_by = :approved_by, approved_at = NOW() WHERE id = :id"
        );
        $stmt->execute(['approved_by' => $approvedBy, 'id' => $id]);
    }

    /**
     * Định mức GIỜ CÔNG của SKU, suy ra từ định mức năng suất đã duyệt —
     * không lưu thành trường riêng để hai con số không bao giờ mâu thuẫn.
     *
     * boxes_per_hour là năng suất của cả chuyền khi chạy đủ standard_worker_count
     * người, nên năng suất mỗi người = boxes_per_hour / standard_worker_count.
     *
     * @return array{workers:int,boxes_per_hour:float,hours_per_day:float,boxes_per_worker_hour:float,labor_hours_per_box:float,boxes_per_day:float,hours_for_sample:float,labor_hours_for_sample:float,sample_boxes:int}|null
     */
    public function laborStandard(array $yieldNorm, int $sampleBoxes = 1000): ?array
    {
        $workers = (int) ($yieldNorm['standard_worker_count'] ?? 0);
        $boxesPerHour = (float) ($yieldNorm['boxes_per_hour'] ?? 0);
        $hoursPerDay = (float) ($yieldNorm['hours_per_day'] ?? 0);

        if ($workers <= 0 || $boxesPerHour <= 0) {
            return null;
        }

        return [
            'workers' => $workers,
            'boxes_per_hour' => $boxesPerHour,
            'hours_per_day' => $hoursPerDay,
            'boxes_per_worker_hour' => $boxesPerHour / $workers,
            'labor_hours_per_box' => $workers / $boxesPerHour,
            'boxes_per_day' => $boxesPerHour * $hoursPerDay,
            'sample_boxes' => $sampleBoxes,
            'hours_for_sample' => $sampleBoxes / $boxesPerHour,
            'labor_hours_for_sample' => $sampleBoxes * $workers / $boxesPerHour,
        ];
    }

    /** @return string[] Vietnamese labels of any missing required field. */
    public function missingFields(array $yieldNorm): array
    {
        $missing = [];
        if ($yieldNorm['standard_worker_count'] === null) $missing[] = 'số người chuẩn';
        if ($yieldNorm['hours_per_day'] === null) $missing[] = 'giờ/ngày';
        if ($yieldNorm['boxes_per_hour'] === null) $missing[] = 'hộp/giờ đo thực tế';
        if ($yieldNorm['setup_time_minutes'] === null) $missing[] = 'thời gian setup';
        if ($yieldNorm['qc_buffer_pct'] === null) $missing[] = '% dự phòng QC';
        return $missing;
    }
}
