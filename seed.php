<?php
// seed.php - Seeds sample admin and users
require_once __DIR__ . '/config/database.php';

$pdo = Database::getConnection();

// Execute schema
$schema = file_get_contents(__DIR__ . '/schema.sql');
$pdo->exec($schema);

// Check if admin exists
$stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
$stmt->execute(['admin']);
$admin = $stmt->fetch();

if (!$admin) {
    // Insert admin
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
    echo "Admin user created (admin / Admin123!).\n";
} else {
    $adminId = $admin['id'];
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
    echo "Demo referrer user created (alex_investor / User123!).\n";
} else {
    $alexId = $alex['id'];
}

// Check if demo investor exists, referred by alex
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
    echo "Demo investor user created (sarah_trader / User123!, referred by alex_investor).\n";

    // Create an active investment of 1 unit ($10.00 base reference capital)
    // 40 days cycle: $0.50/day investor benefit, $0.25/day referral income
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

    // Insert 5 past distributions
    for ($day = 1; $day <= 5; $day++) {
        $distDate = date('Y-m-d', strtotime("-" . (6 - $day) . " days"));
        $stmtDist = $pdo->prepare("INSERT INTO daily_distributions (investment_id, user_id, day_number, investor_benefit, referral_income, referrer_id, liquidity_status, distribution_date) VALUES (?, ?, ?, ?, ?, ?, 'Withdrawable daily', ?)");
        $stmtDist->execute([$invId, $sarahId, $day, 0.50, 0.25, $alexId, $distDate]);
    }

    // Sarah wallet update and transactions
    $pdo->prepare("UPDATE users SET wallet_balance = wallet_balance + 2.50, total_invested = 10.00, total_earned = 2.50 WHERE id = ?")->execute([$sarahId]);
    $pdo->prepare("INSERT INTO transactions (user_id, type, amount, balance_after, description, reference_id) VALUES (?, 'investment', -10.00, 40.00, 'Subscribed to 1x Solaria 40 Trading Pool Unit', ?)")->execute([$sarahId, $invId]);
    $pdo->prepare("INSERT INTO transactions (user_id, type, amount, balance_after, description, reference_id) VALUES (?, 'daily_benefit', 2.50, 42.50, 'Days 1-5 Investor Benefit accumulated ($0.50/day)', ?)")->execute([$sarahId, $invId]);

    // Alex referral wallet update
    $pdo->prepare("UPDATE users SET wallet_balance = wallet_balance + 1.25, total_earned = total_earned + 1.25 WHERE id = ?")->execute([$alexId]);
    $pdo->prepare("INSERT INTO transactions (user_id, type, amount, balance_after, description, reference_id) VALUES (?, 'referral_income', 1.25, 101.25, 'Days 1-5 Referral Income from sarah_trader ($0.25/day)', ?)")->execute([$alexId, $invId]);

    echo "Sample 40-day active pool created with 5 completed days.\n";
}

echo "Database seeded successfully.\n";
