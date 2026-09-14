<?php

if (!defined('APP_BOOTSTRAPPED')) {
    http_response_code(403);
    exit;
}

class BaoCaoCaController extends Controller
{
    public function list(): void
    {
        $reports = (new ShiftReportModel())->allRecent();
        $this->view('bao_cao_ca/list', ['reports' => $reports]);
    }

    public function createForm(): void
    {
        $orders = $this->ordersWithSuggestedTarget();
        $this->view('bao_cao_ca/create', ['orders' => $orders, 'errors' => [], 'old' => []]);
    }

    /** @return array<int,array<string,mixed>> allList() rows plus a suggested_target per order. */
    private function ordersWithSuggestedTarget(): array
    {
        $orders = (new ProductionOrderModel())->allList();
        $yieldModel = new YieldNormModel();
        foreach ($orders as &$order) {
            $order['suggested_target'] = null;
            if ($order['released_yield_norm_id'] ?? null) {
                $yn = $yieldModel->find((int) $order['released_yield_norm_id']);
                if ($yn && $yn['boxes_per_hour'] && $yn['hours_per_day']) {
                    $order['suggested_target'] = (int) round((float) $yn['boxes_per_hour'] * (float) $yn['hours_per_day']);
                }
            }
        }
        unset($order);
        return $orders;
    }

    public function store(): void
    {
        $this->requireCsrf();
        $model = new ShiftReportModel();

        $orderId = (int) $this->input('production_order_id', 0);
        $reportDate = (string) $this->input('report_date', '');
        $line = trim((string) $this->input('line', ''));

        $validator = new Validator();
        if ($orderId <= 0) {
            $validator->addError('production_order_id', 'Vui lòng chọn lệnh sản xuất.');
        }
        $validator->required(['report_date' => $reportDate], 'report_date', 'Ngày báo cáo');
        $validator->required(['line' => $line], 'line', 'Chuyền');

        if ($validator->fails()) {
            $this->view('bao_cao_ca/create', ['orders' => $this->ordersWithSuggestedTarget(), 'errors' => $validator->errors(), 'old' => $this->allInput()]);
            return;
        }

        $existing = $model->findByOrderDateLine($orderId, $reportDate, $line);
        if ($existing) {
            flash('info', 'Báo cáo cho lệnh + ngày + chuyền này đã tồn tại — chuyển sang sửa báo cáo hiện có.');
            $this->redirect("/bao-cao-ca/{$existing['id']}");
            return;
        }

        $outputQty = $this->nullableInt($this->input('output_qty'));
        $workerCount = $this->nullableInt($this->input('worker_count'));
        $incidents = trim((string) $this->input('incidents', ''));
        $incidents = $incidents === '' ? null : $incidents;
        $targetQty = $this->nullableInt($this->input('target_qty'));
        $catchUp = $this->nullableInt($this->input('catch_up_target_tomorrow'));
        $plan = trim((string) $this->input('remediation_plan', ''));
        $plan = $plan === '' ? null : $plan;

        if ($outputQty !== null && $targetQty !== null && $outputQty < $targetQty && ($catchUp === null || $plan === null)) {
            $validator->addError('shortfall', 'Ca không đạt chỉ tiêu — bắt buộc điền chỉ tiêu bù ngày mai và kế hoạch khắc phục.');
            $this->view('bao_cao_ca/create', ['orders' => $this->ordersWithSuggestedTarget(), 'errors' => $validator->errors(), 'old' => $this->allInput()]);
            return;
        }

        try {
            $id = $model->create($orderId, $reportDate, $line, $outputQty, $workerCount, $incidents, $targetQty, $catchUp, $plan, (int) Auth::user()['id']);
        } catch (PDOException $e) {
            if ((int) $e->getCode() === 23000 || $e->errorInfo[1] === 1062) {
                $existing = $model->findByOrderDateLine($orderId, $reportDate, $line);
                flash('info', 'Báo cáo cho lệnh + ngày + chuyền này đã tồn tại — chuyển sang sửa báo cáo hiện có.');
                $this->redirect('/bao-cao-ca/' . ($existing['id'] ?? ''));
                return;
            }
            throw $e;
        }

        flash('success', 'Đã ghi nhận báo cáo ca.');
        $this->redirect("/bao-cao-ca/{$id}");
    }

    public function show(string $id): void
    {
        $model = new ShiftReportModel();
        $report = $model->find((int) $id);
        if (!$report) {
            $this->abort404();
        }
        $editHistory = $model->editHistory((int) $id);
        $this->view('bao_cao_ca/detail', ['report' => $report, 'editHistory' => $editHistory]);
    }

    public function update(string $id): void
    {
        $this->requireCsrf();
        $model = new ShiftReportModel();
        $report = $model->find((int) $id);
        if (!$report) {
            $this->abort404();
        }

        $reason = trim((string) $this->input('reason', ''));
        if ($reason === '') {
            flash('error', 'Vui lòng ghi rõ lý do khi sửa báo cáo.');
            $this->redirect("/bao-cao-ca/{$id}");
        }

        $outputQty = $this->nullableInt($this->input('output_qty'));
        $workerCount = $this->nullableInt($this->input('worker_count'));
        $incidents = trim((string) $this->input('incidents', ''));
        $targetQty = $this->nullableInt($this->input('target_qty'));
        $catchUp = $this->nullableInt($this->input('catch_up_target_tomorrow'));
        $plan = trim((string) $this->input('remediation_plan', ''));

        if ($outputQty !== null && $targetQty !== null && $outputQty < $targetQty && ($catchUp === null || $plan === '')) {
            flash('error', 'Ca không đạt chỉ tiêu — bắt buộc điền chỉ tiêu bù ngày mai và kế hoạch khắc phục.');
            $this->redirect("/bao-cao-ca/{$id}");
        }

        try {
            $model->update((int) $id, [
                'output_qty' => $outputQty, 'worker_count' => $workerCount, 'incidents' => $incidents === '' ? null : $incidents,
                'target_qty' => $targetQty, 'catch_up_target_tomorrow' => $catchUp, 'remediation_plan' => $plan === '' ? null : $plan,
            ], $reason, (int) Auth::user()['id']);
            flash('success', 'Đã lưu thay đổi.');
        } catch (LockedReportException $e) {
            flash('error', $e->getMessage());
        }
        $this->redirect("/bao-cao-ca/{$id}");
    }

    public function confirmQc(string $id): void
    {
        $this->requireCsrf();
        (new ShiftReportModel())->confirmQc((int) $id, (int) Auth::user()['id']);
        flash('success', 'QC đã xác nhận báo cáo ca.');
        $this->redirect("/bao-cao-ca/{$id}");
    }

    public function lock(string $id): void
    {
        $this->requireCsrf();
        try {
            (new ShiftReportModel())->lock((int) $id, (int) Auth::user()['id']);
            flash('success', 'Đã khóa báo cáo ca.');
        } catch (CannotLockException $e) {
            flash('error', $e->getMessage());
        }
        $this->redirect("/bao-cao-ca/{$id}");
    }

    private function nullableInt($value): ?int
    {
        return ($value === null || $value === '') ? null : (int) $value;
    }

    private function allInput(): array
    {
        return $_POST;
    }
}
