<?php
// login.php
require_once __DIR__ . '/includes/Auth.php';

if (Auth::check()) {
    header('Location: /dashboard.php');
    exit;
}

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usernameOrEmail = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    $res = Auth::login($usernameOrEmail, $password);
    if ($res['success']) {
        if ($res['user']['role'] === 'admin') {
            header('Location: /admin.php');
        } else {
            header('Location: /dashboard.php');
        }
        exit;
    } else {
        $error = $res['error'];
    }
}

$pageTitle = 'Sign In';
require_once __DIR__ . '/includes/header.php';
?>

<div class="max-w-md mx-auto my-12 px-4">
    <div class="bg-white rounded-xl shadow-md border border-slate-200 p-8">
        <div class="text-center mb-6">
            <h1 class="text-2xl font-bold text-slate-900">Sign In to Solaris</h1>
            <p class="text-xs text-slate-500 mt-1">Access your Solaria 40 Trading Pool &amp; Liquidity Dashboard</p>
        </div>

        <?php if ($error): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 text-sm p-3 rounded-lg mb-4">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="login.php" class="space-y-4">
            <div>
                <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Username or Email</label>
                <input type="text" name="username" required value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="e.g. sarah_trader or sarah@example.com">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Password</label>
                <input type="password" name="password" required class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="••••••••">
            </div>

            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-500 text-white font-semibold py-2.5 rounded-lg text-sm shadow transition">
                Sign In
            </button>
        </form>

        <div class="mt-6 pt-4 border-t border-slate-100 text-center text-xs text-slate-500">
            Don't have an account? <a href="/register.php" class="text-blue-600 font-semibold hover:underline">Register</a>
            <div class="mt-3 p-2 bg-slate-50 rounded text-[11px] text-slate-400">
                Demo Accounts:<br>
                Admin: <code class="text-slate-600">admin / Admin123!</code><br>
                Demo Investor: <code class="text-slate-600">sarah_trader / User123!</code><br>
                Demo Sponsor: <code class="text-slate-600">alex_investor / User123!</code>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
