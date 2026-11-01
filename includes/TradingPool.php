<?php
// includes/TradingPool.php
require_once __DIR__ . '/../config/database.php';

class TradingPool {
    public const BASE_CAPITAL_PER_UNIT = 10.00;     // $10.00 base reference capital
    public const DAILY_INVESTOR_BENEFIT = 0.50;     // $0.50 daily Investor Benefit
    public const DAILY_REFERRAL_INCOME = 0.25;      // $0.25 daily Referral Income
    public const CYCLE_DAYS = 40;                   // 40-Day cycle

    /**
     * Subscribe user to Solaria 40 Trading Pool
     */
    public static function createInvestment(int $userId, int $units): array {
        if ($units < 1) {
            return ['success' => false, 'error' => 'Units must be at least 1.'];
        }

        $pdo = Database::getConnection();
        $pdo->beginTransaction();

        try {
            // Lock user row
            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? FOR UPDATE");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();

            if (!$user) {
                $pdo->rollBack();
                return ['success' => false, 'error' => 'User not found.'];
            }

            $totalCost = $units * self::BASE_CAPITAL_PER_UNIT;
            if ($user['wallet_balance'] < $totalCost) {
                $pdo->rollBack();
                return ['success' => false, 'error' => 'Insufficient wallet balance ($' . number_format($user['wallet_balance'], 2) . '). Required: $' . number_format($totalCost, 2)];
            }

            $newBalance = $user['wallet_balance'] - $totalCost;
            $newTotalInvested = $user['total_invested'] + $totalCost;

            // Deduct balance
            $stmtUpdateUser = $pdo->prepare("UPDATE users SET wallet_balance = ?, total_invested = ? WHERE id = ?");
            $stmtUpdateUser->execute([$newBalance, $newTotalInvested, $userId]);

            // Calculate cycle values
            $dailyInvestor = $units * self::DAILY_INVESTOR_BENEFIT;
            $dailyRef = $units * self::DAILY_REFERRAL_INCOME;
            $startDate = date('Y-m-d');
            $endDate = date('Y-m-d', strtotime('+' . self::CYCLE_DAYS . ' days'));

            // Create investment record
            $stmtInv = $pdo->prepare("INSERT INTO investments
                (user_id, units, capital_amount, daily_investor_benefit, daily_referral_income, cycle_total_days, days_completed, status, start_date, end_date)
                VALUES (?, ?, ?, ?, ?, ?, 0, 'active', ?, ?)");
            $stmtInv->execute([$userId, $units, $totalCost, $dailyInvestor, $dailyRef, self::CYCLE_DAYS, $startDate, $endDate]);
            $investmentId = $pdo->lastInsertId();

            // Record transaction
            $stmtTx = $pdo->prepare("INSERT INTO transactions (user_id, type, amount, balance_after, description, reference_id) VALUES (?, 'investment', ?, ?, ?, ?)");
            $stmtTx->execute([
                $userId,
                -$totalCost,
                $newBalance,
                "Joined Solaria 40 Trading Pool: {$units} unit(s) @ $" . number_format(self::BASE_CAPITAL_PER_UNIT, 2) . " each ($" . number_format($totalCost, 2) . " capital)",
                $investmentId
            ]);

            $pdo->commit();
            return ['success' => true, 'investment_id' => $investmentId];
        } catch (Exception $e) {
            $pdo->rollBack();
            return ['success' => false, 'error' => 'Investment failed: ' . $e->getMessage()];
        }
    }

    /**
     * Executes 1 day cycle progression on an investment
     */
    public static function processDailyYieldForInvestment(int $investmentId): array {
        $pdo = Database::getConnection();
        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare("SELECT * FROM investments WHERE id = ? FOR UPDATE");
            $stmt->execute([$investmentId]);
            $inv = $stmt->fetch();

            if (!$inv) {
                $pdo->rollBack();
                return ['success' => false, 'error' => 'Investment not found'];
            }

            if ($inv['status'] !== 'active') {
                $pdo->rollBack();
                return ['success' => false, 'error' => 'Investment is not active'];
            }

            if ($inv['days_completed'] >= $inv['cycle_total_days']) {
                $pdo->prepare("UPDATE investments SET status = 'completed' WHERE id = ?")->execute([$investmentId]);
                $pdo->commit();
                return ['success' => false, 'error' => 'Investment cycle already completed'];
            }

            $currentDay = $inv['days_completed'] + 1;
            $investorBenefit = $inv['daily_investor_benefit'];
            $referralIncome = $inv['daily_referral_income'];
            $userId = $inv['user_id'];
            $today = date('Y-m-d');

            // Find user referrer
            $stmtUser = $pdo->prepare("SELECT referred_by_id, wallet_balance, total_earned FROM users WHERE id = ? FOR UPDATE");
            $stmtUser->execute([$userId]);
            $user = $stmtUser->fetch();
            $referrerId = $user['referred_by_id'];

            // Record daily distribution
            $stmtDist = $pdo->prepare("INSERT INTO daily_distributions
                (investment_id, user_id, day_number, investor_benefit, referral_income, referrer_id, liquidity_status, distribution_date)
                VALUES (?, ?, ?, ?, ?, ?, 'Withdrawable daily', ?)");
            $stmtDist->execute([$investmentId, $userId, $currentDay, $investorBenefit, $referralIncome, $referrerId, $today]);
            $distId = $pdo->lastInsertId();

            // Credit Investor Benefit to user wallet (Withdrawable daily!)
            $newUserBalance = $user['wallet_balance'] + $investorBenefit;
            $newUserEarned = $user['total_earned'] + $investorBenefit;
            $stmtUpUser = $pdo->prepare("UPDATE users SET wallet_balance = ?, total_earned = ? WHERE id = ?");
            $stmtUpUser->execute([$newUserBalance, $newUserEarned, $userId]);

            $stmtTx1 = $pdo->prepare("INSERT INTO transactions (user_id, type, amount, balance_after, description, reference_id) VALUES (?, 'daily_benefit', ?, ?, ?, ?)");
            $stmtTx1->execute([
                $userId,
                $investorBenefit,
                $newUserBalance,
                "Solaria 40 Day {$currentDay} Investor Benefit ($" . number_format($investorBenefit, 2) . ") - Liquidity Withdrawable Daily",
                $investmentId
            ]);

            // If user has a referrer, credit referral income to referrer
            if ($referrerId) {
                $stmtRef = $pdo->prepare("SELECT wallet_balance, total_earned FROM users WHERE id = ? FOR UPDATE");
                $stmtRef->execute([$referrerId]);
                $referrer = $stmtRef->fetch();
                if ($referrer) {
                    $newRefBalance = $referrer['wallet_balance'] + $referralIncome;
                    $newRefEarned = $referrer['total_earned'] + $referralIncome;
                    $stmtUpRef = $pdo->prepare("UPDATE users SET wallet_balance = ?, total_earned = ? WHERE id = ?");
                    $stmtUpRef->execute([$newRefBalance, $newRefEarned, $referrerId]);

                    $stmtTx2 = $pdo->prepare("INSERT INTO transactions (user_id, type, amount, balance_after, description, reference_id) VALUES (?, 'referral_income', ?, ?, ?, ?)");
                    $stmtTx2->execute([
                        $referrerId,
                        $referralIncome,
                        $newRefBalance,
                        "Solaria 40 Day {$currentDay} Referral Income from User #{$userId} ($" . number_format($referralIncome, 2) . ")",
                        $investmentId
                    ]);
                }
            }

            // Update investment state
            $newDaysCompleted = $currentDay;
            $newTotalBenefit = $inv['total_benefit_distributed'] + $investorBenefit;
            $newTotalReferral = $inv['total_referral_distributed'] + $referralIncome;
            $newStatus = ($newDaysCompleted >= $inv['cycle_total_days']) ? 'completed' : 'active';

            $stmtUpInv = $pdo->prepare("UPDATE investments SET
                days_completed = ?,
                total_benefit_distributed = ?,
                total_referral_distributed = ?,
                status = ?,
                last_distribution_date = ?
                WHERE id = ?");
            $stmtUpInv->execute([$newDaysCompleted, $newTotalBenefit, $newTotalReferral, $newStatus, $today, $investmentId]);

            $pdo->commit();
            return [
                'success' => true,
                'day_completed' => $currentDay,
                'investor_benefit' => $investorBenefit,
                'referral_income' => $referralIncome,
                'is_completed' => ($newStatus === 'completed')
            ];
        } catch (Exception $e) {
            $pdo->rollBack();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Run daily distributions for all active pools
     */
    public static function processAllActivePools(): array {
        $pdo = Database::getConnection();
        $stmt = $pdo->query("SELECT id FROM investments WHERE status = 'active' AND days_completed < cycle_total_days");
        $pools = $stmt->fetchAll();

        $processed = 0;
        $errors = [];

        foreach ($pools as $pool) {
            $res = self::processDailyYieldForInvestment((int)$pool['id']);
            if ($res['success']) {
                $processed++;
            } else {
                $errors[] = "Pool #{$pool['id']}: " . $res['error'];
            }
        }

        return ['processed' => $processed, 'errors' => $errors];
    }

    /**
     * Manual deposit / funding wallet
     */
    public static function depositFunds(int $userId, float $amount): array {
        if ($amount <= 0) {
            return ['success' => false, 'error' => 'Deposit amount must be positive.'];
        }

        $pdo = Database::getConnection();
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("SELECT wallet_balance FROM users WHERE id = ? FOR UPDATE");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
            if (!$user) {
                $pdo->rollBack();
                return ['success' => false, 'error' => 'User not found.'];
            }

            $newBalance = $user['wallet_balance'] + $amount;
            $pdo->prepare("UPDATE users SET wallet_balance = ? WHERE id = ?")->execute([$newBalance, $userId]);

            $pdo->prepare("INSERT INTO transactions (user_id, type, amount, balance_after, description) VALUES (?, 'deposit', ?, ?, ?)")
                ->execute([$userId, $amount, $newBalance, "Wallet deposit added ($" . number_format($amount, 2) . ")"]);

            $pdo->commit();
            return ['success' => true, 'new_balance' => $newBalance];
        } catch (Exception $e) {
            $pdo->rollBack();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Request a withdrawal
     */
    public static function requestWithdrawal(int $userId, float $amount, string $address, string $method = 'USDT-TRC20'): array {
        if ($amount < 1.00) {
            return ['success' => false, 'error' => 'Minimum withdrawal is $1.00.'];
        }
        $address = trim($address);
        if (empty($address)) {
            return ['success' => false, 'error' => 'Destination address is required.'];
        }

        $pdo = Database::getConnection();
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("SELECT wallet_balance, total_withdrawn FROM users WHERE id = ? FOR UPDATE");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();

            if (!$user || $user['wallet_balance'] < $amount) {
                $pdo->rollBack();
                return ['success' => false, 'error' => 'Insufficient wallet balance ($' . number_format($user['wallet_balance'] ?? 0, 2) . ').'];
            }

            $newBalance = $user['wallet_balance'] - $amount;
            $newWithdrawn = $user['total_withdrawn'] + $amount;

            $pdo->prepare("UPDATE users SET wallet_balance = ?, total_withdrawn = ? WHERE id = ?")
                ->execute([$newBalance, $newWithdrawn, $userId]);

            $stmtWithdraw = $pdo->prepare("INSERT INTO withdrawals (user_id, amount, payment_method, destination_address, status) VALUES (?, ?, ?, ?, 'pending')");
            $stmtWithdraw->execute([$userId, $amount, $method, $address]);
            $withdrawId = $pdo->lastInsertId();

            $pdo->prepare("INSERT INTO transactions (user_id, type, amount, balance_after, description, reference_id) VALUES (?, 'withdrawal', ?, ?, ?, ?)")
                ->execute([$userId, -$amount, $newBalance, "Withdrawal request submitted ({$method}) to " . substr($address, 0, 8) . '...', $withdrawId]);

            $pdo->commit();
            return ['success' => true, 'withdrawal_id' => $withdrawId];
        } catch (Exception $e) {
            $pdo->rollBack();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Admin action to approve or reject withdrawal
     */
    public static function updateWithdrawalStatus(int $withdrawalId, string $status, ?string $note = null): array {
        if (!in_array($status, ['approved', 'rejected'])) {
            return ['success' => false, 'error' => 'Invalid status.'];
        }

        $pdo = Database::getConnection();
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("SELECT * FROM withdrawals WHERE id = ? FOR UPDATE");
            $stmt->execute([$withdrawalId]);
            $w = $stmt->fetch();

            if (!$w || $w['status'] !== 'pending') {
                $pdo->rollBack();
                return ['success' => false, 'error' => 'Withdrawal not found or already processed.'];
            }

            if ($status === 'approved') {
                $pdo->prepare("UPDATE withdrawals SET status = 'approved', note = ?, processed_at = NOW() WHERE id = ?")
                    ->execute([$note, $withdrawalId]);
            } else {
                // Reject: refund balance to user
                $userId = $w['user_id'];
                $amount = $w['amount'];

                $stmtUser = $pdo->prepare("SELECT wallet_balance, total_withdrawn FROM users WHERE id = ? FOR UPDATE");
                $stmtUser->execute([$userId]);
                $user = $stmtUser->fetch();

                $refundBalance = $user['wallet_balance'] + $amount;
                $refundWithdrawn = max(0, $user['total_withdrawn'] - $amount);

                $pdo->prepare("UPDATE users SET wallet_balance = ?, total_withdrawn = ? WHERE id = ?")
                    ->execute([$refundBalance, $refundWithdrawn, $userId]);

                $pdo->prepare("UPDATE withdrawals SET status = 'rejected', note = ?, processed_at = NOW() WHERE id = ?")
                    ->execute([$note ?: 'Withdrawal rejected & refunded', $withdrawalId]);

                $pdo->prepare("INSERT INTO transactions (user_id, type, amount, balance_after, description, reference_id) VALUES (?, 'withdrawal_refund', ?, ?, ?, ?)")
                    ->execute([$userId, $amount, $refundBalance, "Refund for rejected withdrawal #{$withdrawalId}: " . ($note ?: 'Rejected'), $withdrawalId]);
            }

            $pdo->commit();
            return ['success' => true];
        } catch (Exception $e) {
            $pdo->rollBack();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
