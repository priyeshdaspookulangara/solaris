<?php
// tests/test_trading_pool.php
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/TradingPool.php';

function runTests() {
    echo "=== Running Solaris / Solaria 40 Unit & Integration Tests ===\n";
    $pdo = Database::getConnection();

    // 1. Test Registration with Referral Code
    $testUser1 = "testuser_" . time();
    $reg1 = Auth::register($testUser1, "{$testUser1}@example.com", "Password123!");
    assert($reg1['success'] === true, "User 1 registration failed");
    $user1Id = $reg1['user_id'];
    echo "✓ Test User 1 registered (ID: {$user1Id})\n";

    // Fetch user 1 referral code
    $stmt = $pdo->prepare("SELECT referral_code FROM users WHERE id = ?");
    $stmt->execute([$user1Id]);
    $refCode1 = $stmt->fetchColumn();

    // Register User 2 referred by User 1
    $testUser2 = "refuser_" . time();
    $reg2 = Auth::register($testUser2, "{$testUser2}@example.com", "Password123!", $refCode1);
    assert($reg2['success'] === true, "User 2 registration with referral failed");
    $user2Id = $reg2['user_id'];

    $stmt = $pdo->prepare("SELECT referred_by_id FROM users WHERE id = ?");
    $stmt->execute([$user2Id]);
    $referredBy = $stmt->fetchColumn();
    assert((int)$referredBy === (int)$user1Id, "Referred_by_id does not match user 1");
    echo "✓ Test User 2 registered with referral to User 1\n";

    // 2. Test Wallet Balance and Investment
    // User 2 was given $20.00 signup bonus. They should be able to invest 2 units ($20.00)
    $invRes = TradingPool::createInvestment($user2Id, 2);
    assert($invRes['success'] === true, "Investment creation failed: " . ($invRes['error'] ?? ''));
    $invId = $invRes['investment_id'];

    $stmt = $pdo->prepare("SELECT * FROM investments WHERE id = ?");
    $stmt->execute([$invId]);
    $inv = $stmt->fetch();
    assert((float)$inv['capital_amount'] === 20.00, "Capital amount calculation mismatch");
    assert((float)$inv['daily_investor_benefit'] === 1.00, "Daily investor benefit mismatch (2 * $0.50)");
    assert((float)$inv['daily_referral_income'] === 0.50, "Daily referral income mismatch (2 * $0.25)");
    assert((int)$inv['cycle_total_days'] === 40, "Cycle total days should be 40");
    assert((int)$inv['days_completed'] === 0, "Initial days completed should be 0");
    echo "✓ 2x Solaria 40 unit investment successfully created ($20 capital, $1.00/day benefit, $0.50/day referral)\n";

    // 3. Test Daily Yield Progression
    $yieldRes = TradingPool::processDailyYieldForInvestment($invId);
    assert($yieldRes['success'] === true, "Daily yield processing failed: " . ($yieldRes['error'] ?? ''));
    assert($yieldRes['day_completed'] === 1, "Day completed should be 1");
    assert((float)$yieldRes['investor_benefit'] === 1.00, "Investor benefit should be 1.00");
    assert((float)$yieldRes['referral_income'] === 0.50, "Referral income should be 0.50");

    // Verify User 2 received $1.00
    $stmt = $pdo->prepare("SELECT wallet_balance FROM users WHERE id = ?");
    $stmt->execute([$user2Id]);
    $u2Bal = (float)$stmt->fetchColumn();
    assert($u2Bal === 1.00, "User 2 balance should be 1.00, got: {$u2Bal}");

    // Verify User 1 (Referrer) received $0.50 (Initial $20 + $0.50 = $20.50)
    $stmt = $pdo->prepare("SELECT wallet_balance FROM users WHERE id = ?");
    $stmt->execute([$user1Id]);
    $u1Bal = (float)$stmt->fetchColumn();
    assert($u1Bal === 20.50, "User 1 referrer balance should be 20.50, got: {$u1Bal}");
    echo "✓ Daily distribution calculated correctly: +$1.00 to investor, +$0.50 to referrer\n";

    // 4. Test Withdrawal Request
    $withdrawRes = TradingPool::requestWithdrawal($user2Id, 1.00, 'TXYZ1234567890TRONADDRESS');
    assert($withdrawRes['success'] === true, "Withdrawal failed: " . ($withdrawRes['error'] ?? ''));
    $withdrawId = $withdrawRes['withdrawal_id'];

    // Balance should now be 0.00
    $stmt->execute([$user2Id]);
    $u2BalAfter = (float)$stmt->fetchColumn();
    assert($u2BalAfter === 0.00, "Balance after withdrawal should be 0.00");
    echo "✓ Daily liquidity withdrawal of $1.00 submitted and deducted from wallet\n";

    // 5. Test Admin Withdrawal Approval
    $approveRes = TradingPool::updateWithdrawalStatus($withdrawId, 'approved', 'TXID_TEST_999');
    assert($approveRes['success'] === true, "Approval failed");

    $stmt = $pdo->prepare("SELECT status FROM withdrawals WHERE id = ?");
    $stmt->execute([$withdrawId]);
    assert($stmt->fetchColumn() === 'approved', "Withdrawal status should be approved");
    echo "✓ Admin withdrawal approval verified\n";

    // 6. Test Admin Withdrawal Rejection & Refund
    // Deposit $5 to user 2
    TradingPool::depositFunds($user2Id, 5.00);
    $wReq = TradingPool::requestWithdrawal($user2Id, 5.00, 'TXYZREJECTTEST');
    $wId2 = $wReq['withdrawal_id'];
    $rejectRes = TradingPool::updateWithdrawalStatus($wId2, 'rejected', 'Incorrect wallet address');
    assert($rejectRes['success'] === true, "Rejection failed");

    $stmt->execute([$user2Id]);
    $u2BalRefund = (float)$stmt->fetchColumn();
    assert($u2BalRefund === 5.00, "User balance should be refunded to 5.00");
    echo "✓ Withdrawal rejection and wallet refund verified\n";

    echo "=== All Tests Passed Successfully! ===\n";
}

runTests();
