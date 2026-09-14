<?php

if (!defined('APP_BOOTSTRAPPED')) {
    http_response_code(403);
    exit;
}

class DanhMucController extends Controller
{
    // ---------------- Khách hàng ----------------

    public function customersList(): void
    {
        $customers = (new CustomerModel())->allActive();
        $this->view('danh_muc/customers/list', ['customers' => $customers]);
    }

    public function customersCreateForm(): void
    {
        $this->view('danh_muc/customers/form', ['customer' => null, 'errors' => [], 'old' => []]);
    }

    public function customersStore(): void
    {
        $this->requireCsrf();
        $this->saveCustomer(null);
    }

    public function customersEditForm(string $id): void
    {
        $customer = (new CustomerModel())->find((int) $id);
        if (!$customer) {
            $this->abort404();
        }
        $this->view('danh_muc/customers/form', ['customer' => $customer, 'errors' => [], 'old' => []]);
    }

    public function customersUpdate(string $id): void
    {
        $this->requireCsrf();
        $customer = (new CustomerModel())->find((int) $id);
        if (!$customer) {
            $this->abort404();
        }
        $this->saveCustomer($customer);
    }

    private function saveCustomer(?array $existing): void
    {
        $model = new CustomerModel();
        $rawCode = (string) $this->input('code', '');
        $name = trim((string) $this->input('name', ''));
        $market = trim((string) $this->input('market', ''));
        $market = $market === '' ? null : $market;
        $code = normalizeCode($rawCode);

        $validator = new Validator();
        $validator->required(['code' => $code], 'code', 'Mã khách hàng');
        $validator->required(['name' => $name], 'name', 'Tên khách hàng');

        if (!$validator->fails() && $model->codeExists($code, $existing['id'] ?? null)) {
            $validator->addError('code', "Mã khách hàng đã tồn tại: {$code}");
        }

        if ($validator->fails()) {
            $old = ['code' => $rawCode, 'name' => $name, 'market' => $market];
            $this->view('danh_muc/customers/form', ['customer' => $existing, 'errors' => $validator->errors(), 'old' => $old]);
            return;
        }

        try {
            if ($existing) {
                $model->update((int) $existing['id'], $code, $name, $market);
                flash('success', 'Đã cập nhật khách hàng.');
            } else {
                $model->create($code, $name, $market, (int) Auth::user()['id']);
                flash('success', 'Đã tạo khách hàng mới.');
            }
        } catch (PDOException $e) {
            if ((int) $e->getCode() === 23000 || $e->errorInfo[1] === 1062) {
                flash('error', "Mã khách hàng đã tồn tại: {$code}");
                $this->redirect($existing ? "/danh-muc/khach-hang/{$existing['id']}/sua" : '/danh-muc/khach-hang/tao');
                return;
            }
            throw $e;
        }

        $this->redirect('/danh-muc/khach-hang');
    }

    // ---------------- Loại cà phê ----------------

    public function coffeeTypesList(): void
    {
        $coffeeTypes = (new CoffeeTypeModel())->allActive();
        $this->view('danh_muc/coffee_types/list', ['coffeeTypes' => $coffeeTypes]);
    }

    public function coffeeTypesCreateForm(): void
    {
        $this->view('danh_muc/coffee_types/form', ['coffeeType' => null, 'errors' => [], 'old' => []]);
    }

    public function coffeeTypesStore(): void
    {
        $this->requireCsrf();
        $this->saveCoffeeType(null);
    }

    public function coffeeTypesEditForm(string $id): void
    {
        $coffeeType = (new CoffeeTypeModel())->find((int) $id);
        if (!$coffeeType) {
            $this->abort404();
        }
        $this->view('danh_muc/coffee_types/form', ['coffeeType' => $coffeeType, 'errors' => [], 'old' => []]);
    }

    public function coffeeTypesUpdate(string $id): void
    {
        $this->requireCsrf();
        $coffeeType = (new CoffeeTypeModel())->find((int) $id);
        if (!$coffeeType) {
            $this->abort404();
        }
        $this->saveCoffeeType($coffeeType);
    }

    private function saveCoffeeType(?array $existing): void
    {
        $model = new CoffeeTypeModel();
        $rawCode = (string) $this->input('code', '');
        $name = trim((string) $this->input('name', ''));
        $code = normalizeCode($rawCode);

        $validator = new Validator();
        $validator->required(['code' => $code], 'code', 'Mã loại cà phê');
        $validator->required(['name' => $name], 'name', 'Tên loại cà phê');

        if (!$validator->fails() && $model->codeExists($code, $existing['id'] ?? null)) {
            $validator->addError('code', "Mã loại cà phê đã tồn tại: {$code}");
        }

        if ($validator->fails()) {
            $old = ['code' => $rawCode, 'name' => $name];
            $this->view('danh_muc/coffee_types/form', ['coffeeType' => $existing, 'errors' => $validator->errors(), 'old' => $old]);
            return;
        }

        try {
            if ($existing) {
                $model->update((int) $existing['id'], $code, $name);
                flash('success', 'Đã cập nhật loại cà phê.');
            } else {
                $model->create($code, $name, (int) Auth::user()['id']);
                flash('success', 'Đã tạo loại cà phê mới.');
            }
        } catch (PDOException $e) {
            if ((int) $e->getCode() === 23000 || $e->errorInfo[1] === 1062) {
                flash('error', "Mã loại cà phê đã tồn tại: {$code}");
                $this->redirect($existing ? "/danh-muc/loai-ca-phe/{$existing['id']}/sua" : '/danh-muc/loai-ca-phe/tao');
                return;
            }
            throw $e;
        }

        $this->redirect('/danh-muc/loai-ca-phe');
    }
}
