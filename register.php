<?php
// register.php
require_once __DIR__ . '/includes/Auth.php';

if (Auth::check()) {
    header('Location: /dashboard.php');
    exit;
}

$refFromQuery = $_GET['ref'] ?? '';
$error = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $refCode = $_POST['referral_code'] ?? '';

    $res = Auth::register($username, $email, $password, $refCode);
    if ($res['success']) {
        Auth::login($username, $password);
        header('Location: /dashboard.php');
        exit;
    } else {
        $error = $res['error'];
    }
}

$pageTitle = 'Join Solaria 40';
require_once __DIR__ . '/includes/header.php';
?>

<div class="max-w-md mx-auto my-12 px-4">
    <div class="bg-white rounded-xl shadow-md border border-slate-200 p-8">
        <div class="text-center mb-6">
            <h1 class="text-2xl font-bold text-slate-900">Create Solaris Account</h1>
            <p class="text-xs text-slate-500 mt-1">Get instant access to Solaria 40 Daily Liquidity Trading Pool</p>
            <div class="mt-2 inline-block bg-emerald-50 text-emerald-700 text-xs px-2.5 py-1 rounded-full font-semibold border border-emerald-200">
                ✨ $20.00 Welcome Bonus included
            </div>
        </div>

        <?php if ($error): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 text-sm p-3 rounded-lg mb-4">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="register.php" class="space-y-4">
            <div>
                <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Username</label>
                <input type="text" name="username" required value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Choose a username">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Email Address</label>
                <input type="email" name="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="your@email.com">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Password</label>
                <input type="password" name="password" required class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Minimum 6 characters">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Referral Code (Optional)</label>
                <input type="text" name="referral_code" value="<?= htmlspecialchars($_POST['referral_code'] ?? $refFromQuery) ?>" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="e.g. ALEX40">
            </div>

            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-500 text-white font-semibold py-2.5 rounded-lg text-sm shadow transition">
                Create Account &amp; Claim $20 Bonus
            </button>
        </form>

        <div class="mt-6 pt-4 border-t border-slate-100 text-center text-xs text-slate-500">
            Already have an account? <a href="/login.php" class="text-blue-600 font-semibold hover:underline">Sign In</a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
