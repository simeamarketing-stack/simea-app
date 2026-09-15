<?php

if (!defined('APP_BOOTSTRAPPED')) {
    http_response_code(403);
    exit;
}

class DinhMucController extends Controller
{
    public function listForSku(string $skuId): void
    {
        $sku = (new SkuModel())->find((int) $skuId);
        if (!$sku) {
            $this->abort404();
        }
        $versions = (new YieldNormModel())->allForSku((int) $skuId);
        $this->view('dinh_muc/list_versions', ['sku' => $sku, 'versions' => $versions]);
    }

    public function createVersion(string $skuId): void
    {
        $this->requireCsrf();
        $sku = (new SkuModel())->find((int) $skuId);
        if (!$sku) {
            $this->abort404();
        }
        $newId = (new YieldNormModel())->createDraftCopiedFromLatest((int) $skuId, (int) Auth::user()['id']);
        flash('success', 'Đã tạo phiên bản định mức năng suất mới.');
        $this->redirect("/dinh-muc/{$newId}");
    }

    public function show(string $id): void
    {
        $model = new YieldNormModel();
        $version = $model->find((int) $id);
        if (!$version) {
            $this->abort404();
        }
        $missing = $version['status'] === 'draft' ? $model->missingFields($version) : [];
        $this->view('dinh_muc/version_detail', [
            'version' => $version,
            'missing' => $missing,
            'labor' => $model->laborStandard($version),
        ]);
    }

    public function update(string $id): void
    {
        $this->requireCsrf();
        $model = new YieldNormModel();
        $version = $model->find((int) $id);
        if (!$version || $version['status'] !== 'draft') {
            $this->abort404();
        }

        $model->update(
            (int) $id,
            $this->nullableInt($this->input('standard_worker_count')),
            $this->nullableFloat($this->input('hours_per_day')),
            $this->nullableFloat($this->input('boxes_per_hour')),
            $this->nullableInt($this->input('setup_time_minutes')),
            $this->nullableFloat($this->input('qc_buffer_pct'))
        );
        flash('success', 'Đã lưu định mức năng suất.');
        $this->redirect("/dinh-muc/{$id}");
    }

    public function approve(string $id): void
    {
        $this->requireCsrf();
        $model = new YieldNormModel();
        $version = $model->find((int) $id);
        if (!$version) {
            $this->abort404();
        }
        if ($version['status'] === 'approved') {
            $this->redirect("/dinh-muc/{$id}");
        }

        $missing = $model->missingFields($version);
        if ($missing) {
            flash('error', 'Không thể duyệt: còn thiếu ' . implode(', ', $missing) . '.');
            $this->redirect("/dinh-muc/{$id}");
        }

        $model->approve((int) $id, (int) Auth::user()['id']);
        flash('success', 'Đã duyệt định mức năng suất.');
        $this->redirect("/dinh-muc/{$id}");
    }

    private function nullableFloat($value): ?float
    {
        return ($value === null || $value === '') ? null : (float) $value;
    }

    private function nullableInt($value): ?int
    {
        return ($value === null || $value === '') ? null : (int) $value;
    }
}
