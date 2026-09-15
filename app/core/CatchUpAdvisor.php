<?php

if (!defined('APP_BOOTSTRAPPED')) {
    http_response_code(403);
    exit;
}

/**
 * Khi một ca hụt chỉ tiêu, so sánh 2 cách bù cho ngày hôm sau:
 *   A. Tăng ca với đội hiện tại  — trả lương nhân OVERTIME_MULTIPLIER
 *   B. Thêm người trong ca bình thường — trả lương nhân EXTRA_WORKER_MULTIPLIER
 *
 * GIẢ ĐỊNH: năng suất tỷ lệ thuận với số người (thêm 1 người thì sản lượng
 * tăng đúng bằng năng suất 1 người). Thực tế có điểm bão hòa — con số ở đây
 * là mức tham khảo để ra quyết định, không phải cam kết sản lượng.
 *
 * Lưu ý về chi phí: cả 2 phương án tốn xấp xỉ cùng số GIỜ CÔNG (đều là khối
 * lượng việc đó), nên phương án nào rẻ hơn phụ thuộc hệ số lương chứ không
 * phụ thuộc mức lương tuyệt đối. Điểm đảo chiều duy nhất là khi phần hụt quá
 * nhỏ: thuê nguyên một người cho cả ca sẽ đắt hơn tăng ca vài chục phút.
 */
class CatchUpAdvisor
{
    /**
     * @param int   $shortfallBoxes Số hộp còn thiếu cần bù
     * @param array $yieldNorm      Bản ghi yield_norms (cần boxes_per_hour, standard_worker_count, hours_per_day)
     * @param ?int  $actualWorkers  Số người thực tế của ca vừa rồi; null thì lấy theo định mức
     * @return array|null null khi định mức chưa đủ dữ liệu để tính
     */
    public static function advise(int $shortfallBoxes, array $yieldNorm, ?int $actualWorkers = null): ?array
    {
        $standardWorkers = (int) ($yieldNorm['standard_worker_count'] ?? 0);
        $boxesPerHour = (float) ($yieldNorm['boxes_per_hour'] ?? 0);
        $hoursPerDay = (float) ($yieldNorm['hours_per_day'] ?? 0);

        if ($shortfallBoxes <= 0 || $standardWorkers <= 0 || $boxesPerHour <= 0 || $hoursPerDay <= 0) {
            return null;
        }

        $perWorkerRate = $boxesPerHour / $standardWorkers;   // hộp / người / giờ
        $crew = $actualWorkers && $actualWorkers > 0 ? $actualWorkers : $standardWorkers;
        $crewIsAssumed = !($actualWorkers && $actualWorkers > 0);

        // --- Phương án A: tăng ca với đội hiện tại ---
        $crewRate = $perWorkerRate * $crew;                  // hộp / giờ của đội hiện tại
        $otHoursNeeded = $shortfallBoxes / $crewRate;
        $otHoursUsed = min($otHoursNeeded, (float) MAX_OVERTIME_HOURS);
        $otCovers = $otHoursNeeded <= MAX_OVERTIME_HOURS + 1e-9;
        $otLaborHours = $otHoursUsed * $crew;
        $otCost = $otLaborHours * HOURLY_WAGE_VND * OVERTIME_MULTIPLIER;

        $overtime = [
            'hours_needed' => $otHoursNeeded,
            'hours_used' => $otHoursUsed,
            'crew' => $crew,
            'labor_hours' => $otLaborHours,
            'cost' => $otCost,
            'covers_all' => $otCovers,
            'boxes_covered' => min($shortfallBoxes, $otHoursUsed * $crewRate),
        ];

        // --- Phương án B: thêm người, giữ nguyên độ dài ca ---
        $extraPeople = (int) ceil(($shortfallBoxes / $hoursPerDay) / $perWorkerRate);
        $extraLaborHours = $extraPeople * $hoursPerDay;
        $extraCost = $extraLaborHours * HOURLY_WAGE_VND * EXTRA_WORKER_MULTIPLIER;

        $extraWorkers = [
            'people' => $extraPeople,
            'hours' => $hoursPerDay,
            'labor_hours' => $extraLaborHours,
            'cost' => $extraCost,
            'covers_all' => true,
            'boxes_covered' => $shortfallBoxes,
        ];

        // --- Phương án C: kết hợp, chỉ dùng khi tăng ca một mình không đủ ---
        $combo = null;
        if (!$otCovers) {
            $remaining = max(0, $shortfallBoxes - MAX_OVERTIME_HOURS * $crewRate);
            $comboPeople = (int) ceil(($remaining / $hoursPerDay) / $perWorkerRate);
            $comboCost = (MAX_OVERTIME_HOURS * $crew * HOURLY_WAGE_VND * OVERTIME_MULTIPLIER)
                + ($comboPeople * $hoursPerDay * HOURLY_WAGE_VND * EXTRA_WORKER_MULTIPLIER);
            $combo = [
                'overtime_hours' => (float) MAX_OVERTIME_HOURS,
                'crew' => $crew,
                'people' => $comboPeople,
                'hours' => $hoursPerDay,
                'cost' => $comboCost,
            ];
        }

        // --- Chọn phương án ---
        if (!$otCovers) {
            $recommended = 'extra_workers';
            $reason = sprintf(
                'Tăng ca một mình không đủ: cần %s nhưng trần tăng ca là %d giờ/ngày.',
                formatHours(round($otHoursNeeded, 1)),
                MAX_OVERTIME_HOURS
            );
            if ($extraCost > $combo['cost']) {
                $recommended = 'combo';
                $reason .= ' Kết hợp tăng ca kịch trần + thêm người rẻ hơn thuê đủ người.';
            }
        } elseif ($extraCost < $otCost) {
            $recommended = 'extra_workers';
            $reason = sprintf(
                'Cùng khối lượng việc nhưng người bổ sung trả lương thường, còn tăng ca nhân %s — rẻ hơn %s.',
                rtrim(rtrim(number_format(OVERTIME_MULTIPLIER, 1, ',', '.'), '0'), ','),
                formatMoney($otCost - $extraCost)
            );
        } else {
            $recommended = 'overtime';
            $reason = sprintf(
                'Phần hụt nhỏ (%s giờ tăng ca là xong) — thuê nguyên %d người cho cả ca %s sẽ lãng phí hơn.',
                formatQty(round($otHoursNeeded, 1)),
                $extraPeople,
                formatHours($hoursPerDay)
            );
        }

        return [
            'shortfall' => $shortfallBoxes,
            'per_worker_rate' => $perWorkerRate,
            'crew' => $crew,
            'crew_is_assumed' => $crewIsAssumed,
            'overtime' => $overtime,
            'extra_workers' => $extraWorkers,
            'combo' => $combo,
            'recommended' => $recommended,
            'reason' => $reason,
        ];
    }

    /** Nhãn tiếng Việt của phương án được chọn. */
    public static function label(string $key): string
    {
        return [
            'overtime' => 'Tăng ca với đội hiện tại',
            'extra_workers' => 'Thêm người cho ngày mai',
            'combo' => 'Tăng ca kịch trần + thêm người',
        ][$key] ?? $key;
    }
}
