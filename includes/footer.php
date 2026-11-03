<?php
// includes/footer.php
require_once __DIR__ . '/Auth.php';
$isSidebarLayout = isset($useSidebar) && $useSidebar && Auth::user();
?>
    </main>
<?php if ($isSidebarLayout): ?>
        <footer class="bg-white text-slate-500 py-4 px-6 border-t border-slate-200 text-xs flex justify-between items-center">
            <div>
                <strong>SOLARIA 40</strong> &bull; 40-Day Accelerated Trading Pool ($0.50 Daily Investor Benefit)
            </div>
            <div>&copy; <?= date('Y') ?> Solaris Financial Tech</div>
        </footer>
    </div> <!-- Close flex-1 content wrapper -->
<?php else: ?>
    <footer class="bg-slate-900 text-slate-400 py-8 border-t border-slate-800 text-sm mt-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center space-y-2">
            <p class="font-medium text-slate-300">SOLARIA 40 &bull; 40-Day Accelerated Trading Pool & Daily Liquidity Schedule</p>
            <p class="text-xs text-slate-500">Structured for rapid capital growth with a $10.00 base reference capital, providing a $0.50 daily Investor Benefit and $0.25 daily Referral Income over a 40-day cycle with full daily withdrawal access.</p>
            <div class="text-xs text-slate-600 pt-2">&copy; <?= date('Y') ?> Solaris Financial Technology. All rights reserved.</div>
        </div>
    </footer>
<?php endif; ?>
</body>
</html>
