<?php if (!defined('APP_BOOTSTRAPPED')) { http_response_code(403); exit; } ?>
<h1>Chào, <?= e($user['full_name']) ?></h1>
<p>Vai trò của bạn: <strong><?= e(ROLE_LABELS[$user['role']] ?? $user['role']) ?></strong></p>
