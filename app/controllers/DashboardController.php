<?php

if (!defined('APP_BOOTSTRAPPED')) {
    http_response_code(403);
    exit;
}

class DashboardController extends Controller
{
    /** Màu gán cho từng KHÁCH HÀNG trên lịch (không phải theo lệnh). */
    private const CUSTOMER_COLORS = ['#7A4A2B', '#2563AC', '#1F8A5F', '#8A5A00', '#6B4FA0', '#0E7C86', '#A6365C'];
    private const OTHER_COLOR = '#6B6459';

    /** Badge nào được coi là "gấp" (chấm đỏ) vs "cần để ý" (chấm hổ phách). */
    private const URGENT_BADGES = ['missing_material', 'due_risk', 'missing_qty'];
    private const WARN_BADGES = ['schedule_unconfirmed', 'stale_report'];

    private const DETAIL_TITLES = [
        'lenh-dang-chay' => 'Lệnh đang chạy',
        'lenh-can-chu-y' => 'Lệnh cần chú ý',
        'vat-tu-sap-het' => 'Vật tư sắp hết',
        'hieu-suat' => 'Hiệu suất 30 ngày',
        'loi-qc' => 'Tỷ lệ lỗi QC 30 ngày',
    ];

    public function index(): void
    {
        [$year, $month] = $this->resolveMonth();
        $rangeStart = sprintf('%04d-%02d-01', $year, $month);
        $daysInMonth = (int) date('t', strtotime($rangeStart));
        $rangeEnd = sprintf('%04d-%02d-%02d', $year, $month, $daysInMonth);

        $orderModel = new ProductionOrderModel();
        $shiftModel = new ShiftReportModel();

        $ordersInRange = $orderModel->ordersOverlappingRange($rangeStart, $rangeEnd);
        $customerColors = $this->assignCustomerColors($ordersInRange);
        $orderMeta = $this->buildOrderMeta($ordersInRange, $orderModel, $shiftModel);

        $reportsByDate = [];
        foreach ($shiftModel->reportsBetween($rangeStart, $rangeEnd) as $report) {
            $reportsByDate[$report['report_date']][] = $report;
        }

        $attention = $orderModel->attentionList();
        $attentionByKey = [];
        foreach ($attention as $order) {
            $attentionByKey[$order['badge']['key']][] = $order;
        }

        $this->view('dashboard/index', [
            'year' => $year,
            'month' => $month,
            'calendar' => $this->buildCalendar($year, $month, $ordersInRange, $reportsByDate, $orderMeta),
            'customerColors' => $customerColors,
            'customerLegend' => $this->buildLegend($ordersInRange, $customerColors),
            'orderMeta' => $orderMeta,
            'activeCount' => count($orderModel->activeOrders()),
            'attentionCount' => count($attention),
            'attentionByKey' => $attentionByKey,
            'lowStockCount' => count($this->buildMaterialAlerts()),
            'productivity' => $shiftModel->productivitySummary(30),
            'qcDefect' => $shiftModel->qcDefectRate(30),
        ]);
    }

    public function detail(string $slug): void
    {
        if (!isset(self::DETAIL_TITLES[$slug])) {
            $this->abort404();
        }

        $orderModel = new ProductionOrderModel();
        $shiftModel = new ShiftReportModel();
        $data = ['title' => self::DETAIL_TITLES[$slug], 'backUrl' => $this->backToDashboardUrl()];

        switch ($slug) {
            case 'lenh-dang-chay':
                $rows = [];
                foreach ($orderModel->activeOrders() as $order) {
                    $output = $shiftModel->totalOutputForOrder((int) $order['id']);
                    $rows[] = [
                        'order' => $order,
                        'badge' => $orderModel->computeBadge($order),
                        'output' => $output,
                        'pct_done' => $order['planned_quantity'] ? $output / (int) $order['planned_quantity'] : null,
                        'pct_time' => $this->elapsedFraction($order),
                    ];
                }
                $data['rows'] = $rows;
                $this->view('dashboard/detail_lenh_dang_chay', $data);
                return;

            case 'lenh-can-chu-y':
                $byKey = [];
                foreach ($orderModel->attentionList() as $order) {
                    $byKey[$order['badge']['key']][] = $order;
                }
                $data['attentionByKey'] = $byKey;
                $this->view('dashboard/detail_lenh_can_chu_y', $data);
                return;

            case 'vat-tu-sap-het':
                $data['alerts'] = $this->buildMaterialAlerts();
                $data['damaged'] = (new StockLedgerModel())->damagedThisMonth();
                $this->view('dashboard/detail_vat_tu_sap_het', $data);
                return;

            case 'hieu-suat':
                $data['summary'] = $shiftModel->productivitySummary(30);
                $data['reports'] = $shiftModel->reportsBetween(date('Y-m-d', strtotime('-30 days')), date('Y-m-d'));
                $this->view('dashboard/detail_hieu_suat', $data);
                return;

            case 'loi-qc':
                $data['summary'] = $shiftModel->qcDefectRate(30);
                $reports = [];
                foreach ($shiftModel->reportsBetween(date('Y-m-d', strtotime('-30 days')), date('Y-m-d')) as $report) {
                    if ($report['qc_checked_qty'] !== null) {
                        $reports[] = $report;
                    }
                }
                $data['reports'] = $reports;
                $this->view('dashboard/detail_loi_qc', $data);
                return;
        }
    }

    public function day(string $date): void
    {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || strtotime($date) === false) {
            $this->abort404();
        }

        $orderModel = new ProductionOrderModel();
        $shiftModel = new ShiftReportModel();

        $ordersInRange = $orderModel->ordersOverlappingRange($date, $date);
        $orderMeta = $this->buildOrderMeta($ordersInRange, $orderModel, $shiftModel);
        $reports = $shiftModel->reportsBetween($date, $date);

        $totals = ['output' => 0, 'target' => 0, 'checked' => 0, 'defect' => 0];
        foreach ($reports as $report) {
            $totals['output'] += (int) $report['output_qty'];
            $totals['target'] += (int) $report['target_qty'];
            $totals['checked'] += (int) $report['qc_checked_qty'];
            $totals['defect'] += (int) $report['qc_defect_qty'];
        }

        $this->view('dashboard/day', [
            'date' => $date,
            'orders' => $ordersInRange,
            'orderMeta' => $orderMeta,
            'customerColors' => $this->assignCustomerColors($ordersInRange),
            'reports' => $reports,
            'totals' => $totals,
            'backUrl' => $this->backToDashboardUrl($date),
        ]);
    }

    // ---------------- helpers ----------------

    /** @return array{0:int,1:int} */
    private function resolveMonth(): array
    {
        $year = (int) ($this->input('year') ?: date('Y'));
        $month = (int) ($this->input('month') ?: date('n'));
        if ($month < 1) { $month = 12; $year--; }
        if ($month > 12) { $month = 1; $year++; }
        return [$year, $month];
    }

    /** Quay lại dashboard đúng tháng đang xem. */
    private function backToDashboardUrl(?string $date = null): string
    {
        if ($date !== null) {
            return '/dashboard?year=' . (int) substr($date, 0, 4) . '&month=' . (int) substr($date, 5, 2);
        }
        [$year, $month] = $this->resolveMonth();
        return '/dashboard?year=' . $year . '&month=' . $month;
    }

    /** Tỷ lệ thời gian đã trôi qua giữa ngày bắt đầu và hạn giao hiện hành. */
    private function elapsedFraction(array $order): ?float
    {
        if (!$order['planned_start_date'] || !$order['current_due_date']) {
            return null;
        }
        $start = strtotime($order['planned_start_date']);
        $end = strtotime($order['current_due_date']);
        if ($end <= $start) {
            return null;
        }
        return max(0, min(1, (strtotime('today') - $start) / ($end - $start)));
    }

    /**
     * Badge + tiến độ + mức độ gấp cho từng lệnh, tính một lần rồi dùng lại
     * cho cả lịch và các trang chi tiết.
     */
    private function buildOrderMeta(array $orders, ProductionOrderModel $orderModel, ShiftReportModel $shiftModel): array
    {
        $meta = [];
        foreach ($orders as $order) {
            $badge = $orderModel->computeBadge($order);
            $output = $shiftModel->totalOutputForOrder((int) $order['id']);
            $meta[$order['id']] = [
                'badge' => $badge,
                'output' => $output,
                'pct_done' => $order['planned_quantity'] ? min(1, $output / (int) $order['planned_quantity']) : null,
                'pct_time' => $this->elapsedFraction($order),
                'urgency' => in_array($badge['key'], self::URGENT_BADGES, true) ? 'urgent'
                    : (in_array($badge['key'], self::WARN_BADGES, true) ? 'warn' : ''),
            ];
        }
        return $meta;
    }

    /** Màu ổn định theo khách hàng (sort theo tên) để không nhảy màu khi thêm lệnh. */
    private function assignCustomerColors(array $orders): array
    {
        $names = [];
        foreach ($orders as $order) {
            $names[(int) $order['customer_id']] = $order['customer_name'];
        }
        asort($names, SORT_NATURAL | SORT_FLAG_CASE);

        $colors = [];
        $i = 0;
        foreach ($names as $customerId => $name) {
            $colors[$customerId] = $i < count(self::CUSTOMER_COLORS) ? self::CUSTOMER_COLORS[$i] : self::OTHER_COLOR;
            $i++;
        }
        return $colors;
    }

    /** @return array<int,array{name:string,color:string}> */
    private function buildLegend(array $orders, array $customerColors): array
    {
        $legend = [];
        foreach ($orders as $order) {
            $id = (int) $order['customer_id'];
            $legend[$id] = ['name' => $order['customer_name'], 'color' => $customerColors[$id] ?? self::OTHER_COLOR];
        }
        uasort($legend, fn($a, $b) => strcasecmp($a['name'], $b['name']));
        return $legend;
    }

    /**
     * Vật tư cần chú ý + "cần mua thêm bao nhiêu".
     *
     * outstanding = Σ theo lệnh đang chạy: max(0, cần − đã giữ cho lệnh đó)
     * available   = tồn kho − Σ phiếu giữ chỗ đang active
     * cần mua     = max(0, outstanding − available)
     *
     * Hai vế không trùng nhau: một bên tính theo từng lệnh, một bên là tổng
     * giữ chỗ toàn kho. max(0,…) đặt TRƯỚC khi cộng để một lệnh giữ dư không
     * che mất lệnh khác đang thiếu.
     */
    private function buildMaterialAlerts(): array
    {
        $orderModel = new ProductionOrderModel();
        $demand = [];

        foreach ($orderModel->activeOrders() as $order) {
            foreach ($orderModel->materialRequirements($order) as $req) {
                $id = $req['material_id'];
                if (!isset($demand[$id])) {
                    $demand[$id] = ['outstanding' => 0.0, 'required' => 0.0, 'orders' => []];
                }
                $demand[$id]['required'] += $req['required'];
                $demand[$id]['outstanding'] += $req['shortfall'];
                if ($req['shortfall'] > 0) {
                    $demand[$id]['orders'][] = [
                        'order_code' => $order['order_code'],
                        'order_id' => (int) $order['id'],
                        'customer_name' => $order['customer_name'],
                        'current_due_date' => $order['current_due_date'],
                        'required' => $req['required'],
                        'reserved' => $req['reserved'],
                        'shortfall' => $req['shortfall'],
                    ];
                }
            }
        }

        $alerts = [];
        foreach ((new StockLedgerModel())->stockSummary() as $material) {
            $id = (int) $material['id'];
            $available = (float) $material['stock'] - (float) $material['reserved'];
            $outstanding = $demand[$id]['outstanding'] ?? 0.0;
            $threshold = $material['min_stock_alert'] !== null ? (float) $material['min_stock_alert'] : null;

            $needToBuy = roundQuantity(max(0, $outstanding - $available), $material['unit_type']);
            $toSafeLevel = $threshold !== null
                ? roundQuantity(max(0, $threshold - ($available - $outstanding)), $material['unit_type'])
                : null;

            $reasons = [];
            if ($threshold !== null && $available <= $threshold) {
                $reasons[] = 'Dưới ngưỡng cảnh báo';
            }
            if ($needToBuy > 0) {
                $reasons[] = 'Không đủ cho lệnh đang chạy';
            }
            if ($available < 0) {
                $reasons[] = 'Tồn khả dụng âm (đã giữ quá tồn)';
            }
            if (!$reasons) {
                continue;
            }

            $alerts[] = [
                'material' => $material,
                'available' => $available,
                'required_total' => $demand[$id]['required'] ?? 0.0,
                'outstanding' => $outstanding,
                'need_to_buy' => $needToBuy,
                'to_safe_level' => $toSafeLevel,
                'reasons' => $reasons,
                'orders' => $demand[$id]['orders'] ?? [],
            ];
        }
        return $alerts;
    }

    /**
     * Lịch tháng: mỗi ngày gồm các lệnh đang chạy (gom theo chuyền), báo cáo
     * ca của ngày đó và tổng sản lượng/chỉ tiêu.
     */
    private function buildCalendar(int $year, int $month, array $ordersInRange, array $reportsByDate, array $orderMeta): array
    {
        $firstOfMonth = new DateTime(sprintf('%04d-%02d-01', $year, $month));
        $daysInMonth = (int) $firstOfMonth->format('t');
        $leadingBlanks = ((int) $firstOfMonth->format('N')) - 1; // thứ 2 = 1

        $days = array_fill(0, $leadingBlanks, ['date' => null, 'lines' => [], 'orderCount' => 0, 'output' => 0, 'target' => 0]);

        for ($d = 1; $d <= $daysInMonth; $d++) {
            $date = sprintf('%04d-%02d-%02d', $year, $month, $d);

            $dayOrders = [];
            foreach ($ordersInRange as $order) {
                $start = $order['planned_start_date'];
                $end = $order['current_due_date'] ?? $order['planned_start_date'];
                if ($start !== null && $date >= $start && $date <= $end) {
                    $dayOrders[] = $order;
                }
            }

            // Gấp lên trước để khi cắt bớt pill thì thứ cần thấy vẫn còn.
            usort($dayOrders, function ($a, $b) use ($orderMeta) {
                $rank = ['urgent' => 0, 'warn' => 1, '' => 2];
                return $rank[$orderMeta[$a['id']]['urgency']] <=> $rank[$orderMeta[$b['id']]['urgency']];
            });

            // Gom theo chuyền; lệnh chưa gán chuyền xếp cuối.
            $lines = [];
            foreach ($dayOrders as $order) {
                $lines[$order['chuyen'] ?? ''][] = $order;
            }
            if (isset($lines[''])) {
                $unassigned = $lines[''];
                unset($lines['']);
                ksort($lines, SORT_NATURAL | SORT_FLAG_CASE);
                $lines[''] = $unassigned;
            } else {
                ksort($lines, SORT_NATURAL | SORT_FLAG_CASE);
            }

            $output = 0;
            $target = 0;
            foreach ($reportsByDate[$date] ?? [] as $report) {
                $output += (int) $report['output_qty'];
                $target += (int) $report['target_qty'];
            }

            $days[] = [
                'date' => $date,
                'lines' => $lines,
                'orderCount' => count($dayOrders),
                'output' => $output,
                'target' => $target,
            ];
        }

        while (count($days) % 7 !== 0) {
            $days[] = ['date' => null, 'lines' => [], 'orderCount' => 0, 'output' => 0, 'target' => 0];
        }

        return array_chunk($days, 7);
    }
}
