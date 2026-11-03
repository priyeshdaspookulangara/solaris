<?php
require_once __DIR__ . '/includes/Auth.php';
require_once __DIR__ . '/includes/TradingPool.php';

Auth::requireAdmin();
$user = Auth::user();
$pdo = Database::getConnection();

// Filters
$userFilter = $_GET['user_id'] ?? 'ALL';
$typeFilter = $_GET['type'] ?? 'ALL';
$startDate = $_GET['start_date'] ?? '';
$endDate = $_GET['end_date'] ?? '';

// Build query for system-wide ledger
$sql = "SELECT t.*, u.username, u.email
        FROM transactions t
        JOIN users u ON t.user_id = u.id
        WHERE 1=1";
$params = [];

if ($userFilter !== 'ALL' && !empty($userFilter)) {
    $sql .= " AND t.user_id = :user_id";
    $params[':user_id'] = $userFilter;
}

if ($typeFilter !== 'ALL' && !empty($typeFilter)) {
    $sql .= " AND t.type = :type";
    $params[':type'] = $typeFilter;
}

if (!empty($startDate)) {
    $sql .= " AND t.created_at >= :start_date";
    $params[':start_date'] = $startDate . ' 00:00:00';
}

if (!empty($endDate)) {
    $sql .= " AND t.created_at <= :end_date";
    $params[':end_date'] = $endDate . ' 23:59:59';
}

$sql .= " ORDER BY t.created_at DESC LIMIT 500";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$transactions = $stmt->fetchAll();

// Fetch all registered users for filter dropdown
$usersList = $pdo->query("SELECT id, username, email FROM users ORDER BY username ASC")->fetchAll();

// System-wide Aggregates & Solaria 40 metrics
$totalContractsCount = $pdo->query("SELECT COUNT(*) FROM investments")->fetchColumn();

// Direct query for exact transaction aggregates
$stmtTxStats = $pdo->query("SELECT
    COALESCE(SUM(CASE WHEN type = 'investment' THEN amount ELSE 0 END), 0) AS total_capital,
    COALESCE(SUM(CASE WHEN type = 'daily_benefit' THEN amount ELSE 0 END), 0) AS total_investor_benefits,
    COALESCE(SUM(CASE WHEN type = 'referral_income' THEN amount ELSE 0 END), 0) AS total_referral_benefits,
    COALESCE(SUM(CASE WHEN type = 'withdrawal' THEN amount ELSE 0 END), 0) AS total_withdrawn
    FROM transactions");
$txStats = $stmtTxStats->fetch();

$totalYield = $txStats['total_investor_benefits'] + $txStats['total_referral_benefits'];
$totalLiabilities = $pdo->query("SELECT COALESCE(SUM(wallet_balance), 0) FROM users")->fetchColumn();

// Contracts audit list
$stmtContracts = $pdo->query("SELECT i.*, u.username, u.email
    FROM investments i
    JOIN users u ON i.user_id = u.id
    ORDER BY i.created_at DESC");
$contracts = $stmtContracts->fetchAll();

$pageTitle = "Solaria 40 System Audit & Financial Report - Solaris Admin";
$useSidebar = true;
require_once __DIR__ . '/includes/header.php';
?>

<style>
@media print {
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
        padding: 6px 8px !important;
        color: #000 !important;
        font-size: 10pt !important;
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
    <!-- Official Printable Admin Report Letterhead Header -->
    <div class="print-only mb-6 border-b-2 border-slate-900 pb-4">
        <div class="flex justify-between items-start">
            <div>
                <h1 class="text-3xl font-extrabold text-slate-900 uppercase tracking-tight">SOLARIS TRADING POOL</h1>
                <p class="text-sm text-slate-700 font-semibold">Master Financial Audit & Pool Reconciliation Report</p>
                <p class="text-xs text-slate-500">Official Solaria 40 System Comprehensive Operations Ledger</p>
            </div>
            <div class="text-right text-xs text-slate-600">
                <p><strong>Audit Generated:</strong> <?= date('Y-m-d H:i:s') ?></p>
                <p><strong>Audit Report ID:</strong> AUDIT-SYS-<?= strtoupper(substr(md5('solaris-admin' . time()), 0, 10)) ?></p>
                <p><strong>Auditor:</strong> <?= htmlspecialchars($user['username']) ?> (<?= htmlspecialchars($user['email']) ?>)</p>
            </div>
        </div>
        <div class="mt-4 pt-3 border-t border-slate-200 grid grid-cols-4 gap-2 text-xs">
            <div>
                <p><strong>Pool Capital:</strong> $<?= number_format($txStats['total_capital'], 2) ?></p>
            </div>
            <div>
                <p><strong>Yield Disbursed:</strong> $<?= number_format($totalYield, 2) ?></p>
            </div>
            <div>
                <p><strong>Withdrawn Liquidity:</strong> $<?= number_format($txStats['total_withdrawn'], 2) ?></p>
            </div>
            <div class="text-right">
                <p><strong>Member Balances:</strong> $<?= number_format($totalLiabilities, 2) ?></p>
            </div>
        </div>
    </div>

    <!-- Screen Toolbar -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6 no-print">
        <div>
            <span class="text-xs font-bold uppercase tracking-wider text-amber-500">Master Financial Control</span>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">System Audit & Financial Reports</h1>
            <p class="text-sm text-slate-500">Comprehensive liquidity, yield distribution, and ledger reconciliation.</p>
        </div>
        <div class="flex items-center gap-2">
            <button onclick="window.print()" class="px-4 py-2.5 bg-slate-900 hover:bg-slate-800 text-white font-bold rounded-lg text-sm shadow flex items-center gap-2 transition cursor-pointer">
                <i class="fa-solid fa-print"></i> Print Master Audit
            </button>
            <a href="admin_reports.php" class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold rounded-lg text-sm transition">
                <i class="fa-solid fa-rotate"></i> Reset Filters
            </a>
        </div>
    </div>

    <!-- Summary KPI Cards -->
    <div class="grid grid-cols-2 sm:grid-cols-5 gap-4 mb-6">
        <div class="bg-white p-4 rounded-xl border border-slate-200 card-print shadow-sm">
            <div class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Total Pool Capital</div>
            <div class="text-xl font-extrabold text-blue-600">$<?= number_format($txStats['total_capital'], 2) ?></div>
            <div class="text-xs text-slate-400"><?= $totalContractsCount ?> contracts created</div>
        </div>
        <div class="bg-white p-4 rounded-xl border border-slate-200 card-print shadow-sm">
            <div class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Investor Benefits</div>
            <div class="text-xl font-extrabold text-emerald-600">+$<?= number_format($txStats['total_investor_benefits'], 2) ?></div>
            <div class="text-xs text-slate-400">$0.50/unit/day</div>
        </div>
        <div class="bg-white p-4 rounded-xl border border-slate-200 card-print shadow-sm">
            <div class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Referral Disbursed</div>
            <div class="text-xl font-extrabold text-amber-600">+$<?= number_format($txStats['total_referral_benefits'], 2) ?></div>
            <div class="text-xs text-slate-400">$0.25/unit/day</div>
        </div>
        <div class="bg-white p-4 rounded-xl border border-slate-200 card-print shadow-sm">
            <div class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Withdrawn Paid</div>
            <div class="text-xl font-extrabold text-indigo-600">$<?= number_format($txStats['total_withdrawn'], 2) ?></div>
            <div class="text-xs text-slate-400">Approved payouts</div>
        </div>
        <div class="bg-white p-4 rounded-xl border border-slate-200 card-print shadow-sm">
            <div class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Total Net Liquidity</div>
            <div class="text-xl font-extrabold text-slate-800">$<?= number_format($totalLiabilities, 2) ?></div>
            <div class="text-xs text-slate-400">Current wallet balances</div>
        </div>
    </div>

    <!-- Filter Form (Hidden when printing) -->
    <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm mb-6 no-print">
        <form method="GET" class="grid grid-cols-1 sm:grid-cols-5 gap-4 items-end">
            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Member / Account</label>
                <select name="user_id" class="w-full text-sm border border-slate-300 rounded-lg px-3 py-2 bg-slate-50 focus:ring-2 focus:ring-amber-500">
                    <option value="ALL">All Accounts</option>
                    <?php foreach ($usersList as $u): ?>
                        <option value="<?= $u['id'] ?>" <?= $userFilter == $u['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($u['username']) ?> (<?= htmlspecialchars($u['email']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Transaction Type</label>
                <select name="type" class="w-full text-sm border border-slate-300 rounded-lg px-3 py-2 bg-slate-50 focus:ring-2 focus:ring-amber-500">
                    <option value="ALL" <?= $typeFilter === 'ALL' ? 'selected' : '' ?>>All Transaction Types</option>
                    <option value="investment" <?= $typeFilter === 'investment' ? 'selected' : '' ?>>Capital Subscriptions</option>
                    <option value="daily_benefit" <?= $typeFilter === 'daily_benefit' ? 'selected' : '' ?>>Investor Benefits</option>
                    <option value="referral_income" <?= $typeFilter === 'referral_income' ? 'selected' : '' ?>>Referral Rewards</option>
                    <option value="withdrawal" <?= $typeFilter === 'withdrawal' ? 'selected' : '' ?>>Withdrawals</option>
                    <option value="deposit" <?= $typeFilter === 'deposit' ? 'selected' : '' ?>>Deposits</option>
                    <option value="withdrawal_refund" <?= $typeFilter === 'withdrawal_refund' ? 'selected' : '' ?>>Refunds</option>
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
                    <i class="fa-solid fa-filter mr-1"></i> Filter Audit
                </button>
            </div>
        </form>
    </div>

    <!-- Master Transaction Audit Log Table -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden mb-8 card-print">
        <div class="px-5 py-4 border-b border-slate-200 bg-slate-50 flex justify-between items-center">
            <h2 class="text-base font-bold text-slate-800">Master Financial Transaction Ledger</h2>
            <span class="text-xs text-slate-500 font-medium"><?= count($transactions) ?> record(s)</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-600">
                <thead class="bg-slate-100 text-xs uppercase font-bold text-slate-700 border-b border-slate-200">
                    <tr>
                        <th class="px-4 py-3">Tx ID</th>
                        <th class="px-4 py-3">Timestamp</th>
                        <th class="px-4 py-3">Account</th>
                        <th class="px-4 py-3">Type</th>
                        <th class="px-4 py-3">Description</th>
                        <th class="px-4 py-3 text-right">Amount</th>
                        <th class="px-4 py-3 text-right">Balance After</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    <?php if (empty($transactions)): ?>
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-slate-400 italic">No records match the filter criteria.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($transactions as $tx): ?>
                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-3 font-mono text-xs text-slate-500">#<?= $tx['id'] ?></td>
                                <td class="px-4 py-3 text-xs whitespace-nowrap"><?= date('Y-m-d H:i', strtotime($tx['created_at'])) ?></td>
                                <td class="px-4 py-3">
                                    <div class="font-bold text-slate-800 text-xs"><?= htmlspecialchars($tx['username']) ?></div>
                                    <div class="text-[11px] text-slate-400"><?= htmlspecialchars($tx['email']) ?></div>
                                </td>
                                <td class="px-4 py-3">
                                    <?php if ($tx['type'] === 'investment'): ?>
                                        <span class="px-2 py-0.5 rounded text-xs font-bold bg-blue-100 text-blue-800">INVESTMENT</span>
                                    <?php elseif ($tx['type'] === 'daily_benefit'): ?>
                                        <span class="px-2 py-0.5 rounded text-xs font-bold bg-emerald-100 text-emerald-800">INVESTOR YIELD</span>
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

    <!-- Active Solaria 40 Contracts Reconciliation -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden card-print">
        <div class="px-5 py-4 border-b border-slate-200 bg-slate-50 flex justify-between items-center">
            <h2 class="text-base font-bold text-slate-800">Solaria 40 Contracts Lifecycle & Distribution Status</h2>
            <span class="text-xs text-slate-500 font-medium"><?= count($contracts) ?> total contracts</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-600">
                <thead class="bg-slate-100 text-xs uppercase font-bold text-slate-700 border-b border-slate-200">
                    <tr>
                        <th class="px-4 py-3">Contract ID</th>
                        <th class="px-4 py-3">Investor</th>
                        <th class="px-4 py-3">Units & Capital</th>
                        <th class="px-4 py-3">Daily Yield</th>
                        <th class="px-4 py-3">Current Day</th>
                        <th class="px-4 py-3 text-right">Total Distributed</th>
                        <th class="px-4 py-3 text-center">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    <?php if (empty($contracts)): ?>
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-slate-400 italic">No contracts recorded in system.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($contracts as $c): ?>
                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-3 font-mono font-bold text-xs text-slate-600">#<?= $c['id'] ?></td>
                                <td class="px-4 py-3">
                                    <div class="font-bold text-slate-800 text-xs"><?= htmlspecialchars($c['username']) ?></div>
                                    <div class="text-[11px] text-slate-400"><?= htmlspecialchars($c['email']) ?></div>
                                </td>
                                <td class="px-4 py-3 text-xs">
                                    <span class="font-bold text-slate-800"><?= $c['units'] ?> unit(s)</span>
                                    <span class="text-slate-500">($<?= number_format($c['capital_amount'], 2) ?>)</span>
                                </td>
                                <td class="px-4 py-3 text-xs font-bold text-emerald-600">
                                    +$<?= number_format($c['units'] * 0.50, 2) ?> / day
                                </td>
                                <td class="px-4 py-3 text-xs">
                                    <span class="font-bold text-indigo-700">Day <?= $c['days_completed'] ?></span> of <?= $c['cycle_total_days'] ?>
                                    <span class="text-[11px] text-slate-400">(<?= $c['cycle_total_days'] > 0 ? round(($c['days_completed'] / $c['cycle_total_days']) * 100) : 0 ?>%)</span>
                                </td>
                                <td class="px-4 py-3 text-right font-bold text-emerald-600 whitespace-nowrap">
                                    $<?= number_format($c['total_benefit_distributed'], 2) ?>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span class="px-2 py-0.5 rounded text-xs font-bold <?= strtolower($c['status']) === 'active' ? 'bg-green-100 text-green-800' : 'bg-slate-100 text-slate-800' ?>">
                                        <?= strtoupper($c['status']) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Official Signature Block for Master Printed Statement -->
    <div class="print-only mt-12 pt-6 border-t-2 border-slate-400 grid grid-cols-2 text-xs">
        <div>
            <p class="font-bold text-slate-800">LEAD AUDITOR ATTESTATION</p>
            <p class="text-slate-500 mt-1">I certify that the Solaria 40 platform transactions are balanced and verified.</p>
            <div class="mt-8 border-b border-slate-400 w-48"></div>
            <p class="text-slate-600 mt-1">Senior Compliance Officer / Date</p>
        </div>
        <div class="text-right">
            <p class="font-bold text-slate-800">SYSTEM ARCHITECT SIGN-OFF</p>
            <p class="text-slate-500 mt-1">Cryptographic & Financial Integrity Verified</p>
            <div class="mt-8 border-b border-slate-400 w-48 ml-auto"></div>
            <p class="text-slate-600 mt-1">Chief Risk Officer</p>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
