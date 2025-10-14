<?php
session_start();
require_once '../../src/templates/admin_header.php';
require_once '../../src/includes/db_connect.php';

// Security check
if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'staff') {
    exit('Access Denied');
}

// --- Fetching Live Data for Dashboard Widgets ---

// 1. Total Packages Sold (from purchase_history where it's a code generation)
$total_packages_sold_result = $mysqli->query("SELECT COUNT(id) AS count FROM purchase_history");
$total_packages_sold = $total_packages_sold_result->fetch_assoc()['count'];

// 2. Total Points Generated (from binary_points table)
$total_points_generated_result = $mysqli->query("SELECT SUM(points) AS total FROM binary_points");
$total_points_generated = $total_points_generated_result->fetch_assoc()['total'] ?? 0;

// 3. Total Commissions Paid (All Types)
$comm_all_result = $mysqli->query("SELECT SUM(amount) AS total FROM commissions");
$total_commissions_paid = (float)($comm_all_result->fetch_assoc()['total'] ?? 0);

// 4. Breakdown of Commissions by Type
$comm_binary_result = $mysqli->query("SELECT SUM(amount) AS total FROM commissions WHERE type = 'binary_pair'");
$total_binary_comm = (float)($comm_binary_result->fetch_assoc()['total'] ?? 0);

$comm_leadership_result = $mysqli->query("SELECT SUM(amount) AS total FROM commissions WHERE type LIKE 'leadership_l%'");
$total_leadership_comm = (float)($comm_leadership_result->fetch_assoc()['total'] ?? 0);

$comm_unilevel_result = $mysqli->query("SELECT SUM(amount) AS total FROM commissions WHERE type = 'unilevel_bonus'");
$total_unilevel_comm = (float)($comm_unilevel_result->fetch_assoc()['total'] ?? 0);

// 5. Total Retention (Company Gain from Flush-Out)
// To calculate this, we need to know the total points processed vs. points that resulted in a pair
// This is a complex calculation, so for now, we'll create a placeholder.
// A better way would be to log flushed points to a new table in the commission script.
// Simplified placeholder logic:
    $retention_result = $mysqli->query("SELECT SUM(amount) AS total FROM company_gains WHERE type = 'flush_out'");
    $total_retention = (float)($retention_result->fetch_assoc()['total'] ?? 0);

// 6. Net Company Fund
// This is Total Revenue (from paid accounts) minus Total Payouts.
$total_revenue_result = $mysqli->query("
    SELECT SUM(ph.price_paid) AS total 
    FROM purchase_history ph
    WHERE ph.account_type = 'Paid Account' OR ph.account_type = 'CD Account'
");
$total_revenue = (float)($total_revenue_result->fetch_assoc()['total'] ?? 0);

$net_company_fund = $total_revenue - $total_commissions_paid;

?>
<style>
    .kpi-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; }
    .kpi-card { background: #fff; padding: 25px; border-radius: 8px; box-shadow: var(--shadow); border: 1px solid var(--border-color); }
    .kpi-card h3 { margin: 0 0 10px 0; font-size: 16px; color: #777; font-weight: 500; }
    .kpi-card .kpi-value { font-size: 32px; font-weight: 700; color: var(--text-color); }
    .kpi-card .kpi-subvalue { font-size: 14px; margin-top: 15px; padding-top: 10px; border-top: 1px solid #eee; }
    .kpi-card .kpi-subvalue div { display: flex; justify-content: space-between; margin-bottom: 5px; }
</style>

<div class="content-header">
    <h2>Admin Dashboard</h2>
    <p>A live overview of your MLM system's performance.</p>
</div>

<div class="kpi-grid">
    <!-- Total Revenue Card -->
    <div class="kpi-card" style="background-color: var(--primary-color-light);">
        <h3>Net Company Fund</h3>
        <p class="kpi-value">₱<?php echo number_format($net_company_fund, 2); ?></p>
        <div class="kpi-subvalue">
            <div><span>Total Revenue:</span> <span>₱<?php echo number_format($total_revenue, 2); ?></span></div>
            <div><span>Total Payouts:</span> <span>- ₱<?php echo number_format($total_commissions_paid, 2); ?></span></div>
        </div>
    </div>
    
    <!-- Commissions Card -->
    <div class="kpi-card">
        <h3>Total Commissions Paid</h3>
        <p class="kpi-value">₱<?php echo number_format($total_commissions_paid, 2); ?></p>
         <div class="kpi-subvalue">
            <div><span>Binary Pairs:</span> <span>₱<?php echo number_format($total_binary_comm, 2); ?></span></div>
            <div><span>Leadership Bonus:</span> <span>₱<?php echo number_format($total_leadership_comm, 2); ?></span></div>
            <div><span>Unilevel Bonus:</span> <span>₱<?php echo number_format($total_unilevel_comm, 2); ?></span></div>
        </div>
    </div>
    
    <!-- Activity Card -->
    <div class="kpi-card">
        <h3>Network Activity</h3>
        <p class="kpi-value"><?php echo number_format($total_packages_sold); ?></p>
        <small style="margin-top: -10px; display: block;">Total Accounts Created</small>
         <div class="kpi-subvalue">
            <div><span>Total Points Generated:</span> <span><?php echo number_format($total_points_generated); ?></span></div>
        </div>
    </div>

    <!-- Retention Card -->
    <div class="kpi-card">
    <h3>Total Retention (Company Gain)</h3>
    <p class="kpi-value">₱<?php echo number_format($total_retention, 2); ?></p>
     <div class="kpi-subvalue">
        <div><span>From Flush-Outs:</span> <span>₱<?php echo number_format($total_retention, 2); ?></span></div>
    </div>
</div>
</div>

<?php
require_once '../../src/templates/admin_footer.php';
$mysqli->close();
?>