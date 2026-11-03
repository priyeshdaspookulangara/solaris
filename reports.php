<?php
require_once __DIR__ . '/includes/Auth.php';
require_once __DIR__ . '/includes/TradingPool.php';

Auth::requireLogin();
$user = Auth::user();
$pdo = Database::getConnection();

// Filters
$typeFilter = $_GET['type'] ?? 'ALL';
$startDate = $_GET['start_date'] ?? '';
$endDate = $_GET['end_date'] ?? '';

// Build query for user transactions
$sql = "SELECT * FROM transactions WHERE user_id = :user_id";
$params = [':user_id' => $user['id']];

if ($typeFilter !== 'ALL' && !empty($typeFilter)) {
    $sql .= " AND type = :type";
    $params[':type'] = $typeFilter;
}

if (!empty($startDate)) {
    $sql .= " AND created_at >= :start_date";
    $params[':start_date'] = $startDate . ' 00:00:00';
}

if (!empty($endDate)) {
    $sql .= " AND created_at <= :end_date";
    $params[':end_date'] = $endDate . ' 23:59:59';
}

$sql .= " ORDER BY created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$transactions = $stmt->fetchAll();

// Aggregates for report header
$stmtSummary = $pdo->prepare("SELECT
    COALESCE(SUM(CASE WHEN type = 'investment' THEN amount ELSE 0 END), 0) AS total_invested,
    COALESCE(SUM(CASE WHEN type = 'daily_benefit' THEN amount ELSE 0 END), 0) AS total_benefits,
    COALESCE(SUM(CASE WHEN type = 'referral_income' THEN amount ELSE 0 END), 0) AS total_referrals,
    COALESCE(SUM(CASE WHEN type = 'withdrawal' THEN amount ELSE 0 END), 0) AS total_withdrawn
    FROM transactions WHERE user_id = :uid");
$stmtSummary->execute([':uid' => $user['id']]);
$userSummary = $stmtSummary->fetch();

// Also retrieve user's Solaria 40 contract distributions
$stmtDist = $pdo->prepare("SELECT dd.*, i.id as contract_id, i.units, i.capital_amount
    FROM daily_distributions dd
    JOIN investments i ON dd.investment_id = i.id
    WHERE dd.user_id = :uid
    ORDER BY dd.distribution_date DESC, dd.day_number DESC");
$stmtDist->execute([':uid' => $user['id']]);
$distributions = $stmtDist->fetchAll();

$pageTitle = "Customer Financial Statement - Solaris";
$useSidebar = true;
require_once __DIR__ . '/includes/header.php';
?>

<style>
@media print {
    /* Hide non-printable elements */
    aside, header, nav, .no-print, button, form, .print-hide {
        display: none !important;
    }
    body, main, .print-container {
        background: #ffffff !important;
        color: #000000 !important;
        padding: 0 !important;
        margin: 0 !important;
        width: 100% !important;
    }
    .print-only {
        display: block !important;
    }
    .card-print {
        border: 1px solid #ddd !important;
        box-shadow: none !important;
        break-inside: avoid;
    }
    table {
        width: 100% !important;
        border-collapse: collapse !important;
    }
    th, td {
        border: 1px solid #ccc !important;
        padding: 6px 10px !important;
        color: #000 !important;
        font-size: 11pt !important;
    }
    th {
        background-color: #f3f4f6 !important;
    }
}
.print-only {
    display: none;
}
</style>

<div class="print-container">
    <!-- Printable Official Letterhead Header (Always rendered, styled for print) -->
    <div class="print-only mb-6 border-b-2 border-slate-900 pb-4">
        <div class="flex justify-between items-start">
            <div>
                <h1 class="text-3xl font-extrabold text-slate-900 uppercase tracking-tight">SOLARIS TRADING POOL</h1>
                <p class="text-sm text-slate-600 font-semibold">Solaria 40 Accelerated Liquidity Program</p>
                <p class="text-xs text-slate-500">Official Customer Financial Statement & Ledger Audit</p>
            </div>
            <div class="text-right text-xs text-slate-600">
                <p><strong>Generated:</strong> <?= date('Y-m-d H:i:s') ?></p>
                <p><strong>Statement ID:</strong> STMT-<?= strtoupper(substr(md5($user['id'] . time()), 0, 10)) ?></p>
                <p><strong>Status:</strong> <span class="text-green-700 font-bold">VERIFIED LEDGER</span></p>
            </div>
        </div>
        <div class="mt-4 pt-3 border-t border-slate-200 grid grid-cols-2 text-xs">
            <div>
                <p><strong>Username:</strong> <?= htmlspecialchars($user['username']) ?></p>
                <p><strong>Email Address:</strong> <?= htmlspecialchars($user['email']) ?></p>
            </div>
            <div class="text-right">
                <p><strong>Current Wallet Liquidity:</strong> $<?= number_format($user['wallet_balance'], 2) ?></p>
                <p><strong>Referral Code:</strong> <?= htmlspecialchars($user['referral_code']) ?></p>
                <p><strong>Account Created:</strong> <?= date('M d, Y', strtotime($user['created_at'])) ?></p>
            </div>
        </div>
    </div>

    <!-- Screen Toolbar -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6 no-print">
        <div>
            <span class="text-xs font-bold uppercase tracking-wider text-amber-500">Official Records & Ledger</span>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">Customer Financial Statement</h1>
            <p class="text-sm text-slate-500">Review, filter, and print your complete Solaria 40 financial history.</p>
        </div>
        <div class="flex items-center gap-2">
            <button onclick="window.print()" class="px-4 py-2.5 bg-slate-900 hover:bg-slate-800 text-white font-bold rounded-lg text-sm shadow flex items-center gap-2 transition cursor-pointer">
                <i class="fa-solid fa-print"></i> Print Statement
            </button>
            <a href="reports.php" class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold rounded-lg text-sm transition">
                <i class="fa-solid fa-rotate"></i> Reset Filters
            </a>
        </div>
    </div>

    <!-- Summary KPI Cards -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
        <div class="bg-white p-4 rounded-xl border border-slate-200 card-print shadow-sm">
            <div class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Total Subscribed</div>
            <div class="text-xl font-extrabold text-blue-600">$<?= number_format($userSummary['total_invested'], 2) ?></div>
            <div class="text-xs text-slate-400">Solaria 40 Capital</div>
        </div>
        <div class="bg-white p-4 rounded-xl border border-slate-200 card-print shadow-sm">
            <div class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Investor Benefits</div>
            <div class="text-xl font-extrabold text-emerald-600">+$<?= number_format($userSummary['total_benefits'], 2) ?></div>
            <div class="text-xs text-slate-400">Daily Trading Yield</div>
        </div>
        <div class="bg-white p-4 rounded-xl border border-slate-200 card-print shadow-sm">
            <div class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Referral Rewards</div>
            <div class="text-xl font-extrabold text-amber-600">+$<?= number_format($userSummary['total_referrals'], 2) ?></div>
            <div class="text-xs text-slate-400">Affiliate Benefits</div>
        </div>
        <div class="bg-white p-4 rounded-xl border border-slate-200 card-print shadow-sm">
            <div class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Withdrawn Liquidity</div>
            <div class="text-xl font-extrabold text-indigo-600">$<?= number_format($userSummary['total_withdrawn'], 2) ?></div>
            <div class="text-xs text-slate-400">Paid Out to Wallet</div>
        </div>
    </div>

    <!-- Filter Form (Hidden when printing) -->
    <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm mb-6 no-print">
        <form method="GET" class="grid grid-cols-1 sm:grid-cols-4 gap-4 items-end">
            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Transaction Type</label>
                <select name="type" class="w-full text-sm border border-slate-300 rounded-lg px-3 py-2 bg-slate-50 focus:ring-2 focus:ring-amber-500">
                    <option value="ALL" <?= $typeFilter === 'ALL' ? 'selected' : '' ?>>All Transactions</option>
                    <option value="investment" <?= $typeFilter === 'investment' ? 'selected' : '' ?>>Capital Subscriptions</option>
                    <option value="daily_benefit" <?= $typeFilter === 'daily_benefit' ? 'selected' : '' ?>>Daily Investor Benefits</option>
                    <option value="referral_income" <?= $typeFilter === 'referral_income' ? 'selected' : '' ?>>Daily Referral Benefits</option>
                    <option value="withdrawal" <?= $typeFilter === 'withdrawal' ? 'selected' : '' ?>>Withdrawals</option>
                    <option value="deposit" <?= $typeFilter === 'deposit' ? 'selected' : '' ?>>Deposits</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Start Date</label>
                <input type="date" name="start_date" value="<?= htmlspecialchars($startDate) ?>" class="w-full text-sm border border-slate-300 rounded-lg px-3 py-2 bg-slate-50 focus:ring-2 focus:ring-amber-500">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase mb-1">End Date</label>
                <input type="date" name="end_date" value="<?= htmlspecialchars($endDate) ?>" class="w-full text-sm border border-slate-300 rounded-lg px-3 py-2 bg-slate-50 focus:ring-2 focus:ring-amber-500">
            </div>
            <div>
                <button type="submit" class="w-full bg-amber-500 hover:bg-amber-600 text-slate-900 font-bold py-2 px-4 rounded-lg text-sm shadow transition">
                    <i class="fa-solid fa-filter mr-1"></i> Apply Filter
                </button>
            </div>
        </form>
    </div>

    <!-- Detailed Transaction Ledger Table -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden mb-8 card-print">
        <div class="px-5 py-4 border-b border-slate-200 bg-slate-50 flex justify-between items-center">
            <h2 class="text-base font-bold text-slate-800">Complete Ledger Activity Log</h2>
            <span class="text-xs text-slate-500 font-medium"><?= count($transactions) ?> record(s) found</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-600">
                <thead class="bg-slate-100 text-xs uppercase font-bold text-slate-700 border-b border-slate-200">
                    <tr>
                        <th class="px-4 py-3">Tx ID</th>
                        <th class="px-4 py-3">Date & Time</th>
                        <th class="px-4 py-3">Type</th>
                        <th class="px-4 py-3">Description</th>
                        <th class="px-4 py-3 text-right">Amount</th>
                        <th class="px-4 py-3 text-right">Closing Balance</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    <?php if (empty($transactions)): ?>
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-slate-400 italic">No transactions found matching the filter criteria.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($transactions as $tx): ?>
                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-3 font-mono text-xs text-slate-500">#<?= $tx['id'] ?></td>
                                <td class="px-4 py-3 text-xs whitespace-nowrap"><?= date('Y-m-d H:i', strtotime($tx['created_at'])) ?></td>
                                <td class="px-4 py-3">
                                    <?php if ($tx['type'] === 'investment'): ?>
                                        <span class="px-2 py-0.5 rounded text-xs font-bold bg-blue-100 text-blue-800">INVESTMENT</span>
                                    <?php elseif ($tx['type'] === 'daily_benefit'): ?>
                                        <span class="px-2 py-0.5 rounded text-xs font-bold bg-emerald-100 text-emerald-800">INVESTOR BENEFIT</span>
                                    <?php elseif ($tx['type'] === 'referral_income'): ?>
                                        <span class="px-2 py-0.5 rounded text-xs font-bold bg-amber-100 text-amber-800">REFERRAL BONUS</span>
                                    <?php elseif ($tx['type'] === 'withdrawal'): ?>
                                        <span class="px-2 py-0.5 rounded text-xs font-bold bg-purple-100 text-purple-800">WITHDRAWAL</span>
                                    <?php elseif ($tx['type'] === 'deposit'): ?>
                                        <span class="px-2 py-0.5 rounded text-xs font-bold bg-teal-100 text-teal-800">DEPOSIT</span>
                                    <?php else: ?>
                                        <span class="px-2 py-0.5 rounded text-xs font-bold bg-slate-100 text-slate-800"><?= strtoupper($tx['type']) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-3 text-xs"><?= htmlspecialchars($tx['description']) ?></td>
                                <td class="px-4 py-3 text-right font-bold whitespace-nowrap <?= in_array($tx['type'], ['daily_benefit', 'referral_income', 'deposit', 'withdrawal_refund']) ? 'text-emerald-600' : 'text-slate-800' ?>">
                                    <?= in_array($tx['type'], ['daily_benefit', 'referral_income', 'deposit', 'withdrawal_refund']) ? '+' : '-' ?>$<?= number_format($tx['amount'], 2) ?>
                                </td>
                                <td class="px-4 py-3 text-right font-mono font-bold text-slate-700 whitespace-nowrap">
                                    $<?= number_format($tx['balance_after'], 2) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Solaria 40 Distribution Audit Schedule -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden card-print">
        <div class="px-5 py-4 border-b border-slate-200 bg-slate-50 flex justify-between items-center">
            <h2 class="text-base font-bold text-slate-800">Solaria 40 Daily Accrual Schedule Breakdown</h2>
            <span class="text-xs text-slate-500 font-medium"><?= count($distributions) ?> day distribution(s)</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-600">
                <thead class="bg-slate-100 text-xs uppercase font-bold text-slate-700 border-b border-slate-200">
                    <tr>
                        <th class="px-4 py-3">Contract</th>
                        <th class="px-4 py-3">Cycle Day</th>
                        <th class="px-4 py-3">Date Disbursed</th>
                        <th class="px-4 py-3 text-right">Investor Yield ($0.50/unit)</th>
                        <th class="px-4 py-3 text-right">Referral Yield ($0.25/unit)</th>
                        <th class="px-4 py-3 text-center">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    <?php if (empty($distributions)): ?>
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-slate-400 italic">No cycle distributions accrued yet. Distributions occur daily.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($distributions as $dist): ?>
                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-3 font-semibold text-slate-800">Contract #<?= $dist['contract_id'] ?> (<?= $dist['units'] ?> unit)</td>
                                <td class="px-4 py-3 font-bold text-indigo-700">Day <?= $dist['day_number'] ?> of 40</td>
                                <td class="px-4 py-3 text-xs"><?= htmlspecialchars($dist['distribution_date']) ?></td>
                                <td class="px-4 py-3 text-right font-bold text-emerald-600">+$<?= number_format($dist['investor_benefit'], 2) ?></td>
                                <td class="px-4 py-3 text-right font-bold text-amber-600">+$<?= number_format($dist['referral_income'], 2) ?></td>
                                <td class="px-4 py-3 text-center">
                                    <span class="px-2 py-0.5 rounded text-xs font-bold bg-green-100 text-green-800">CREDITED</span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Official Signature Block for Printed Statement -->
    <div class="print-only mt-12 pt-6 border-t-2 border-slate-400 grid grid-cols-2 text-xs">
        <div>
            <p class="font-bold text-slate-800">CUSTOMER ATTESTATION</p>
            <p class="text-slate-500 mt-1">I certify that the above statement reflects my Solaria 40 transactions.</p>
            <div class="mt-8 border-b border-slate-400 w-48"></div>
            <p class="text-slate-600 mt-1">Customer Signature / Date</p>
        </div>
        <div class="text-right">
            <p class="font-bold text-slate-800">SOLARIS FINANCIAL CLEARING</p>
            <p class="text-slate-500 mt-1">Automated Daily Liquidity Reconciliation Engine</p>
            <div class="mt-8 border-b border-slate-400 w-48 ml-auto"></div>
            <p class="text-slate-600 mt-1">Authorized Compliance Officer</p>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
