<?php
// seed.php - Seeds sample admin and users
// Works in both CLI and Browser / Web Server mode with friendly diagnostics.

// Enable error reporting for diagnostics during setup
error_reporting(E_ALL);
ini_set('display_errors', '1');

$isCli = (php_sapi_name() === 'cli');

function outputMsg(string $msg, string $type = 'info'): void {
    global $isCli;
    if ($isCli) {
        $prefix = $type === 'error' ? '[ERROR] ' : ($type === 'success' ? '[SUCCESS] ' : '[INFO] ');
        echo $prefix . $msg . "\n";
    } else {
        $colors = [
            'info' => 'color: #1e40af; background: #dbeafe;',
            'success' => 'color: #065f46; background: #d1fae5;',
            'error' => 'color: #991b1b; background: #fee2e2;',
        ];
        $style = $colors[$type] ?? $colors['info'];
        echo "<div style='font-family: monospace; padding: 10px 14px; margin: 6px 0; border-radius: 6px; $style'>" . htmlspecialchars($msg) . "</div>";
    }
}

if (!$isCli) {
    echo "<!DOCTYPE html><html><head><title>Solaris Database Setup & Seed</title></head><body style='font-family: sans-serif; max-width: 800px; margin: 40px auto; padding: 20px; line-height: 1.5;'>";
    echo "<h2 style='color: #0f172a; border-bottom: 2px solid #e2e8f0; padding-bottom: 10px;'>Solaris Database Setup & Seed</h2>";
}

try {
    require_once __DIR__ . '/config/database.php';
    outputMsg("Database configuration loaded (Host: " . DB_HOST . ", Database: " . DB_NAME . ", User: " . DB_USER . ").");

    $pdo = Database::getConnection();
    outputMsg("Successfully connected to MySQL/MariaDB.", 'success');
} catch (Throwable $e) {
    outputMsg("Connection Failed: " . $e->getMessage(), 'error');
    if (!$isCli) {
        echo "<div style='margin-top: 15px; background: #f8fafc; border: 1px solid #cbd5e1; padding: 15px; border-radius: 6px;'>";
        echo "<strong>How to fix:</strong><br>";
        echo "1. Create a <code>config/config.php</code> file using <code>config/config.sample.php</code>.<br>";
        echo "2. Put your database name, MySQL username, and password in <code>config/config.php</code>.<br>";
        echo "3. Ensure the database user has permissions for the specified database.";
        echo "</div></body></html>";
    }
    exit(1);
}

try {
    // Execute schema table definitions
    $schema = file_get_contents(__DIR__ . '/schema.sql');
    $pdo->exec($schema);
    outputMsg("Database tables verified/created successfully from schema.sql.", 'success');

    // Check if admin exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute(['admin']);
    $admin = $stmt->fetch();

    if (!$admin) {
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, role, wallet_balance, referral_code) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            'admin',
            'admin@solaris.io',
            password_hash('Admin123!', PASSWORD_BCRYPT),
            'admin',
            1000.00,
            'SOLARISADMIN'
        ]);
        $adminId = $pdo->lastInsertId();
        outputMsg("Created Administrator account: username 'admin', password 'Admin123!'", 'success');
    } else {
        $adminId = $admin['id'];
        outputMsg("Administrator account 'admin' already exists.");
    }

    // Check if demo user (referrer) exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute(['alex_investor']);
    $alex = $stmt->fetch();

    if (!$alex) {
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, role, wallet_balance, referral_code) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            'alex_investor',
            'alex@example.com',
            password_hash('User123!', PASSWORD_BCRYPT),
            'user',
            100.00,
            'ALEX40'
        ]);
        $alexId = $pdo->lastInsertId();
        outputMsg("Created Demo Referrer: username 'alex_investor', password 'User123!' (Code: ALEX40)", 'success');
    } else {
        $alexId = $alex['id'];
        outputMsg("Demo user 'alex_investor' already exists.");
    }

    // Check if demo investor exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute(['sarah_trader']);
    $sarah = $stmt->fetch();

    if (!$sarah) {
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, role, wallet_balance, referral_code, referred_by_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            'sarah_trader',
            'sarah@example.com',
            password_hash('User123!', PASSWORD_BCRYPT),
            'user',
            50.00,
            'SARAH99',
            $alexId
        ]);
        $sarahId = $pdo->lastInsertId();
        outputMsg("Created Demo Investor: username 'sarah_trader', password 'User123!'", 'success');

        // Create an active investment of 1 unit ($10.00 base reference capital)
        $units = 1;
        $capital = 10.00 * $units;
        $dailyInvestor = 0.50 * $units;
        $dailyRef = 0.25 * $units;
        $startDate = date('Y-m-d', strtotime('-5 days'));
        $endDate = date('Y-m-d', strtotime('+35 days'));

        $stmtInv = $pdo->prepare("INSERT INTO investments
            (user_id, units, capital_amount, daily_investor_benefit, daily_referral_income, cycle_total_days, days_completed, total_benefit_distributed, total_referral_distributed, status, start_date, end_date, last_distribution_date)
            VALUES (?, ?, ?, ?, ?, 40, 5, 2.50, 1.25, 'active', ?, ?, ?)");
        $stmtInv->execute([$sarahId, $units, $capital, $dailyInvestor, $dailyRef, $startDate, $endDate, date('Y-m-d', strtotime('-1 day'))]);
        $invId = $pdo->lastInsertId();

        for ($day = 1; $day <= 5; $day++) {
            $distDate = date('Y-m-d', strtotime("-" . (6 - $day) . " days"));
            $stmtDist = $pdo->prepare("INSERT INTO daily_distributions (investment_id, user_id, day_number, investor_benefit, referral_income, referrer_id, liquidity_status, distribution_date) VALUES (?, ?, ?, ?, ?, ?, 'Withdrawable daily', ?)");
            $stmtDist->execute([$invId, $sarahId, $day, 0.50, 0.25, $alexId, $distDate]);
        }

        $pdo->prepare("UPDATE users SET wallet_balance = wallet_balance + 2.50, total_invested = 10.00, total_earned = 2.50 WHERE id = ?")->execute([$sarahId]);
        $pdo->prepare("INSERT INTO transactions (user_id, type, amount, balance_after, description, reference_id) VALUES (?, 'investment', -10.00, 40.00, 'Subscribed to 1x Solaria 40 Trading Pool Unit', ?)")->execute([$sarahId, $invId]);
        $pdo->prepare("INSERT INTO transactions (user_id, type, amount, balance_after, description, reference_id) VALUES (?, 'daily_benefit', 2.50, 42.50, 'Days 1-5 Investor Benefit accumulated ($0.50/day)', ?)")->execute([$sarahId, $invId]);

        $pdo->prepare("UPDATE users SET wallet_balance = wallet_balance + 1.25, total_earned = total_earned + 1.25 WHERE id = ?")->execute([$alexId]);
        $pdo->prepare("INSERT INTO transactions (user_id, type, amount, balance_after, description, reference_id) VALUES (?, 'referral_income', 1.25, 101.25, 'Days 1-5 Referral Income from sarah_trader ($0.25/day)', ?)")->execute([$alexId, $invId]);

        outputMsg("Created sample active Solaria 40 unit with 5 completed days.", 'success');
    } else {
        outputMsg("Demo user 'sarah_trader' already exists.");
    }

    outputMsg("Database initialization & seeding completed successfully!", 'success');

    if (!$isCli) {
        echo "<div style='margin-top: 20px;'><a href='login.php' style='display: inline-block; background: #0f172a; color: #fff; padding: 10px 18px; border-radius: 6px; text-decoration: none; font-weight: bold;'>Go to Solaris Login &rarr;</a></div>";
        echo "</body></html>";
    }
} catch (Throwable $e) {
    outputMsg("Seeding Error: " . $e->getMessage(), 'error');
    if (!$isCli) {
        echo "</body></html>";
    }
    exit(1);
}
