<?php
// admin.php - Administrator Control Panel
require_once __DIR__ . '/includes/Auth.php';
require_once __DIR__ . '/includes/TradingPool.php';

Auth::requireAdmin();
$currentUser = Auth::user();
$pdo = Database::getConnection();

$message = null;
$error = null;

// Handle manual trigger of daily distribution
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'run_distribution') {
        $res = TradingPool::processAllActivePools();
        $message = "Daily distribution executed: {$res['processed']} pool(s) updated.";
        if (!empty($res['errors'])) {
            $error = implode("; ", $res['errors']);
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'process_withdrawal') {
        $wId = (int)$_POST['withdrawal_id'];
        $status = $_POST['status']; // 'approved' or 'rejected'
        $note = trim($_POST['note'] ?? '');
        $res = TradingPool::updateWithdrawalStatus($wId, $status, $note);
        if ($res['success']) {
            $message = "Withdrawal #{$wId} has been {$status}.";
        } else {
            $error = $res['error'];
        }
    }
}

// Fetch stats
$totalUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalCapital = $pdo->query("SELECT COALESCE(SUM(capital_amount), 0) FROM investments WHERE status = 'active'")->fetchColumn();
$totalDistributed = $pdo->query("SELECT COALESCE(SUM(total_benefit_distributed + total_referral_distributed), 0) FROM investments")->fetchColumn();
$pendingWithdrawalsCount = $pdo->query("SELECT COUNT(*) FROM withdrawals WHERE status = 'pending'")->fetchColumn();

// Fetch active pools
$activePools = $pdo->query("
    SELECT i.*, u.username
    FROM investments i
    JOIN users u ON i.user_id = u.id
    ORDER BY i.status ASC, i.id DESC
")->fetchAll();

// Fetch pending withdrawals
$pendingWithdrawals = $pdo->query("
    SELECT w.*, u.username, u.email
    FROM withdrawals w
    JOIN users u ON w.user_id = u.id
    WHERE w.status = 'pending'
    ORDER BY w.id ASC
")->fetchAll();

$pageTitle = 'Admin Trading Pool Control';
require_once __DIR__ . '/includes/header.php';
?>

<div class="max-w-7xl mx-auto px-4 py-8 space-y-8">
    <div class="flex flex-col md:flex-row md:items-center justify-between border-b-2 border-amber-500 pb-4 gap-4">
        <div>
            <div class="text-xs uppercase font-bold text-amber-600 tracking-wider flex items-center">
                <i class="fa-solid fa-lock mr-1.5"></i> Solaris Administrator Panel
            </div>
            <h1 class="text-2xl md:text-3xl font-extrabold text-slate-900 mt-1">Trading Pool &amp; Liquidity Orchestrator</h1>
            <p class="text-xs text-slate-500 mt-1">Manage Solaria 40 contracts, monitor liquidity cycles, and process daily schedules.</p>
        </div>

        <form method="POST" action="admin.php" onsubmit="return confirm('Trigger 24-hour progression schedule for all active pools?');">
            <input type="hidden" name="action" value="run_distribution">
            <button type="submit" class="bg-amber-600 hover:bg-amber-500 text-white font-bold px-5 py-2.5 rounded-lg shadow transition flex items-center space-x-2 text-sm">
                <i class="fa-solid fa-play"></i>
                <span>Execute Next Daily Progression</span>
            </button>
        </form>
    </div>

    <?php if ($message): ?>
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-lg text-sm flex items-center space-x-2">
            <i class="fa-solid fa-circle-check text-emerald-600"></i>
            <span><?= htmlspecialchars($message) ?></span>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg text-sm flex items-center space-x-2">
            <i class="fa-solid fa-circle-exclamation text-red-600"></i>
            <span><?= htmlspecialchars($error) ?></span>
        </div>
    <?php endif; ?>

    <!-- Overview Stats -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
            <div class="text-xs font-semibold uppercase text-slate-500">Active Capital in Pool</div>
            <div class="text-2xl font-black text-blue-700 mt-1">$<?= number_format($totalCapital, 2) ?></div>
            <div class="text-xs text-slate-400 mt-1">Solaria 40 unit subscriptions</div>
        </div>

        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
            <div class="text-xs font-semibold uppercase text-slate-500">Total Yield Disbursed</div>
            <div class="text-2xl font-black text-emerald-600 mt-1">$<?= number_format($totalDistributed, 2) ?></div>
            <div class="text-xs text-slate-400 mt-1">Investor benefits &amp; referral fees</div>
        </div>

        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
            <div class="text-xs font-semibold uppercase text-slate-500">Total Registered Members</div>
            <div class="text-2xl font-black text-purple-700 mt-1"><?= $totalUsers ?></div>
            <div class="text-xs text-slate-400 mt-1">Investors &amp; affiliates</div>
        </div>

        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
            <div class="text-xs font-semibold uppercase text-slate-500">Pending Withdrawals</div>
            <div class="text-2xl font-black text-amber-600 mt-1"><?= $pendingWithdrawalsCount ?></div>
            <div class="text-xs text-slate-400 mt-1">Daily liquidity requests</div>
        </div>
    </div>

    <!-- Pending Withdrawals Management -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-5 border-b border-slate-200 bg-slate-50/50 flex justify-between items-center">
            <div>
                <h2 class="text-base font-bold text-slate-900">Pending Liquidity Withdrawals</h2>
                <p class="text-xs text-slate-500">Approve or reject requests submitted from daily unlocked earnings</p>
            </div>
            <span class="bg-amber-100 text-amber-800 text-xs px-2.5 py-0.5 rounded-full font-bold">
                <?= count($pendingWithdrawals) ?> Pending
            </span>
        </div>

        <?php if (empty($pendingWithdrawals)): ?>
            <div class="p-6 text-center text-slate-400 text-xs">
                <i class="fa-solid fa-check-double text-2xl text-slate-300 mb-1"></i>
                <p>No pending withdrawals. All requests are cleared!</p>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-100 text-slate-600 uppercase font-semibold">
                        <tr>
                            <th class="py-3 px-4">ID</th>
                            <th class="py-3 px-4">User</th>
                            <th class="py-3 px-4">Amount</th>
                            <th class="py-3 px-4">Method &amp; Address</th>
                            <th class="py-3 px-4">Date</th>
                            <th class="py-3 px-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php foreach ($pendingWithdrawals as $pw): ?>
                            <tr>
                                <td class="py-3 px-4 font-mono font-bold">#<?= $pw['id'] ?></td>
                                <td class="py-3 px-4">
                                    <div class="font-bold text-slate-900"><?= htmlspecialchars($pw['username']) ?></div>
                                    <div class="text-[11px] text-slate-400"><?= htmlspecialchars($pw['email']) ?></div>
                                </td>
                                <td class="py-3 px-4 font-black text-sm text-emerald-700">$<?= number_format($pw['amount'], 2) ?></td>
                                <td class="py-3 px-4">
                                    <div class="font-semibold text-slate-700"><?= htmlspecialchars($pw['payment_method']) ?></div>
                                    <div class="font-mono text-[10px] text-slate-500"><?= htmlspecialchars($pw['destination_address']) ?></div>
                                </td>
                                <td class="py-3 px-4 text-slate-500 font-mono"><?= htmlspecialchars(substr($pw['created_at'], 0, 16)) ?></td>
                                <td class="py-3 px-4 text-right space-x-2">
                                    <form method="POST" action="admin.php" class="inline">
                                        <input type="hidden" name="action" value="process_withdrawal">
                                        <input type="hidden" name="withdrawal_id" value="<?= $pw['id'] ?>">
                                        <input type="hidden" name="status" value="approved">
                                        <button type="submit" class="bg-emerald-600 hover:bg-emerald-500 text-white px-3 py-1.5 rounded text-xs font-semibold shadow-sm">
                                            Approve
                                        </button>
                                    </form>
                                    <form method="POST" action="admin.php" class="inline">
                                        <input type="hidden" name="action" value="process_withdrawal">
                                        <input type="hidden" name="withdrawal_id" value="<?= $pw['id'] ?>">
                                        <input type="hidden" name="status" value="rejected">
                                        <input type="hidden" name="note" value="Declined by administrator">
                                        <button type="submit" class="bg-red-600 hover:bg-red-500 text-white px-3 py-1.5 rounded text-xs font-semibold shadow-sm">
                                            Reject &amp; Refund
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Active Investment Pools -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-5 border-b border-slate-200 bg-slate-50/50">
            <h2 class="text-base font-bold text-slate-900">All Solaria 40 Trading Pool Contracts</h2>
            <p class="text-xs text-slate-500">Live monitoring of 40-day cycle schedules and daily distribution status</p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-100 text-slate-600 uppercase font-semibold">
                    <tr>
                        <th class="py-3 px-4">Pool ID</th>
                        <th class="py-3 px-4">Investor</th>
                        <th class="py-3 px-4">Units / Capital</th>
                        <th class="py-3 px-4">Daily Yield</th>
                        <th class="py-3 px-4">Schedule Progress</th>
                        <th class="py-3 px-4">Distributed</th>
                        <th class="py-3 px-4">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach ($activePools as $pool):
                        $pct = min(100, round(($pool['days_completed'] / $pool['cycle_total_days']) * 100));
                    ?>
                        <tr class="hover:bg-slate-50/50 transition">
                            <td class="py-3 px-4 font-mono font-bold text-slate-600">#<?= $pool['id'] ?></td>
                            <td class="py-3 px-4 font-semibold text-slate-900"><?= htmlspecialchars($pool['username']) ?></td>
                            <td class="py-3 px-4 font-bold text-slate-800">
                                <?= $pool['units'] ?> unit(s) ($<?= number_format($pool['capital_amount'], 2) ?>)
                            </td>
                            <td class="py-3 px-4 text-blue-600 font-semibold">
                                +$<?= number_format($pool['daily_investor_benefit'], 2) ?> / day
                            </td>
                            <td class="py-3 px-4 min-w-[150px]">
                                <div class="flex justify-between text-[11px] mb-1">
                                    <span>Day <?= $pool['days_completed'] ?> / 40</span>
                                    <span class="font-bold text-blue-600"><?= $pct ?>%</span>
                                </div>
                                <div class="w-full bg-slate-200 rounded-full h-1.5 overflow-hidden">
                                    <div class="bg-blue-600 h-1.5 rounded-full" style="width: <?= $pct ?>%"></div>
                                </div>
                            </td>
                            <td class="py-3 px-4 font-medium text-emerald-700">
                                $<?= number_format($pool['total_benefit_distributed'], 2) ?>
                            </td>
                            <td class="py-3 px-4">
                                <?php if ($pool['status'] === 'active'): ?>
                                    <span class="bg-emerald-100 text-emerald-800 text-[10px] uppercase font-bold px-2 py-0.5 rounded-full">Active</span>
                                <?php else: ?>
                                    <span class="bg-slate-200 text-slate-700 text-[10px] uppercase font-bold px-2 py-0.5 rounded-full">Completed</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
