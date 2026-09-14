<?php

if (!defined('APP_BOOTSTRAPPED')) {
    http_response_code(403);
    exit;
}

class LenhSanXuatController extends Controller
{
    public function list(): void
    {
        $model = new ProductionOrderModel();
        $orders = $model->allList();
        foreach ($orders as &$order) {
            $order['badge'] = $model->computeBadge($order);
        }
        unset($order);
        $this->view('lenh_san_xuat/list', ['orders' => $orders]);
    }

    public function createForm(): void
    {
        $this->view('lenh_san_xuat/create', [
            'customers' => (new CustomerModel())->allActive(),
            'errors' => [],
            'old' => [],
        ]);
    }

    public function store(): void
    {
        $this->requireCsrf();
        $model = new ProductionOrderModel();

        $rawCode = (string) $this->input('order_code', '');
        $customerId = (int) $this->input('customer_id', 0);
        $code = normalizeCode($rawCode);

        $validator = new Validator();
        $validator->required(['code' => $code], 'code', 'Mã lệnh');
        if ($customerId <= 0) {
            $validator->addError('customer_id', 'Vui lòng chọn khách hàng.');
        }
        if (!$validator->fails() && $model->codeExists($code)) {
            $validator->addError('code', "Mã lệnh đã tồn tại: {$code}");
        }

        if ($validator->fails()) {
            $this->view('lenh_san_xuat/create', [
                'customers' => (new CustomerModel())->allActive(),
                'errors' => $validator->errors(),
                'old' => ['order_code' => $rawCode, 'customer_id' => $customerId],
            ]);
            return;
        }

        try {
            $id = $model->create($code, $customerId, (int) Auth::user()['id']);
        } catch (PDOException $e) {
            if ((int) $e->getCode() === 23000 || $e->errorInfo[1] === 1062) {
                flash('error', "Mã lệnh đã tồn tại: {$code}");
                $this->redirect('/lenh-san-xuat/tao');
                return;
            }
            throw $e;
        }

        flash('success', 'Đã tạo lệnh sản xuất.');
        $this->redirect("/lenh-san-xuat/{$id}");
    }

    public function show(string $id): void
    {
        $model = new ProductionOrderModel();
        $order = $model->find((int) $id);
        if (!$order) {
            $this->abort404();
        }

        $skuModel = new SkuModel();
        $sku = $order['sku_id'] ? $skuModel->find((int) $order['sku_id']) : null;

        $order['badge'] = $model->computeBadge($order);
        $missing = $order['status'] === 'draft' ? $model->missingForRelease($order) : [];
        $scheduleLog = (new ScheduleChangeLogModel())->allForOrder((int) $id);
        $reservations = (new MaterialReservationModel())->allForOrder((int) $id);
        $bomLines = $order['released_bom_version_id']
            ? (new BomLineModel())->allForVersion((int) $order['released_bom_version_id'])
            : ($order['bom_version_id'] ? (new BomLineModel())->allForVersion((int) $order['bom_version_id']) : []);

        $bomVersions = $order['sku_id'] ? (new BomVersionModel())->allForSku((int) $order['sku_id']) : [];
        $yieldVersions = $order['sku_id'] ? (new YieldNormModel())->allForSku((int) $order['sku_id']) : [];

        $suggestedDueDate = null;
        if ($order['planned_quantity'] && $order['planned_start_date'] && $order['released_yield_norm_id']) {
            $yieldNorm = (new YieldNormModel())->find((int) $order['released_yield_norm_id']);
            if ($yieldNorm && $yieldNorm['boxes_per_hour'] > 0 && $yieldNorm['hours_per_day'] > 0) {
                $perDay = (float) $yieldNorm['boxes_per_hour'] * (float) $yieldNorm['hours_per_day'];
                $days = (int) ceil($order['planned_quantity'] / $perDay);
                $suggestedDueDate = (new DateTime($order['planned_start_date']))->modify("+{$days} days")->format('Y-m-d');
            }
        }

        $this->view('lenh_san_xuat/detail', [
            'order' => $order,
            'sku' => $sku,
            'missing' => $missing,
            'scheduleLog' => $scheduleLog,
            'reservations' => $reservations,
            'bomLines' => $bomLines,
            'bomVersions' => $bomVersions,
            'yieldVersions' => $yieldVersions,
            'suggestedDueDate' => $suggestedDueDate,
            'skus' => (new SkuModel())->allActiveWithRelations(),
            'materials' => (new MaterialModel())->allActiveWithGroup(),
            'users' => (new UserModel())->allActive(),
        ]);
    }

    public function updateInfo(string $id): void
    {
        $this->requireCsrf();
        $model = new ProductionOrderModel();
        $order = $model->find((int) $id);
        if (!$order || $order['status'] !== 'draft') {
            $this->abort404();
        }

        $skuId = $this->nullableInt($this->input('sku_id'));
        $bomVersionId = $this->nullableInt($this->input('bom_version_id'));
        $yieldNormId = $this->nullableInt($this->input('yield_norm_id'));
        $plannedQuantity = $this->nullableInt($this->input('planned_quantity'));
        $plannedStartDate = $this->input('planned_start_date') ?: null;

        $model->updateInfo((int) $id, $skuId, $bomVersionId, $yieldNormId, $plannedQuantity, $plannedStartDate);
        flash('success', 'Đã cập nhật thông tin lệnh.');
        $this->redirect("/lenh-san-xuat/{$id}");
    }

    public function release(string $id): void
    {
        $this->requireCsrf();
        $model = new ProductionOrderModel();
        $order = $model->find((int) $id);
        if (!$order) {
            $this->abort404();
        }
        $missing = $model->missingForRelease($order);
        if ($missing) {
            flash('error', 'Không thể phát hành — còn thiếu: ' . implode(', ', $missing) . '.');
            $this->redirect("/lenh-san-xuat/{$id}");
        }
        $model->releaseOrder((int) $id, (int) Auth::user()['id']);
        flash('success', 'Đã phát hành lệnh sản xuất.');
        $this->redirect("/lenh-san-xuat/{$id}");
    }

    public function confirmSchedule(string $id): void
    {
        $this->requireCsrf();
        $dueDate = (string) $this->input('due_date', '');
        if ($dueDate === '') {
            flash('error', 'Vui lòng chọn hạn giao.');
            $this->redirect("/lenh-san-xuat/{$id}");
        }
        (new ProductionOrderModel())->confirmScheduleFirstTime((int) $id, $dueDate, (int) Auth::user()['id']);
        flash('success', 'Đã xác nhận lịch lần đầu. Hạn giao gốc đã được ghi nhận.');
        $this->redirect("/lenh-san-xuat/{$id}");
    }

    public function reschedule(string $id): void
    {
        $this->requireCsrf();
        $newDate = (string) $this->input('new_date', '');
        $reason = trim((string) $this->input('reason', ''));
        if ($newDate === '' || $reason === '') {
            flash('error', 'Vui lòng nhập ngày mới và lý do đổi lịch.');
            $this->redirect("/lenh-san-xuat/{$id}");
        }
        (new ProductionOrderModel())->reschedule((int) $id, $newDate, $reason, (int) Auth::user()['id']);
        flash('success', 'Đã đổi lịch.');
        $this->redirect("/lenh-san-xuat/{$id}");
    }

    public function assign(string $id): void
    {
        $this->requireCsrf();
        $chuyen = trim((string) $this->input('chuyen', ''));
        $chuyen = $chuyen === '' ? null : $chuyen;
        $userId = $this->nullableInt($this->input('phu_trach_user_id'));
        (new ProductionOrderModel())->assign((int) $id, $chuyen, $userId);
        flash('success', 'Đã cập nhật chuyền/người phụ trách.');
        $this->redirect("/lenh-san-xuat/{$id}");
    }

    public function updateStatus(string $id): void
    {
        $this->requireCsrf();
        $status = (string) $this->input('status', '');
        if (!in_array($status, ['draft', 'released', 'in_progress', 'completed', 'cancelled'], true)) {
            $this->abort404();
        }
        (new ProductionOrderModel())->updateStatus((int) $id, $status);
        flash('success', 'Đã cập nhật trạng thái.');
        $this->redirect("/lenh-san-xuat/{$id}");
    }

    public function reserve(string $id): void
    {
        $this->requireCsrf();
        $materialId = (int) $this->input('material_id', 0);
        $quantity = (float) $this->input('quantity', 0);

        if ($materialId <= 0 || $quantity <= 0) {
            flash('error', 'Vui lòng chọn vật tư và nhập số lượng hợp lệ.');
            $this->redirect("/lenh-san-xuat/{$id}");
        }

        try {
            (new MaterialReservationModel())->createReservation((int) $id, $materialId, $quantity, (int) Auth::user()['id']);
            flash('success', 'Đã giữ chỗ vật tư.');
        } catch (InsufficientStockException $e) {
            flash('error', $e->getMessage());
        }
        $this->redirect("/lenh-san-xuat/{$id}");
    }

    public function releaseReservation(string $reservationId): void
    {
        $this->requireCsrf();
        $reservation = (new MaterialReservationModel())->find((int) $reservationId);
        if (!$reservation) {
            $this->abort404();
        }
        $reason = trim((string) $this->input('reason', 'Hủy giữ chỗ'));
        (new MaterialReservationModel())->release((int) $reservationId, (int) Auth::user()['id'], $reason);
        flash('success', 'Đã hủy giữ chỗ.');
        $this->redirect("/lenh-san-xuat/{$reservation['production_order_id']}");
    }

    public function issueReservation(string $reservationId): void
    {
        $this->requireCsrf();
        $reservation = (new MaterialReservationModel())->find((int) $reservationId);
        if (!$reservation) {
            $this->abort404();
        }
        $quantity = (float) $this->input('quantity', 0);
        if ($quantity <= 0) {
            flash('error', 'Vui lòng nhập số lượng xuất hợp lệ.');
            $this->redirect("/lenh-san-xuat/{$reservation['production_order_id']}");
        }
        try {
            (new MaterialReservationModel())->issue((int) $reservationId, $quantity, (int) Auth::user()['id']);
            flash('success', 'Đã xuất kho.');
        } catch (InsufficientStockException $e) {
            flash('error', $e->getMessage());
        }
        $this->redirect("/lenh-san-xuat/{$reservation['production_order_id']}");
    }

    private function nullableInt($value): ?int
    {
        return ($value === null || $value === '') ? null : (int) $value;
    }
}
