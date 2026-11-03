<?php
// withdraw.php - Daily Liquidity Withdrawal page
require_once __DIR__ . '/includes/Auth.php';
require_once __DIR__ . '/includes/TradingPool.php';

Auth::requireLogin();
$user = Auth::user();
$userId = $user['id'];
$pdo = Database::getConnection();

$message = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = (float)($_POST['amount'] ?? 0);
    $address = trim($_POST['address'] ?? '');
    $method = trim($_POST['payment_method'] ?? 'USDT-TRC20');

    $res = TradingPool::requestWithdrawal($userId, $amount, $address, $method);
    if ($res['success']) {
        $message = "Withdrawal request of $" . number_format($amount, 2) . " submitted successfully.";
        $user = Auth::user(); // refresh balance
    } else {
        $error = $res['error'];
    }
}

// Fetch user withdrawals
$stmtW = $pdo->prepare("SELECT * FROM withdrawals WHERE user_id = ? ORDER BY id DESC");
$stmtW->execute([$userId]);
$withdrawals = $stmtW->fetchAll();

$pageTitle = 'Daily Liquidity Withdrawal';
$useSidebar = true;
require_once __DIR__ . '/includes/header.php';
?>

<div class="max-w-5xl mx-auto space-y-6">
    <div class="border-b-2 border-blue-600 pb-3">
        <h1 class="text-2xl font-bold text-slate-900 flex items-center">
            <span class="w-2.5 h-6 bg-blue-600 rounded-sm inline-block mr-3"></span>
            Daily Liquidity Withdrawal Access
        </h1>
        <p class="text-sm text-slate-500 mt-1">
            As outlined in the Solaria 40 Schedule, investor benefits and referral earnings are fully liquid and withdrawable on a daily basis.
        </p>
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

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Withdrawal Form -->
        <div class="md:col-span-1 bg-white p-6 rounded-xl border border-slate-200 shadow-sm space-y-4">
            <div class="bg-blue-50/60 p-4 rounded-lg border border-blue-200">
                <div class="text-xs uppercase text-slate-500 font-semibold">Available for Withdrawal</div>
                <div class="text-2xl font-black text-slate-900 mt-1">$<?= number_format($user['wallet_balance'], 2) ?></div>
                <div class="text-[11px] text-emerald-600 font-semibold mt-1">Instant Daily Liquidity</div>
            </div>

            <form method="POST" action="withdraw.php" class="space-y-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Network / Method</label>
                    <select name="payment_method" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm bg-white">
                        <option value="USDT-TRC20">USDT (TRC-20)</option>
                        <option value="USDT-ERC20">USDT (ERC-20)</option>
                        <option value="USDC">USDC (Polygon)</option>
                        <option value="BITCOIN">Bitcoin (BTC)</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Destination Address</label>
                    <input type="text" name="address" required placeholder="e.g. T..." class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm font-mono focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <div class="flex justify-between items-center mb-1">
                        <label class="text-xs font-semibold text-slate-600 uppercase">Amount ($ USD)</label>
                        <button type="button" onclick="document.getElementById('withdraw-amount').value = <?= (float)$user['wallet_balance'] ?>" class="text-[11px] text-blue-600 font-semibold hover:underline">Max</button>
                    </div>
                    <input type="number" step="0.01" min="1.00" max="<?= (float)$user['wallet_balance'] ?>" name="amount" id="withdraw-amount" required placeholder="Min $1.00" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm font-semibold">
                </div>

                <button type="submit" <?= $user['wallet_balance'] < 1 ? 'disabled' : '' ?> class="w-full bg-emerald-600 hover:bg-emerald-500 disabled:bg-slate-300 text-white font-bold py-2.5 rounded-lg text-sm shadow transition">
                    Request Withdrawal
                </button>
            </form>
        </div>

        <!-- History Table -->
        <div class="md:col-span-2 bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
            <h3 class="text-base font-bold text-slate-900 mb-3">Withdrawal History</h3>
            <?php if (empty($withdrawals)): ?>
                <div class="text-center py-10 text-slate-400 text-xs">
                    <i class="fa-solid fa-receipt text-3xl mb-2 text-slate-300"></i>
                    <p>No withdrawal requests submitted yet.</p>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 text-slate-500 uppercase font-semibold">
                            <tr>
                                <th class="py-2.5 px-3">Date</th>
                                <th class="py-2.5 px-3">Amount</th>
                                <th class="py-2.5 px-3">Method / Address</th>
                                <th class="py-2.5 px-3">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php foreach ($withdrawals as $w): ?>
                                <tr>
                                    <td class="py-3 px-3 text-slate-500 font-mono"><?= htmlspecialchars(substr($w['created_at'], 0, 16)) ?></td>
                                    <td class="py-3 px-3 font-bold text-slate-900">$<?= number_format($w['amount'], 2) ?></td>
                                    <td class="py-3 px-3">
                                        <div class="font-medium text-slate-700"><?= htmlspecialchars($w['payment_method']) ?></div>
                                        <div class="font-mono text-[10px] text-slate-400 truncate max-w-[150px]"><?= htmlspecialchars($w['destination_address']) ?></div>
                                    </td>
                                    <td class="py-3 px-3">
                                        <?php if ($w['status'] === 'approved'): ?>
                                            <span class="bg-emerald-100 text-emerald-800 text-[11px] font-semibold px-2 py-0.5 rounded-full">Approved</span>
                                        <?php elseif ($w['status'] === 'rejected'): ?>
                                            <span class="bg-red-100 text-red-800 text-[11px] font-semibold px-2 py-0.5 rounded-full">Rejected</span>
                                        <?php else: ?>
                                            <span class="bg-amber-100 text-amber-800 text-[11px] font-semibold px-2 py-0.5 rounded-full">Pending Admin</span>
                                        <?php endif; ?>
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

<?php require_once __DIR__ . '/includes/footer.php'; ?>
