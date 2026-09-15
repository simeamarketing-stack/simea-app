<?php

if (!defined('APP_BOOTSTRAPPED')) {
    http_response_code(403);
    exit;
}

class BaoCaoCaController extends Controller
{
    /** Danh sách theo NGÀY, không phải theo từng lệnh. */
    public function list(): void
    {
        // Ô "mở một ngày khác" gửi lên đây rồi chuyển tiếp sang bảng của ngày đó.
        $picked = (string) $this->input('date', '');
        if ($picked !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $picked) && strtotime($picked) !== false) {
            $this->redirect('/bao-cao-ca/ngay/' . $picked);
        }

        $this->view('bao_cao_ca/list', [
            'days' => (new ShiftReportModel())->dayList(),
            'today' => date('Y-m-d'),
        ]);
    }

    /** Bảng nhập của một ngày: mỗi lệnh đang chạy trong ngày là một dòng. */
    public function day(string $date): void
    {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || strtotime($date) === false) {
            $this->abort404();
        }

        $this->view('bao_cao_ca/day', [
            'date' => $date,
            'rows' => $this->buildDayRows($date),
        ]);
    }

    /** Lưu cả bảng trong một lần. */
    public function saveDay(string $date): void
    {
        $this->requireCsrf();
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || strtotime($date) === false) {
            $this->abort404();
        }

        $model = new ShiftReportModel();
        $userId = (int) Auth::user()['id'];
        $reason = trim((string) $this->input('reason', ''));
        $submitted = $this->input('rows', []);
        if (!is_array($submitted)) {
            $submitted = [];
        }

        $created = 0;
        $updated = 0;
        $errors = [];

        foreach ($submitted as $row) {
            $orderId = (int) ($row['order_id'] ?? 0);
            $reportId = (int) ($row['report_id'] ?? 0);
            $line = trim((string) ($row['line'] ?? ''));

            $output = $this->nullableInt($row['output_qty'] ?? null);
            $finished = $this->nullableInt($row['finished_qty'] ?? null);
            $target = $this->nullableInt($row['target_qty'] ?? null);
            $workers = $this->nullableInt($row['worker_count'] ?? null);
            $incidents = trim((string) ($row['incidents'] ?? ''));
            $incidents = $incidents === '' ? null : $incidents;

            $hasData = $output !== null || $finished !== null || $workers !== null || $incidents !== null;

            if ($orderId <= 0 || (!$hasData && $reportId === 0)) {
                continue; // dòng trống chưa ai đụng tới
            }

            if ($finished !== null && $output !== null && $finished > $output) {
                $errors[] = "Lệnh #{$orderId}: thành phẩm ({$finished}) không thể lớn hơn thực tế ({$output}).";
                continue;
            }

            if ($reportId > 0) {
                $existing = $model->find($reportId);
                if (!$existing || (int) $existing['is_locked'] === 1) {
                    continue; // đã khóa thì không đụng vào
                }

                $newValues = [
                    'output_qty' => $output,
                    'finished_qty' => $finished,
                    'target_qty' => $target,
                    'worker_count' => $workers,
                    'incidents' => $incidents,
                ];
                $changed = false;
                foreach ($newValues as $field => $value) {
                    if ((string) $existing[$field] !== (string) $value) {
                        $changed = true;
                        break;
                    }
                }
                if (!$changed) {
                    continue;
                }
                if ($reason === '') {
                    $errors[] = 'Bạn đang sửa số liệu đã ghi trước đó — bắt buộc điền lý do sửa ở cuối bảng.';
                    break;
                }
                try {
                    $model->update($reportId, $newValues, $reason, $userId);
                    $updated++;
                } catch (LockedReportException $e) {
                    $errors[] = $e->getMessage();
                }
                continue;
            }

            if ($line === '') {
                $errors[] = "Lệnh #{$orderId}: chưa có chuyền, vui lòng điền tên chuyền cho dòng này.";
                continue;
            }

            try {
                $model->create($orderId, $date, $line, $output, $finished, $workers, $incidents, $target, null, null, $userId);
                $created++;
            } catch (PDOException $e) {
                if ((int) $e->getCode() === 23000 || $e->errorInfo[1] === 1062) {
                    $errors[] = "Lệnh #{$orderId} chuyền {$line} đã có báo cáo cho ngày này — tải lại trang để sửa dòng có sẵn.";
                    continue;
                }
                throw $e;
            }
        }

        foreach ($errors as $error) {
            flash('error', $error);
        }
        if ($created || $updated) {
            flash('success', "Đã lưu báo cáo ngày {$date}: thêm mới {$created} dòng, cập nhật {$updated} dòng.");
        } elseif (!$errors) {
            flash('info', 'Không có thay đổi nào để lưu.');
        }

        $this->redirect('/bao-cao-ca/ngay/' . $date);
    }

    public function show(string $id): void
    {
        $model = new ShiftReportModel();
        $report = $model->find((int) $id);
        if (!$report) {
            $this->abort404();
        }

        // Ca hụt chỉ tiêu thì kèm luôn đề xuất bù cho ngày mai.
        $advice = null;
        $shortfall = ($report['output_qty'] !== null && $report['target_qty'] !== null)
            ? (int) $report['target_qty'] - (int) $report['output_qty']
            : 0;
        if ($shortfall > 0) {
            $order = (new ProductionOrderModel())->find((int) $report['production_order_id']);
            if ($order && $order['released_yield_norm_id']) {
                $yieldNorm = (new YieldNormModel())->find((int) $order['released_yield_norm_id']);
                if ($yieldNorm) {
                    $advice = CatchUpAdvisor::advise($shortfall, $yieldNorm, $report['worker_count'] !== null ? (int) $report['worker_count'] : null);
                }
            }
        }

        $this->view('bao_cao_ca/detail', [
            'report' => $report,
            'editHistory' => $model->editHistory((int) $id),
            'advice' => $advice,
            'shortfall' => $shortfall,
        ]);
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
        $finishedQty = $this->nullableInt($this->input('finished_qty'));
        $workerCount = $this->nullableInt($this->input('worker_count'));
        $incidents = trim((string) $this->input('incidents', ''));
        $targetQty = $this->nullableInt($this->input('target_qty'));
        $catchUp = $this->nullableInt($this->input('catch_up_target_tomorrow'));
        $plan = trim((string) $this->input('remediation_plan', ''));

        if ($finishedQty !== null && $outputQty !== null && $finishedQty > $outputQty) {
            flash('error', 'Số lượng thành phẩm không thể lớn hơn số lượng thực tế.');
            $this->redirect("/bao-cao-ca/{$id}");
        }
        if ($outputQty !== null && $targetQty !== null && $outputQty < $targetQty && ($catchUp === null || $plan === '')) {
            flash('error', 'Ca không đạt chỉ tiêu — bắt buộc điền chỉ tiêu bù ngày mai và kế hoạch khắc phục.');
            $this->redirect("/bao-cao-ca/{$id}");
        }

        try {
            $model->update((int) $id, [
                'output_qty' => $outputQty, 'finished_qty' => $finishedQty, 'worker_count' => $workerCount,
                'incidents' => $incidents === '' ? null : $incidents, 'target_qty' => $targetQty,
                'catch_up_target_tomorrow' => $catchUp, 'remediation_plan' => $plan === '' ? null : $plan,
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

    // ---------------- helpers ----------------

    /**
     * Dựng các dòng của bảng ngày: mỗi lệnh chạy trong ngày có sẵn bao nhiêu
     * báo cáo thì hiện bấy nhiêu dòng, cộng thêm 1 dòng trống để khai chuyền
     * khác nếu lệnh đó chạy song song nhiều chuyền.
     */
    private function buildDayRows(string $date): array
    {
        $orderModel = new ProductionOrderModel();
        $yieldModel = new YieldNormModel();

        $reportsByOrder = [];
        foreach ((new ShiftReportModel())->forDate($date) as $report) {
            $reportsByOrder[(int) $report['production_order_id']][] = $report;
        }

        // Lệnh theo lịch chạy trong ngày, cộng thêm lệnh đã có báo cáo hôm đó
        // (phòng khi chạy ngoài lịch hoặc lệnh đã chuyển trạng thái).
        $orders = [];
        foreach ($orderModel->ordersOverlappingRange($date, $date) as $order) {
            if (in_array($order['status'], ['released', 'in_progress'], true)) {
                $orders[(int) $order['id']] = $order;
            }
        }
        foreach (array_keys($reportsByOrder) as $orderId) {
            if (!isset($orders[$orderId])) {
                $order = $orderModel->find($orderId);
                if ($order) {
                    $orders[$orderId] = $order;
                }
            }
        }

        $rows = [];
        foreach ($orders as $orderId => $order) {
            // Chỉ tiêu gợi ý cho ca: năng suất chuyền × số giờ trong ngày.
            $suggestedTarget = null;
            if ($order['released_yield_norm_id']) {
                $norm = $yieldModel->find((int) $order['released_yield_norm_id']);
                if ($norm && $norm['boxes_per_hour'] && $norm['hours_per_day']) {
                    $suggestedTarget = (int) round((float) $norm['boxes_per_hour'] * (float) $norm['hours_per_day']);
                }
            }

            foreach ($reportsByOrder[$orderId] ?? [] as $report) {
                $rows[] = ['order' => $order, 'report' => $report, 'suggested_target' => $suggestedTarget, 'is_new' => false];
            }
            $rows[] = ['order' => $order, 'report' => null, 'suggested_target' => $suggestedTarget, 'is_new' => true];
        }

        return $rows;
    }

    private function nullableInt($value): ?int
    {
        return ($value === null || $value === '') ? null : (int) $value;
    }
}
