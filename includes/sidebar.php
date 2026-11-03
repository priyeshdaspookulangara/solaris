<?php
// includes/sidebar.php
require_once __DIR__ . '/Auth.php';
$sidebarUser = Auth::user();
$currentScript = basename($_SERVER['PHP_SELF']);
?>
<aside class="w-64 bg-[#0c192e] text-slate-300 flex-shrink-0 flex flex-col border-r border-slate-800 min-h-screen">
    <!-- Brand / Logo Area -->
    <div class="h-16 flex items-center px-6 border-b border-slate-800 bg-[#091322]">
        <a href="/index.php" class="flex items-center space-x-2.5">
            <div class="w-8 h-8 rounded-lg bg-blue-600 flex items-center justify-center font-bold text-lg text-white shadow-inner">
                <i class="fa-solid fa-chart-line"></i>
            </div>
            <div class="flex flex-col">
                <span class="text-lg font-bold tracking-wider text-white leading-none">SOLARIS</span>
                <span class="text-[10px] text-blue-400 font-semibold tracking-widest uppercase">SOLARIA 40</span>
            </div>
        </a>
    </div>

    <!-- User Profile Card -->
    <div class="p-4 mx-3 my-4 bg-slate-800/60 rounded-xl border border-slate-700/60">
        <div class="flex items-center space-x-3 mb-3">
            <div class="w-10 h-10 rounded-full bg-blue-600/30 border border-blue-500/50 flex items-center justify-center text-blue-400 font-bold text-sm">
                <i class="fa-solid fa-user"></i>
            </div>
            <div class="overflow-hidden">
                <div class="text-sm font-semibold text-white truncate"><?= htmlspecialchars($sidebarUser['username'] ?? 'User') ?></div>
                <div class="text-xs text-slate-400 capitalize flex items-center gap-1">
                    <span class="w-2 h-2 rounded-full <?= ($sidebarUser['role'] ?? '') === 'admin' ? 'bg-amber-400' : 'bg-emerald-400' ?>"></span>
                    <?= htmlspecialchars($sidebarUser['role'] ?? 'investor') ?>
                </div>
            </div>
        </div>
        <div class="bg-[#091322]/80 p-2.5 rounded-lg border border-slate-800">
            <div class="text-[11px] uppercase tracking-wider text-slate-400 font-semibold">Available Liquidity</div>
            <div class="text-lg font-bold text-emerald-400 font-mono">
                $<?= number_format($sidebarUser['wallet_balance'] ?? 0, 2) ?>
            </div>
        </div>
    </div>

    <!-- Navigation Links -->
    <div class="flex-grow px-3 space-y-1">
        <div class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider px-3 mb-2">Main Menu</div>

        <a href="/dashboard.php" class="flex items-center space-x-3 px-3 py-2.5 rounded-lg text-sm font-medium transition <?= $currentScript === 'dashboard.php' ? 'bg-blue-600 text-white font-semibold shadow' : 'text-slate-300 hover:bg-slate-800 hover:text-white' ?>">
            <i class="fa-solid fa-gauge-high w-5 text-center"></i>
            <span>Dashboard</span>
        </a>

        <a href="/withdraw.php" class="flex items-center space-x-3 px-3 py-2.5 rounded-lg text-sm font-medium transition <?= $currentScript === 'withdraw.php' ? 'bg-blue-600 text-white font-semibold shadow' : 'text-slate-300 hover:bg-slate-800 hover:text-white' ?>">
            <i class="fa-solid fa-money-bill-transfer w-5 text-center"></i>
            <span>Withdraw Daily</span>
        </a>

        <a href="/reports.php" class="flex items-center space-x-3 px-3 py-2.5 rounded-lg text-sm font-medium transition <?= $currentScript === 'reports.php' ? 'bg-blue-600 text-white font-semibold shadow' : 'text-slate-300 hover:bg-slate-800 hover:text-white' ?>">
            <i class="fa-solid fa-file-invoice-dollar w-5 text-center"></i>
            <span>Financial Reports</span>
        </a>

        <a href="/index.php" class="flex items-center space-x-3 px-3 py-2.5 rounded-lg text-sm font-medium transition <?= $currentScript === 'index.php' ? 'bg-blue-600 text-white font-semibold shadow' : 'text-slate-300 hover:bg-slate-800 hover:text-white' ?>">
            <i class="fa-solid fa-calendar-days w-5 text-center"></i>
            <span>Solaria 40 Schedule</span>
        </a>

        <?php if (($sidebarUser['role'] ?? '') === 'admin'): ?>
            <div class="pt-4 pb-1 text-[11px] font-semibold text-amber-400/90 uppercase tracking-wider px-3">Administration</div>
            <a href="/admin.php" class="flex items-center space-x-3 px-3 py-2.5 rounded-lg text-sm font-medium transition <?= $currentScript === 'admin.php' ? 'bg-amber-600 text-white font-semibold shadow' : 'text-amber-300 hover:bg-amber-950/40 hover:text-amber-200' ?>">
                <i class="fa-solid fa-shield-halved w-5 text-center"></i>
                <span>Pool Control Center</span>
            </a>
            <a href="/admin_reports.php" class="flex items-center space-x-3 px-3 py-2.5 rounded-lg text-sm font-medium transition <?= $currentScript === 'admin_reports.php' ? 'bg-amber-600 text-white font-semibold shadow' : 'text-amber-300 hover:bg-amber-950/40 hover:text-amber-200' ?>">
                <i class="fa-solid fa-chart-pie w-5 text-center"></i>
                <span>Master Audit & Reports</span>
            </a>
        <?php endif; ?>
    </div>

    <!-- Quick Referral Box -->
    <div class="p-3 mx-3 my-3 bg-blue-950/30 border border-blue-800/40 rounded-lg text-xs">
        <div class="text-blue-300 font-semibold mb-1 flex items-center">
            <i class="fa-solid fa-gift mr-1.5"></i> Daily Referral
        </div>
        <p class="text-slate-400 text-[11px] leading-relaxed">
            Earn <strong class="text-slate-200">$0.25/day</strong> for every $10 reference unit sponsored.
        </p>
    </div>

    <!-- Bottom Actions -->
    <div class="p-4 border-t border-slate-800 bg-[#091322]">
        <a href="/logout.php" class="w-full flex items-center justify-center space-x-2 py-2 px-3 bg-red-600/20 hover:bg-red-600 text-red-300 hover:text-white border border-red-500/30 rounded-lg text-sm font-medium transition">
            <i class="fa-solid fa-arrow-right-from-bracket"></i>
            <span>Sign Out</span>
        </a>
    </div>
</aside>
