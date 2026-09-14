<?php

if (!defined('APP_BOOTSTRAPPED')) {
    http_response_code(403);
    exit;
}

class ScheduleChangeLogModel extends Model
{
    /** @return array<int,array<string,mixed>> */
    public function allForOrder(int $productionOrderId): array
    {
        $stmt = $this->db()->prepare(
            'SELECT scl.*, u.full_name AS changed_by_name
             FROM schedule_change_log scl
             JOIN users u ON u.id = scl.changed_by
             WHERE scl.production_order_id = :id
             ORDER BY scl.changed_at DESC'
        );
        $stmt->execute(['id' => $productionOrderId]);
        return $stmt->fetchAll();
    }
}
