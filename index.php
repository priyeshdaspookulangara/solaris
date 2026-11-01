<?php
// index.php - Solaria 40 Presentation & Day-by-Day Schedule
$pageTitle = 'Solaria 40 - Accelerated Trading Pool';
require_once __DIR__ . '/includes/header.php';
?>

<div class="max-w-5xl mx-auto px-4 py-8">
    <!-- Header Banner matching the attached design -->
    <div class="rounded-xl overflow-hidden shadow-lg border border-blue-950 bg-gradient-to-r from-[#0d1b2a] via-[#1b263b] to-[#1e3d59] p-8 text-white relative mb-8">
        <div class="inline-block bg-blue-500/20 text-blue-300 text-xs px-2.5 py-1 rounded-full uppercase tracking-wider font-semibold border border-blue-400/30 mb-3">
            Accelerated Trading Pool Program
        </div>
        <h1 class="text-4xl md:text-5xl font-extrabold tracking-tight">SOLARIA 40</h1>
        <p class="text-lg md:text-xl text-blue-200 mt-2 font-medium">
            40-Day Accelerated Trading Pool &amp; Daily Liquidity Schedule ($0.50 Investor Benefit)
        </p>

        <div class="mt-6 flex flex-wrap gap-4 items-center">
            <a href="/register.php" class="bg-blue-600 hover:bg-blue-500 text-white font-bold px-6 py-2.5 rounded-lg shadow-md transition flex items-center space-x-2">
                <span>Join Pool Now</span>
                <i class="fa-solid fa-arrow-right text-xs"></i>
            </a>
            <a href="#schedule" class="bg-slate-800/80 hover:bg-slate-800 text-slate-200 font-medium px-5 py-2.5 rounded-lg border border-slate-700 transition">
                View 40-Day Schedule
            </a>
            <div class="ml-auto text-right text-xs text-blue-200 hidden sm:block">
                Base Unit: <strong class="text-white text-sm">$10.00</strong> | Daily ROI: <strong class="text-emerald-400 text-sm">5.0%</strong>
            </div>
        </div>
    </div>

    <!-- Section 1. Executive Summary & Core Parameters matching design -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 md:p-8 mb-8">
        <div class="border-b-2 border-blue-600 pb-3 mb-6">
            <h2 class="text-2xl font-bold text-slate-900 tracking-tight flex items-center">
                <span class="w-2.5 h-6 bg-blue-600 rounded-sm inline-block mr-3"></span>
                1. Executive Summary &amp; Core Parameters
            </h2>
        </div>

        <p class="text-slate-700 text-base md:text-lg leading-relaxed mb-6 font-normal">
            <strong>Solaria 40</strong> is structured for rapid capital growth with a <strong>$10.00</strong> base reference capital, providing a <strong>$0.50</strong> daily Investor Benefit and <strong>$0.25</strong> daily Referral Income over a <strong>40-day cycle</strong> with full daily withdrawal access.
        </p>

        <!-- Key Parameter Grid -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 pt-2">
            <div class="bg-slate-50 border border-slate-200 rounded-lg p-4 text-center">
                <div class="text-xs uppercase tracking-wider text-slate-500 font-semibold">Base Capital</div>
                <div class="text-2xl font-black text-slate-900 mt-1">$10.00</div>
                <div class="text-xs text-slate-500 mt-1">Per reference unit</div>
            </div>

            <div class="bg-blue-50/70 border border-blue-200 rounded-lg p-4 text-center">
                <div class="text-xs uppercase tracking-wider text-blue-600 font-semibold">Investor Benefit</div>
                <div class="text-2xl font-black text-blue-700 mt-1">$0.50 / day</div>
                <div class="text-xs text-emerald-600 font-semibold mt-1">Withdrawable daily</div>
            </div>

            <div class="bg-purple-50/70 border border-purple-200 rounded-lg p-4 text-center">
                <div class="text-xs uppercase tracking-wider text-purple-600 font-semibold">Referral Income</div>
                <div class="text-2xl font-black text-purple-700 mt-1">$0.25 / day</div>
                <div class="text-xs text-slate-500 mt-1">Direct sponsor bonus</div>
            </div>

            <div class="bg-emerald-50/70 border border-emerald-200 rounded-lg p-4 text-center">
                <div class="text-xs uppercase tracking-wider text-emerald-600 font-semibold">Total Yield (40d)</div>
                <div class="text-2xl font-black text-emerald-700 mt-1">$20.00</div>
                <div class="text-xs text-emerald-600 font-semibold mt-1">200% Capital Return</div>
            </div>
        </div>
    </div>

    <!-- Section 2. Day-by-Day Progression Schedule matching design -->
    <div id="schedule" class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden mb-8">
        <div class="p-6 md:p-8 border-b border-slate-200">
            <div class="border-b-2 border-blue-600 pb-3 mb-4">
                <h2 class="text-2xl font-bold text-slate-900 tracking-tight flex items-center">
                    <span class="w-2.5 h-6 bg-blue-600 rounded-sm inline-block mr-3"></span>
                    2. Day-by-Day Progression Schedule (Days 1 &ndash; 40)
                </h2>
            </div>
            <p class="text-sm text-slate-600">
                Transparent liquidity distribution schedule. Every 24 hours, investor rewards and sponsor referral incomes are unlocked for immediate withdrawal or reinvestment.
            </p>

            <div class="flex items-center space-x-2 mt-4 text-xs font-semibold">
                <span class="text-slate-500">Filter Schedule:</span>
                <button type="button" onclick="showRange(1, 20)" id="btn-part1" class="px-3 py-1 bg-blue-600 text-white rounded shadow-sm hover:bg-blue-700">Days 1 &ndash; 20</button>
                <button type="button" onclick="showRange(21, 40)" id="btn-part2" class="px-3 py-1 bg-slate-100 text-slate-700 rounded border border-slate-300 hover:bg-slate-200">Days 21 &ndash; 40</button>
                <button type="button" onclick="showRange(1, 40)" id="btn-all" class="px-3 py-1 bg-slate-100 text-slate-700 rounded border border-slate-300 hover:bg-slate-200">Full 40 Days</button>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm" id="schedule-table">
                <thead class="bg-slate-100 text-slate-700 font-semibold text-xs uppercase tracking-wider border-b border-slate-200">
                    <tr>
                        <th class="py-3.5 px-6">Day</th>
                        <th class="py-3.5 px-6">Investor Benefit</th>
                        <th class="py-3.5 px-6">Referral Income</th>
                        <th class="py-3.5 px-6">Cumulative Benefit</th>
                        <th class="py-3.5 px-6">Liquidity Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php for ($day = 1; $day <= 40; $day++): ?>
                        <tr class="schedule-row <?= $day % 2 === 0 ? 'bg-slate-50/50' : 'bg-white' ?> hover:bg-blue-50/30 transition" data-day="<?= $day ?>">
                            <td class="py-3 px-6 font-bold text-slate-900">
                                Day <?= $day ?>
                            </td>
                            <td class="py-3 px-6 font-semibold text-blue-600">
                                $0.50
                            </td>
                            <td class="py-3 px-6 text-purple-600 font-medium">
                                $0.25
                            </td>
                            <td class="py-3 px-6 text-slate-700 font-medium">
                                $<?= number_format($day * 0.50, 2) ?>
                            </td>
                            <td class="py-3 px-6">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold badge-liquidity">
                                    <i class="fa-solid fa-circle-check mr-1.5 text-emerald-500 text-[10px]"></i>
                                    Withdrawable daily
                                </span>
                            </td>
                        </tr>
                    <?php endfor; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function showRange(start, end) {
    document.querySelectorAll('.schedule-row').forEach(row => {
        const d = parseInt(row.getAttribute('data-day'));
        if (d >= start && d <= end) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });

    ['btn-part1', 'btn-part2', 'btn-all'].forEach(id => {
        const el = document.getElementById(id);
        el.className = 'px-3 py-1 bg-slate-100 text-slate-700 rounded border border-slate-300 hover:bg-slate-200';
    });
    if (start === 1 && end === 20) {
        document.getElementById('btn-part1').className = 'px-3 py-1 bg-blue-600 text-white rounded shadow-sm hover:bg-blue-700';
    } else if (start === 21 && end === 40) {
        document.getElementById('btn-part2').className = 'px-3 py-1 bg-blue-600 text-white rounded shadow-sm hover:bg-blue-700';
    } else {
        document.getElementById('btn-all').className = 'px-3 py-1 bg-blue-600 text-white rounded shadow-sm hover:bg-blue-700';
    }
}
// Default to Days 1-20 to match the exact view in the attached image
showRange(1, 20);
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
