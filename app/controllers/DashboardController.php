<?php

if (!defined('APP_BOOTSTRAPPED')) {
    http_response_code(403);
    exit;
}

class DashboardController extends Controller
{
    private const VN_WEEKDAYS = ['T2', 'T3', 'T4', 'T5', 'T6', 'T7', 'CN'];
    private const ORDER_COLORS = ['#7A4A2B', '#2563AC', '#1F8A5F', '#A6720F', '#B7362B', '#6B4FA0', '#0E7C86'];

    public function index(): void
    {
        $year = (int) ($this->input('year') ?: date('Y'));
        $month = (int) ($this->input('month') ?: date('n'));
        if ($month < 1) { $month = 12; $year--; }
        if ($month > 12) { $month = 1; $year++; }

        $rangeStart = sprintf('%04d-%02d-01', $year, $month);
        $daysInMonth = (int) date('t', strtotime($rangeStart));
        $rangeEnd = sprintf('%04d-%02d-%02d', $year, $month, $daysInMonth);

        $orderModel = new ProductionOrderModel();
        $shiftModel = new ShiftReportModel();
        $stockModel = new StockLedgerModel();

        $ordersInRange = $orderModel->ordersOverlappingRange($rangeStart, $rangeEnd);
        $calendar = $this->buildCalendar($year, $month, $ordersInRange);

        $allOrders = $orderModel->allList();
        $activeCount = 0;
        foreach ($allOrders as $o) {
            if (in_array($o['status'], ['released', 'in_progress'], true)) {
                $activeCount++;
            }
        }

        $attention = $orderModel->attentionList();
        $attentionByKey = [];
        foreach ($attention as $o) {
            $attentionByKey[$o['badge']['key']][] = $o;
        }

        $progress = [];
        foreach ($allOrders as $o) {
            if ($o['status'] !== 'in_progress' || !$o['planned_quantity']) {
                continue;
            }
            $output = $shiftModel->totalOutputForOrder((int) $o['id']);
            $pctDone = $output / $o['planned_quantity'];
            $pctTime = null;
            if ($o['planned_start_date'] && $o['current_due_date']) {
                $start = strtotime($o['planned_start_date']);
                $end = strtotime($o['current_due_date']);
                $today = strtotime('today');
                if ($end > $start) {
                    $pctTime = max(0, min(1, ($today - $start) / ($end - $start)));
                }
            }
            $progress[] = [
                'order' => $o,
                'output' => $output,
                'pct_done' => $pctDone,
                'pct_time' => $pctTime,
                'behind' => $pctTime !== null && $pctDone < $pctTime - 0.05,
            ];
        }

        $this->view('dashboard/index', [
            'year' => $year,
            'month' => $month,
            'calendar' => $calendar,
            'orderColors' => $this->assignColors($ordersInRange),
            'activeCount' => $activeCount,
            'attention' => $attention,
            'attentionByKey' => $attentionByKey,
            'lowStock' => $stockModel->lowStockAlerts(),
            'damaged' => $stockModel->damagedThisMonth(),
            'productivity' => $shiftModel->productivitySummary(30),
            'qcDefect' => $shiftModel->qcDefectRate(30),
            'progress' => $progress,
        ]);
    }

    /** @param array<int,array<string,mixed>> $orders */
    private function assignColors(array $orders): array
    {
        $colors = [];
        foreach ($orders as $i => $o) {
            $colors[$o['id']] = self::ORDER_COLORS[$i % count(self::ORDER_COLORS)];
        }
        return $colors;
    }

    /**
     * @param array<int,array<string,mixed>> $ordersInRange
     * @return array<int,array<int,array{date:?string,orders:array}>> Weeks of 7 days (Mon-Sun); null date = padding.
     */
    private function buildCalendar(int $year, int $month, array $ordersInRange): array
    {
        $firstOfMonth = new DateTime(sprintf('%04d-%02d-01', $year, $month));
        $daysInMonth = (int) $firstOfMonth->format('t');
        $leadingBlanks = ((int) $firstOfMonth->format('N')) - 1; // Monday = 1

        $days = [];
        for ($i = 0; $i < $leadingBlanks; $i++) {
            $days[] = ['date' => null, 'orders' => []];
        }
        for ($d = 1; $d <= $daysInMonth; $d++) {
            $date = sprintf('%04d-%02d-%02d', $year, $month, $d);
            $dayOrders = [];
            foreach ($ordersInRange as $o) {
                $start = $o['planned_start_date'];
                $end = $o['current_due_date'] ?? $o['planned_start_date'];
                if ($start !== null && $date >= $start && $date <= $end) {
                    $dayOrders[] = $o;
                }
            }
            $days[] = ['date' => $date, 'orders' => $dayOrders];
        }
        while (count($days) % 7 !== 0) {
            $days[] = ['date' => null, 'orders' => []];
        }

        return array_chunk($days, 7);
    }
}
