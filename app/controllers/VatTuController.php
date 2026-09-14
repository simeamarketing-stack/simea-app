<?php

if (!defined('APP_BOOTSTRAPPED')) {
    http_response_code(403);
    exit;
}

class VatTuController extends Controller
{
    public function list(): void
    {
        $materials = (new MaterialModel())->allActiveWithGroup();
        $this->view('vat_tu/list', ['materials' => $materials]);
    }

    public function createForm(): void
    {
        $groups = (new MaterialGroupModel())->all();
        $this->view('vat_tu/form', ['material' => null, 'groups' => $groups, 'errors' => [], 'old' => []]);
    }

    public function store(): void
    {
        $this->requireCsrf();
        $this->save(null);
    }

    public function editForm(string $id): void
    {
        $material = (new MaterialModel())->find((int) $id);
        if (!$material) {
            $this->abort404();
        }
        $groups = (new MaterialGroupModel())->all();
        $this->view('vat_tu/form', ['material' => $material, 'groups' => $groups, 'errors' => [], 'old' => []]);
    }

    public function update(string $id): void
    {
        $this->requireCsrf();
        $material = (new MaterialModel())->find((int) $id);
        if (!$material) {
            $this->abort404();
        }
        $this->save($material);
    }

    private function save(?array $existing): void
    {
        $model = new MaterialModel();
        $rawCode = (string) $this->input('code', '');
        $name = trim((string) $this->input('name', ''));
        $groupId = (int) $this->input('material_group_id', 0);
        $unitOfMeasure = trim((string) $this->input('unit_of_measure', ''));
        $unitType = (string) $this->input('unit_type', '');
        $minStockAlertRaw = $this->input('min_stock_alert', '');
        $minStockAlert = $minStockAlertRaw === '' ? null : (float) $minStockAlertRaw;
        $notes = trim((string) $this->input('notes', ''));
        $notes = $notes === '' ? null : $notes;
        $code = normalizeCode($rawCode);

        $validator = new Validator();
        $validator->required(['code' => $code], 'code', 'Mã vật tư');
        $validator->required(['name' => $name], 'name', 'Tên vật tư');
        $validator->required(['unit_of_measure' => $unitOfMeasure], 'unit_of_measure', 'Đơn vị tính');
        if (!in_array($unitType, ['count', 'continuous'], true)) {
            $validator->addError('unit_type', 'Vui lòng chọn loại đơn vị.');
        }
        if ($groupId <= 0) {
            $validator->addError('material_group_id', 'Vui lòng chọn nhóm vật tư.');
        }

        if (!$validator->fails() && $model->codeExists($code, $existing['id'] ?? null)) {
            $validator->addError('code', "Mã vật tư đã tồn tại: {$code}");
        }

        if ($validator->fails()) {
            $old = [
                'code' => $rawCode, 'name' => $name, 'material_group_id' => $groupId,
                'unit_of_measure' => $unitOfMeasure, 'unit_type' => $unitType,
                'min_stock_alert' => $minStockAlertRaw, 'notes' => $notes,
            ];
            $groups = (new MaterialGroupModel())->all();
            $this->view('vat_tu/form', ['material' => $existing, 'groups' => $groups, 'errors' => $validator->errors(), 'old' => $old]);
            return;
        }

        try {
            if ($existing) {
                $model->update((int) $existing['id'], $groupId, $code, $name, $unitOfMeasure, $unitType, $minStockAlert, $notes);
                flash('success', 'Đã cập nhật vật tư.');
            } else {
                $model->create($groupId, $code, $name, $unitOfMeasure, $unitType, $minStockAlert, $notes, (int) Auth::user()['id']);
                flash('success', 'Đã thêm vật tư mới.');
            }
        } catch (PDOException $e) {
            if ((int) $e->getCode() === 23000 || $e->errorInfo[1] === 1062) {
                flash('error', "Mã vật tư đã tồn tại: {$code}");
                $this->redirect($existing ? "/danh-muc/vat-tu/{$existing['id']}/sua" : '/danh-muc/vat-tu/tao');
                return;
            }
            throw $e;
        }

        $this->redirect('/danh-muc/vat-tu');
    }
}
