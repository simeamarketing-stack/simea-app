<?php

if (!defined('APP_BOOTSTRAPPED')) {
    http_response_code(403);
    exit;
}

class BomController extends Controller
{
    public function listForSku(string $skuId): void
    {
        $sku = (new SkuModel())->find((int) $skuId);
        if (!$sku) {
            $this->abort404();
        }
        $versions = (new BomVersionModel())->allForSku((int) $skuId);
        $this->view('bom/list_versions', ['sku' => $sku, 'versions' => $versions]);
    }

    public function createVersion(string $skuId): void
    {
        $this->requireCsrf();
        $sku = (new SkuModel())->find((int) $skuId);
        if (!$sku) {
            $this->abort404();
        }
        $newId = (new BomVersionModel())->createDraftCopiedFromLatest((int) $skuId, (int) Auth::user()['id']);
        flash('success', 'Đã tạo phiên bản BOM mới (sao chép từ phiên bản gần nhất).');
        $this->redirect("/bom/{$newId}");
    }

    public function show(string $id): void
    {
        $version = (new BomVersionModel())->find((int) $id);
        if (!$version) {
            $this->abort404();
        }
        $lines = (new BomLineModel())->allForVersion((int) $id);
        $caseSpec = (new CaseSpecModel())->findByVersion((int) $id);
        $materials = (new MaterialModel())->allActiveWithGroup();
        $missing = $version['status'] === 'draft' ? (new BomLineModel())->missingFieldsForVersion((int) $id) : [];
        $caseSpecComplete = (new CaseSpecModel())->isComplete((int) $id);

        $this->view('bom/version_detail', [
            'version' => $version,
            'lines' => $lines,
            'caseSpec' => $caseSpec,
            'materials' => $materials,
            'missing' => $missing,
            'caseSpecComplete' => $caseSpecComplete,
        ]);
    }

    public function addLine(string $id): void
    {
        $this->requireCsrf();
        $version = (new BomVersionModel())->find((int) $id);
        if (!$version || $version['status'] !== 'draft') {
            $this->abort404();
        }
        $materialId = (int) $this->input('material_id', 0);
        $basisUnit = (string) $this->input('basis_unit', 'per_box');
        if ($materialId > 0 && in_array($basisUnit, ['per_box', 'per_cup'], true)) {
            (new BomLineModel())->addLine((int) $id, $materialId, $basisUnit);
            flash('success', 'Đã thêm dòng vật tư.');
        }
        $this->redirect("/bom/{$id}");
    }

    public function updateLine(string $lineId): void
    {
        $this->requireCsrf();
        $line = (new BomLineModel())->find((int) $lineId);
        if (!$line) {
            $this->abort404();
        }
        $version = (new BomVersionModel())->find((int) $line['bom_version_id']);
        if (!$version || $version['status'] !== 'draft') {
            $this->abort404();
        }

        $quantity = $this->nullableFloat($this->input('quantity'));
        $bufferPct = $this->nullableFloat($this->input('buffer_pct'));
        $wastePct = $this->nullableFloat($this->input('waste_pct'));

        (new BomLineModel())->updateLine((int) $lineId, $quantity, $bufferPct, $wastePct);
        flash('success', 'Đã lưu dòng vật tư.');
        $this->redirect("/bom/{$line['bom_version_id']}");
    }

    public function deleteLine(string $lineId): void
    {
        $this->requireCsrf();
        $line = (new BomLineModel())->find((int) $lineId);
        if (!$line) {
            $this->abort404();
        }
        $version = (new BomVersionModel())->find((int) $line['bom_version_id']);
        if (!$version || $version['status'] !== 'draft') {
            $this->abort404();
        }
        (new BomLineModel())->deleteLine((int) $lineId);
        flash('success', 'Đã xóa dòng vật tư.');
        $this->redirect("/bom/{$line['bom_version_id']}");
    }

    public function saveCaseSpec(string $id): void
    {
        $this->requireCsrf();
        $version = (new BomVersionModel())->find((int) $id);
        if (!$version || $version['status'] !== 'draft') {
            $this->abort404();
        }

        $caseCode = trim((string) $this->input('case_code', ''));
        $caseCode = $caseCode === '' ? null : $caseCode;
        $lengthCm = $this->nullableFloat($this->input('length_cm'));
        $widthCm = $this->nullableFloat($this->input('width_cm'));
        $heightCm = $this->nullableFloat($this->input('height_cm'));
        $boxesPerCaseRaw = $this->input('boxes_per_case');
        $boxesPerCase = ($boxesPerCaseRaw === null || $boxesPerCaseRaw === '') ? null : (int) $boxesPerCaseRaw;

        (new CaseSpecModel())->upsert((int) $id, $caseCode, $lengthCm, $widthCm, $heightCm, $boxesPerCase);
        flash('success', 'Đã lưu quy cách thùng.');
        $this->redirect("/bom/{$id}");
    }

    public function approve(string $id): void
    {
        $this->requireCsrf();
        $version = (new BomVersionModel())->find((int) $id);
        if (!$version) {
            $this->abort404();
        }
        if ($version['status'] === 'approved') {
            $this->redirect("/bom/{$id}");
        }

        $missing = (new BomLineModel())->missingFieldsForVersion((int) $id);
        $caseSpecComplete = (new CaseSpecModel())->isComplete((int) $id);

        if ($missing || !$caseSpecComplete) {
            flash('error', 'Không thể duyệt: còn dòng vật tư thiếu số liệu hoặc chưa khai đủ quy cách thùng.');
            $this->redirect("/bom/{$id}");
        }

        (new BomVersionModel())->approve((int) $id, (int) Auth::user()['id']);
        flash('success', 'Đã duyệt BOM.');
        $this->redirect("/bom/{$id}");
    }

    private function nullableFloat($value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }
        return (float) $value;
    }
}
