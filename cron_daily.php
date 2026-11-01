<?php
// cron_daily.php - CLI command or Cron task to run daily progression
require_once __DIR__ . '/includes/TradingPool.php';

echo "[" . date('Y-m-d H:i:s') . "] Starting Solaria 40 daily progression...\n";
$result = TradingPool::processAllActivePools();
echo "Processed: " . $result['processed'] . " pools.\n";
if (!empty($result['errors'])) {
    echo "Errors:\n";
    foreach ($result['errors'] as $err) {
        echo " - " . $err . "\n";
    }
}
echo "Daily progression complete.\n";
