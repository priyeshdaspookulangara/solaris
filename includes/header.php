<?php
// includes/header.php
require_once __DIR__ . '/Auth.php';
$currentUser = Auth::user();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) . ' - ' : '' ?>Solaris | Solaria 40</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .solaria-navy {
            background-color: #0b1a30;
        }
        .solaria-blue {
            color: #1e3a8a;
        }
        .badge-liquidity {
            background-color: #ecfdf5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 min-h-screen flex flex-col font-sans">
    <!-- Navbar -->
    <nav class="bg-[#0f1f38] text-white border-b border-slate-700 shadow-md">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16 items-center">
                <div class="flex items-center space-x-3">
                    <a href="/index.php" class="flex items-center space-x-2">
                        <div class="w-9 h-9 rounded-lg bg-blue-600 flex items-center justify-center font-bold text-xl text-white shadow-inner">
                            <i class="fa-solid fa-chart-line"></i>
                        </div>
                        <span class="text-xl font-bold tracking-wider text-white">SOLARIS</span>
                        <span class="text-xs bg-blue-500/20 text-blue-300 font-semibold px-2 py-0.5 rounded border border-blue-400/30">SOLARIA 40</span>
                    </a>
                </div>

                <div class="flex items-center space-x-4">
                    <a href="/index.php" class="text-sm font-medium text-slate-300 hover:text-white transition">Schedule</a>
                    <?php if ($currentUser): ?>
                        <a href="/dashboard.php" class="text-sm font-medium text-slate-300 hover:text-white transition">Dashboard</a>
                        <a href="/withdraw.php" class="text-sm font-medium text-slate-300 hover:text-white transition">Withdraw</a>
                        <?php if ($currentUser['role'] === 'admin'): ?>
                            <a href="/admin.php" class="text-sm font-medium text-amber-300 hover:text-amber-200 transition font-semibold">
                                <i class="fa-solid fa-shield-halved mr-1"></i>Admin Pool
                            </a>
                        <?php endif; ?>

                        <div class="flex items-center pl-4 border-l border-slate-700 space-x-3">
                            <div class="text-right">
                                <div class="text-xs text-slate-400">Balance</div>
                                <div class="text-sm font-bold text-emerald-400">$<?= number_format($currentUser['wallet_balance'], 2) ?></div>
                            </div>
                            <span class="text-sm font-medium text-slate-200 hidden sm:inline"><?= htmlspecialchars($currentUser['username']) ?></span>
                            <a href="/logout.php" class="text-sm bg-red-600/80 hover:bg-red-600 px-3 py-1.5 rounded text-white transition">
                                <i class="fa-solid fa-right-from-bracket"></i>
                            </a>
                        </div>
                    <?php else: ?>
                        <a href="/login.php" class="text-sm font-medium text-slate-300 hover:text-white transition">Login</a>
                        <a href="/register.php" class="text-sm bg-blue-600 hover:bg-blue-500 text-white font-semibold px-4 py-2 rounded-lg transition shadow">Join Solaria 40</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>
    <main class="flex-grow">
