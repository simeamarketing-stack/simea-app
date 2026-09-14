<?php

if (!defined('APP_BOOTSTRAPPED')) {
    http_response_code(403);
    exit;
}

class SkuController extends Controller
{
    public function list(): void
    {
        $skus = (new SkuModel())->allActiveWithRelations();
        $this->view('sku/list', ['skus' => $skus]);
    }

    public function createForm(): void
    {
        $this->view('sku/form', [
            'sku' => null,
            'customers' => (new CustomerModel())->allActive(),
            'coffeeTypes' => (new CoffeeTypeModel())->allActive(),
            'errors' => [],
            'old' => [],
        ]);
    }

    public function store(): void
    {
        $this->requireCsrf();
        $this->save(null);
    }

    public function editForm(string $id): void
    {
        $sku = (new SkuModel())->find((int) $id);
        if (!$sku) {
            $this->abort404();
        }
        $this->view('sku/form', [
            'sku' => $sku,
            'customers' => (new CustomerModel())->allActive(),
            'coffeeTypes' => (new CoffeeTypeModel())->allActive(),
            'errors' => [],
            'old' => [],
        ]);
    }

    public function update(string $id): void
    {
        $this->requireCsrf();
        $sku = (new SkuModel())->find((int) $id);
        if (!$sku) {
            $this->abort404();
        }
        $this->save($sku);
    }

    private function save(?array $existing): void
    {
        $model = new SkuModel();
        $rawCode = (string) $this->input('code', '');
        $customerId = (int) $this->input('customer_id', 0);
        $coffeeTypeId = (int) $this->input('coffee_type_id', 0);
        $unitsPerBox = (int) $this->input('units_per_box', 0);
        $description = trim((string) $this->input('description', ''));
        $description = $description === '' ? null : $description;
        $code = normalizeCode($rawCode);

        $validator = new Validator();
        $validator->required(['code' => $code], 'code', 'Mã SKU');
        if ($customerId <= 0) {
            $validator->addError('customer_id', 'Vui lòng chọn khách hàng.');
        }
        if ($coffeeTypeId <= 0) {
            $validator->addError('coffee_type_id', 'Vui lòng chọn loại cà phê.');
        }
        if ($unitsPerBox <= 0) {
            $validator->addError('units_per_box', 'Số viên/hộp phải lớn hơn 0.');
        }

        if (!$validator->fails() && $model->codeExists($code, $existing['id'] ?? null)) {
            $validator->addError('code', "Mã SKU đã tồn tại: {$code}");
        }

        if ($validator->fails()) {
            $old = [
                'code' => $rawCode, 'customer_id' => $customerId, 'coffee_type_id' => $coffeeTypeId,
                'units_per_box' => $unitsPerBox, 'description' => $description,
            ];
            $this->view('sku/form', [
                'sku' => $existing,
                'customers' => (new CustomerModel())->allActive(),
                'coffeeTypes' => (new CoffeeTypeModel())->allActive(),
                'errors' => $validator->errors(),
                'old' => $old,
            ]);
            return;
        }

        try {
            if ($existing) {
                $model->update((int) $existing['id'], $code, $customerId, $coffeeTypeId, $unitsPerBox, $description);
                flash('success', 'Đã cập nhật SKU.');
            } else {
                $model->create($code, $customerId, $coffeeTypeId, $unitsPerBox, $description, (int) Auth::user()['id']);
                flash('success', 'Đã tạo SKU mới.');
            }
        } catch (PDOException $e) {
            if ((int) $e->getCode() === 23000 || $e->errorInfo[1] === 1062) {
                flash('error', "Mã SKU đã tồn tại: {$code}");
                $this->redirect($existing ? "/danh-muc/sku/{$existing['id']}/sua" : '/danh-muc/sku/tao');
                return;
            }
            throw $e;
        }

        $this->redirect('/danh-muc/sku');
    }
}
