<?php
// dashboard.php - Main investor control panel
require_once __DIR__ . '/includes/Auth.php';
require_once __DIR__ . '/includes/TradingPool.php';

Auth::requireLogin();
$user = Auth::user();
$userId = $user['id'];
$pdo = Database::getConnection();

$message = null;
$error = null;

// Handle Invest Action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'invest') {
        $units = (int)($_POST['units'] ?? 1);
        $res = TradingPool::createInvestment($userId, $units);
        if ($res['success']) {
            $message = "Successfully subscribed to {$units} Solaria 40 unit(s)!";
            $user = Auth::user(); // reload
        } else {
            $error = $res['error'];
        }
    } elseif ($_POST['action'] === 'deposit') {
        $amount = (float)($_POST['amount'] ?? 0);
        $res = TradingPool::depositFunds($userId, $amount);
        if ($res['success']) {
            $message = "Deposited $" . number_format($amount, 2) . " to your wallet balance.";
            $user = Auth::user();
        } else {
            $error = $res['error'];
        }
    }
}

// Fetch active investments
$stmtInvs = $pdo->prepare("SELECT * FROM investments WHERE user_id = ? ORDER BY id DESC");
$stmtInvs->execute([$userId]);
$investments = $stmtInvs->fetchAll();

// Fetch recent distributions for this user
$stmtDist = $pdo->prepare("SELECT d.*, i.units FROM daily_distributions d JOIN investments i ON d.investment_id = i.id WHERE d.user_id = ? ORDER BY d.id DESC LIMIT 15");
$stmtDist->execute([$userId]);
$recentDistributions = $stmtDist->fetchAll();

// Fetch referrals
$stmtRefs = $pdo->prepare("SELECT id, username, email, created_at, wallet_balance, total_invested FROM users WHERE referred_by_id = ? ORDER BY id DESC");
$stmtRefs->execute([$userId]);
$myReferrals = $stmtRefs->fetchAll();

// Fetch recent transactions
$stmtTx = $pdo->prepare("SELECT * FROM transactions WHERE user_id = ? ORDER BY id DESC LIMIT 10");
$stmtTx->execute([$userId]);
$transactions = $stmtTx->fetchAll();

$pageTitle = 'Investor Dashboard';
require_once __DIR__ . '/includes/header.php';
?>

<div class="max-w-7xl mx-auto px-4 py-8 space-y-8">
    <!-- Header alert messages -->
    <?php if ($message): ?>
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-lg text-sm flex items-center justify-between">
            <div class="flex items-center space-x-2">
                <i class="fa-solid fa-circle-check text-emerald-600"></i>
                <span><?= htmlspecialchars($message) ?></span>
            </div>
            <button onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700">&times;</button>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg text-sm flex items-center justify-between">
            <div class="flex items-center space-x-2">
                <i class="fa-solid fa-triangle-exclamation text-red-600"></i>
                <span><?= htmlspecialchars($error) ?></span>
            </div>
            <button onclick="this.parentElement.remove()" class="text-red-500 hover:text-red-700">&times;</button>
        </div>
    <?php endif; ?>

    <!-- Welcome & Overview Banner -->
    <div class="bg-gradient-to-r from-[#0d1b2a] via-[#1b263b] to-[#1e3d59] rounded-xl p-6 md:p-8 text-white shadow">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div>
                <div class="text-xs uppercase tracking-wider text-blue-300 font-semibold mb-1">Overview Dashboard</div>
                <h1 class="text-2xl md:text-3xl font-extrabold">Welcome, <?= htmlspecialchars($user['username']) ?></h1>
                <p class="text-sm text-slate-300 mt-1">
                    Solaria 40 Trading Pool: $10.00 Base Capital &bull; $0.50 Daily Investor Benefit &bull; $0.25 Referral Income
                </p>
            </div>

            <div class="flex flex-wrap gap-3">
                <button onclick="document.getElementById('invest-modal').classList.remove('hidden')" class="bg-blue-600 hover:bg-blue-500 text-white font-bold px-5 py-2.5 rounded-lg shadow transition flex items-center space-x-2 text-sm">
                    <i class="fa-solid fa-plus-circle"></i>
                    <span>Subscribe to Solaria 40</span>
                </button>
                <button onclick="document.getElementById('deposit-modal').classList.remove('hidden')" class="bg-slate-700 hover:bg-slate-600 text-slate-100 font-semibold px-4 py-2.5 rounded-lg transition text-sm">
                    <i class="fa-solid fa-wallet mr-1.5"></i>Deposit Funds
                </button>
                <a href="/withdraw.php" class="bg-emerald-600 hover:bg-emerald-500 text-white font-semibold px-4 py-2.5 rounded-lg transition text-sm flex items-center space-x-1.5">
                    <i class="fa-solid fa-money-bill-transfer"></i>
                    <span>Daily Withdrawal</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Metrics Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        <div class="bg-white rounded-xl p-5 border border-slate-200 shadow-sm">
            <div class="flex justify-between items-start">
                <span class="text-xs uppercase font-semibold text-slate-500">Available Balance</span>
                <span class="p-2 bg-emerald-50 text-emerald-600 rounded-lg text-sm"><i class="fa-solid fa-coins"></i></span>
            </div>
            <div class="text-2xl font-black text-slate-900 mt-2">$<?= number_format($user['wallet_balance'], 2) ?></div>
            <div class="text-xs text-emerald-600 font-medium mt-1 flex items-center">
                <i class="fa-solid fa-check mr-1 text-[10px]"></i>Full daily liquidity access
            </div>
        </div>

        <div class="bg-white rounded-xl p-5 border border-slate-200 shadow-sm">
            <div class="flex justify-between items-start">
                <span class="text-xs uppercase font-semibold text-slate-500">Active Capital</span>
                <span class="p-2 bg-blue-50 text-blue-600 rounded-lg text-sm"><i class="fa-solid fa-layer-group"></i></span>
            </div>
            <div class="text-2xl font-black text-blue-700 mt-2">$<?= number_format($user['total_invested'], 2) ?></div>
            <div class="text-xs text-slate-500 mt-1">
                <?= count($investments) ?> pool package(s)
            </div>
        </div>

        <div class="bg-white rounded-xl p-5 border border-slate-200 shadow-sm">
            <div class="flex justify-between items-start">
                <span class="text-xs uppercase font-semibold text-slate-500">Total Yield Earned</span>
                <span class="p-2 bg-purple-50 text-purple-600 rounded-lg text-sm"><i class="fa-solid fa-arrow-trend-up"></i></span>
            </div>
            <div class="text-2xl font-black text-purple-700 mt-2">$<?= number_format($user['total_earned'], 2) ?></div>
            <div class="text-xs text-slate-500 mt-1">Benefits &amp; referral rewards</div>
        </div>

        <div class="bg-white rounded-xl p-5 border border-slate-200 shadow-sm">
            <div class="flex justify-between items-start">
                <span class="text-xs uppercase font-semibold text-slate-500">Total Withdrawn</span>
                <span class="p-2 bg-amber-50 text-amber-600 rounded-lg text-sm"><i class="fa-solid fa-hand-holding-dollar"></i></span>
            </div>
            <div class="text-2xl font-black text-slate-900 mt-2">$<?= number_format($user['total_withdrawn'], 2) ?></div>
            <div class="text-xs text-slate-500 mt-1">Processed to external wallets</div>
        </div>
    </div>

    <!-- Active Solaria 40 Pools -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-5 border-b border-slate-200 flex justify-between items-center bg-slate-50/50">
            <div>
                <h2 class="text-lg font-bold text-slate-900">My Solaria 40 Trading Pools</h2>
                <p class="text-xs text-slate-500">40-Day Accelerated Trading Cycles ($0.50/day per $10 unit)</p>
            </div>
            <button onclick="document.getElementById('invest-modal').classList.remove('hidden')" class="text-xs bg-blue-600 text-white font-semibold px-3 py-1.5 rounded hover:bg-blue-500">
                + New Pool
            </button>
        </div>

        <?php if (empty($investments)): ?>
            <div class="p-8 text-center text-slate-500">
                <i class="fa-solid fa-inbox text-4xl text-slate-300 mb-3"></i>
                <p class="font-medium">No active trading pools yet.</p>
                <p class="text-xs text-slate-400 mt-1">Subscribe to your first Solaria 40 unit ($10.00 base capital) to earn $0.50 daily!</p>
                <button onclick="document.getElementById('invest-modal').classList.remove('hidden')" class="mt-4 inline-block bg-blue-600 text-white text-xs font-semibold px-4 py-2 rounded-lg">
                    Subscribe Now
                </button>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-100 text-slate-600 text-xs uppercase font-semibold">
                        <tr>
                            <th class="py-3 px-5">Pool ID</th>
                            <th class="py-3 px-5">Units / Capital</th>
                            <th class="py-3 px-5">Daily Benefit</th>
                            <th class="py-3 px-5">Progression</th>
                            <th class="py-3 px-5">Earned So Far</th>
                            <th class="py-3 px-5">Liquidity Status</th>
                            <th class="py-3 px-5">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php foreach ($investments as $inv):
                            $pct = min(100, round(($inv['days_completed'] / $inv['cycle_total_days']) * 100));
                        ?>
                            <tr class="hover:bg-slate-50/70 transition">
                                <td class="py-3.5 px-5 font-mono text-xs font-semibold text-slate-500">#<?= $inv['id'] ?></td>
                                <td class="py-3.5 px-5">
                                    <div class="font-bold text-slate-900"><?= $inv['units'] ?> Unit<?= $inv['units'] > 1 ? 's' : '' ?></div>
                                    <div class="text-xs text-slate-500">$<?= number_format($inv['capital_amount'], 2) ?> Base Capital</div>
                                </td>
                                <td class="py-3.5 px-5 font-semibold text-blue-600">
                                    +$<?= number_format($inv['daily_investor_benefit'], 2) ?> / day
                                </td>
                                <td class="py-3.5 px-5 min-w-[180px]">
                                    <div class="flex justify-between text-xs font-medium text-slate-600 mb-1">
                                        <span>Day <?= $inv['days_completed'] ?> of <?= $inv['cycle_total_days'] ?></span>
                                        <span class="font-bold text-blue-600"><?= $pct ?>%</span>
                                    </div>
                                    <div class="w-full bg-slate-200 rounded-full h-2 overflow-hidden">
                                        <div class="bg-blue-600 h-2 rounded-full transition-all" style="width: <?= $pct ?>%"></div>
                                    </div>
                                </td>
                                <td class="py-3.5 px-5 font-bold text-emerald-600">
                                    $<?= number_format($inv['total_benefit_distributed'], 2) ?>
                                    <div class="text-[11px] text-slate-400 font-normal">of $<?= number_format($inv['units'] * 20.00, 2) ?> max</div>
                                </td>
                                <td class="py-3.5 px-5">
                                    <span class="badge-liquidity text-xs px-2.5 py-1 rounded-full font-semibold inline-flex items-center">
                                        <i class="fa-solid fa-circle-check mr-1 text-[10px]"></i>Withdrawable daily
                                    </span>
                                </td>
                                <td class="py-3.5 px-5">
                                    <?php if ($inv['status'] === 'active'): ?>
                                        <span class="bg-emerald-100 text-emerald-800 text-xs px-2.5 py-0.5 rounded-full font-semibold">Active</span>
                                    <?php else: ?>
                                        <span class="bg-slate-200 text-slate-700 text-xs px-2.5 py-0.5 rounded-full font-semibold">Completed</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Referral Program Section -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Referral Card -->
        <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-sm">
            <h3 class="text-base font-bold text-slate-900 mb-2 flex items-center">
                <i class="fa-solid fa-user-plus text-purple-600 mr-2"></i>
                Referral Income Program
            </h3>
            <p class="text-xs text-slate-600 mb-4">
                Earn <strong class="text-purple-700">$0.25 daily</strong> for each active $10 Solaria 40 unit subscribed by your direct referrals over their full 40-day cycle!
            </p>

            <div class="bg-slate-50 border border-slate-200 p-3 rounded-lg mb-4">
                <div class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Your Referral Code</div>
                <div class="flex items-center justify-between mt-1">
                    <span class="text-lg font-black tracking-wider text-purple-700 font-mono"><?= htmlspecialchars($user['referral_code']) ?></span>
                </div>
            </div>

            <div>
                <label class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider block mb-1">Your Share Link</label>
                <?php $refUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]/register.php?ref=" . urlencode($user['referral_code']); ?>
                <div class="flex items-center">
                    <input type="text" readonly value="<?= htmlspecialchars($refUrl) ?>" id="refLink" class="w-full text-xs font-mono bg-slate-100 border border-slate-300 rounded-l-lg p-2 focus:outline-none">
                    <button onclick="navigator.clipboard.writeText(document.getElementById('refLink').value); alert('Referral link copied to clipboard!')" class="bg-purple-600 hover:bg-purple-700 text-white text-xs font-semibold px-3 py-2 rounded-r-lg">
                        Copy
                    </button>
                </div>
            </div>

            <div class="mt-4 pt-4 border-t border-slate-100 flex justify-between text-xs">
                <span class="text-slate-500">Total Referrals:</span>
                <span class="font-bold text-slate-800"><?= count($myReferrals) ?> members</span>
            </div>
        </div>

        <!-- Recent Distributions & Ledger -->
        <div class="lg:col-span-2 bg-white rounded-xl border border-slate-200 p-6 shadow-sm">
            <h3 class="text-base font-bold text-slate-900 mb-3 flex items-center justify-between">
                <span>Recent Daily Yield Distributions</span>
                <span class="text-xs font-normal text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200">
                    Withdrawable Daily
                </span>
            </h3>

            <?php if (empty($recentDistributions)): ?>
                <p class="text-xs text-slate-500 py-6 text-center">No distributions credited yet. Daily yields accrue every 24 hours.</p>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 text-slate-500 uppercase font-semibold">
                            <tr>
                                <th class="py-2.5 px-3">Date</th>
                                <th class="py-2.5 px-3">Cycle Day</th>
                                <th class="py-2.5 px-3">Investor Benefit</th>
                                <th class="py-2.5 px-3">Liquidity Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php foreach ($recentDistributions as $dist): ?>
                                <tr>
                                    <td class="py-2.5 px-3 text-slate-600 font-mono"><?= htmlspecialchars($dist['distribution_date']) ?></td>
                                    <td class="py-2.5 px-3 font-bold text-slate-800">Day <?= $dist['day_number'] ?> of 40</td>
                                    <td class="py-2.5 px-3 font-bold text-blue-600">+$<?= number_format($dist['investor_benefit'], 2) ?></td>
                                    <td class="py-2.5 px-3">
                                        <span class="badge-liquidity text-[11px] px-2 py-0.5 rounded-full font-medium inline-block">
                                            <?= htmlspecialchars($dist['liquidity_status']) ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Subscribe Modal -->
<div id="invest-modal" class="hidden fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl max-w-md w-full p-6 shadow-2xl relative">
        <button onclick="document.getElementById('invest-modal').classList.add('hidden')" class="absolute top-4 right-4 text-slate-400 hover:text-slate-600 text-lg">&times;</button>
        <h3 class="text-lg font-bold text-slate-900 mb-1">Subscribe to Solaria 40</h3>
        <p class="text-xs text-slate-500 mb-4">40-day cycle &bull; $10.00 base unit &bull; $0.50 daily yield</p>

        <form method="POST" action="dashboard.php" class="space-y-4">
            <input type="hidden" name="action" value="invest">
            <div>
                <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Number of Units ($10 each)</label>
                <div class="flex items-center space-x-2">
                    <input type="number" id="units-input" name="units" min="1" max="1000" value="1" required class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm font-semibold" oninput="updateCalculation()">
                </div>
                <div class="text-[11px] text-slate-400 mt-1">Available balance: <strong class="text-emerald-600">$<?= number_format($user['wallet_balance'], 2) ?></strong></div>
            </div>

            <div class="bg-slate-50 rounded-lg p-3 border border-slate-200 text-xs space-y-1.5">
                <div class="flex justify-between">
                    <span class="text-slate-500">Capital Investment:</span>
                    <span class="font-bold text-slate-800" id="calc-cost">$10.00</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500">Daily Investor Benefit:</span>
                    <span class="font-bold text-blue-600" id="calc-daily">+$0.50 / day</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500">Cycle Duration:</span>
                    <span class="font-semibold text-slate-800">40 Days</span>
                </div>
                <div class="flex justify-between pt-1 border-t border-slate-200">
                    <span class="text-slate-700 font-semibold">Total Projected Return:</span>
                    <span class="font-black text-emerald-600" id="calc-total">$20.00 (200%)</span>
                </div>
            </div>

            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-500 text-white font-bold py-2.5 rounded-lg text-sm shadow transition">
                Confirm Subscription
            </button>
        </form>
    </div>
</div>

<!-- Deposit Modal -->
<div id="deposit-modal" class="hidden fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl max-w-sm w-full p-6 shadow-2xl relative">
        <button onclick="document.getElementById('deposit-modal').classList.add('hidden')" class="absolute top-4 right-4 text-slate-400 hover:text-slate-600 text-lg">&times;</button>
        <h3 class="text-lg font-bold text-slate-900 mb-1">Deposit Funds</h3>
        <p class="text-xs text-slate-500 mb-4">Instant sandbox demo deposit</p>

        <form method="POST" action="dashboard.php" class="space-y-4">
            <input type="hidden" name="action" value="deposit">
            <div>
                <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Amount ($ USD)</label>
                <input type="number" step="0.01" min="5" name="amount" value="50.00" required class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm font-semibold">
            </div>

            <button type="submit" class="w-full bg-slate-800 hover:bg-slate-700 text-white font-bold py-2.5 rounded-lg text-sm shadow transition">
                Add Funds Now
            </button>
        </form>
    </div>
</div>

<script>
function updateCalculation() {
    const units = parseInt(document.getElementById('units-input').value) || 0;
    const cost = units * 10;
    const daily = units * 0.5;
    const total = units * 20;

    document.getElementById('calc-cost').innerText = '$' + cost.toFixed(2);
    document.getElementById('calc-daily').innerText = '+$' + daily.toFixed(2) + ' / day';
    document.getElementById('calc-total').innerText = '$' + total.toFixed(2) + ' (200%)';
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
