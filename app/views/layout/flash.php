<?php if (!defined('APP_BOOTSTRAPPED')) { http_response_code(403); exit; } ?>
<?php foreach ($flashes as $f): ?>
  <div class="alert alert-<?= e($f['type']) ?>"><?= e($f['message']) ?></div>
<?php endforeach; ?>
