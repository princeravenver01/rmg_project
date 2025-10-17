<?php
session_start();
require_once '../../src/templates/admin_header.php';
require_once '../../src/includes/db_connect.php';

// Security check
if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'staff') {
    exit('Access Denied');
}

// --- Fetching Live Data for Dashboard Widgets ---

// 1. Total Codes Generated (from purchase_history where it's a code generation)
$total_codes_generated_result = $mysqli->query("SELECT COUNT(id) AS count FROM purchase_history");
$total_codes_generated = $total_codes_generated_result->fetch_assoc()['count'];

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
$retention_result = $mysqli->query("SELECT SUM(amount) AS total FROM company_gains WHERE type = 'flush_out'");
$total_retention = (float)($retention_result->fetch_assoc()['total'] ?? 0);

// 6. Net Company Fund
$total_revenue_result = $mysqli->query("
    SELECT SUM(ph.price_paid) AS total 
    FROM purchase_history ph
    WHERE ph.account_type = 'Paid Account' OR ph.account_type = 'CD Account'
");
$total_revenue = (float)($total_revenue_result->fetch_assoc()['total'] ?? 0);
$net_company_fund = $total_revenue - $total_commissions_paid;


// --- NEW DATA QUERIES FOR REQUESTED FEATURES ---

// A. Total Active Accounts
$total_active_accounts_result = $mysqli->query("SELECT COUNT(id) AS count FROM users WHERE is_active = 1 AND role = 'member'");
$total_active_accounts = $total_active_accounts_result->fetch_assoc()['count'] ?? 0;

// B. Total Commissions Unpaid (Sum of wallet balances)
$total_unpaid_comm_result = $mysqli->query("SELECT SUM(balance) AS total FROM wallets");
$total_commissions_unpaid = (float)($total_unpaid_comm_result->fetch_assoc()['total'] ?? 0);

// C. Account Type Breakdown
$total_paid_accounts_result = $mysqli->query("SELECT COUNT(id) AS count FROM users WHERE account_type = 'Paid Account' AND role = 'member'");
$total_paid_accounts = $total_paid_accounts_result->fetch_assoc()['count'] ?? 0;

$total_fs_accounts = $total_paid_accounts; // As requested, FS Account is the same as Paid Account

$total_cd_accounts_result = $mysqli->query("SELECT COUNT(id) AS count FROM users WHERE account_type = 'CD Account' AND role = 'member'");
$total_cd_accounts = $total_cd_accounts_result->fetch_assoc()['count'] ?? 0;

// D. CD Account Debt Progress
$cd_debt_remaining_result = $mysqli->query("
    SELECT SUM(mp.debt_balance) AS total_remaining 
    FROM member_profiles mp
    JOIN users u ON mp.user_id = u.id
    WHERE u.account_type = 'CD Account'
");
$cd_debt_remaining = (float)($cd_debt_remaining_result->fetch_assoc()['total_remaining'] ?? 0);

$cd_debt_paid_result = $mysqli->query("
    SELECT SUM(dp.amount_deducted) AS total_paid
    FROM debt_payments dp
    JOIN users u ON dp.user_id = u.id
    WHERE u.account_type = 'CD Account'
");
$cd_debt_paid = (float)($cd_debt_paid_result->fetch_assoc()['total_paid'] ?? 0);

$cd_initial_debt = $cd_debt_remaining + $cd_debt_paid;
$cd_progress_percentage = ($cd_initial_debt > 0) ? ($cd_debt_paid / $cd_initial_debt) * 100 : 0;

// E. Chart Data (Last 12 Months)
$chart_labels = [];
$chart_commissions_data = [];
$chart_codes_data = [];
for ($i = 11; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-$i months"));
    $chart_labels[] = date('M Y', strtotime("-$i months"));
    $chart_commissions_data[$month] = 0;
    $chart_codes_data[$month] = 0;
}

// Fetch commission data for chart
$monthly_comm_result = $mysqli->query("
    SELECT DATE_FORMAT(created_at, '%Y-%m') AS month, SUM(amount) AS total 
    FROM commissions 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
    GROUP BY month ORDER BY month ASC
");
if ($monthly_comm_result) {
    while ($row = $monthly_comm_result->fetch_assoc()) {
        $chart_commissions_data[$row['month']] = (float)$row['total'];
    }
}

// Fetch codes generated data for chart
$monthly_codes_result = $mysqli->query("
    SELECT DATE_FORMAT(created_at, '%Y-%m') AS month, COUNT(id) AS total 
    FROM purchase_history 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
    GROUP BY month ORDER BY month ASC
");
if ($monthly_codes_result) {
    while ($row = $monthly_codes_result->fetch_assoc()) {
        $chart_codes_data[$row['month']] = (int)$row['total'];
    }
}

$chart_commissions_data = array_values($chart_commissions_data);
$chart_codes_data = array_values($chart_codes_data);

?>
<style>
    .kpi-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; }
    .kpi-card { background: #fff; padding: 25px; border-radius: 8px; box-shadow: var(--shadow); border: 1px solid var(--border-color); }
    .kpi-card h3 { margin: 0 0 10px 0; font-size: 16px; color: #777; font-weight: 500; }
    .kpi-card .kpi-value { font-size: 32px; font-weight: 700; color: var(--text-color); }
    .kpi-card .kpi-subvalue { font-size: 14px; margin-top: 15px; padding-top: 10px; border-top: 1px solid #eee; }
    .kpi-card .kpi-subvalue div { display: flex; justify-content: space-between; margin-bottom: 5px; }
    .kpi-card .kpi-subvalue .progress-bar-container { background: #e9ecef; border-radius: 5px; margin-top: 10px; height: 10px; overflow: hidden; }
    .kpi-card .kpi-subvalue .progress-bar { height: 100%; background-color: #28a745; transition: width 0.5s ease-in-out; }
    .chart-container { background: #fff; padding: 25px; border-radius: 8px; box-shadow: var(--shadow); border: 1px solid var(--border-color); margin-top: 30px; height: 450px; }
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
    
    <!-- Commission Status Card -->
    <div class="kpi-card">
        <h3>Commission Status</h3>
        <p class="kpi-value">₱<?php echo number_format($total_commissions_paid, 2); ?></p>
        <small style="margin-top: -10px; display: block;">Total Commissions Paid</small>
         <div class="kpi-subvalue">
            <div><span>Total Unpaid (Wallets):</span> <span style="color: #ff8c00; font-weight: bold;">₱<?php echo number_format($total_commissions_unpaid, 2); ?></span></div>
            <hr style="margin: 8px 0; border-color: #eee; border-style: solid; border-width: 1px 0 0 0;">
            <div><span>Binary Pairs:</span> <span>₱<?php echo number_format($total_binary_comm, 2); ?></span></div>
            <div><span>Leadership Bonus:</span> <span>₱<?php echo number_format($total_leadership_comm, 2); ?></span></div>
            <div><span>Unilevel Bonus:</span> <span>₱<?php echo number_format($total_unilevel_comm, 2); ?></span></div>
        </div>
    </div>
    
    <!-- Network Activity Card -->
    <div class="kpi-card">
        <h3>Network Activity</h3>
        <p class="kpi-value"><?php echo number_format($total_codes_generated); ?></p>
        <small style="margin-top: -10px; display: block;">Total Codes Generated</small>
         <div class="kpi-subvalue">
            <div><span>Total Active Accounts:</span> <span><?php echo number_format($total_active_accounts); ?></span></div>
            <div><span>Total Points Generated:</span> <span><?php echo number_format($total_points_generated); ?></span></div>
        </div>
    </div>

    <!-- Account Breakdown Card -->
    <div class="kpi-card">
        <h3>Account Breakdown</h3>
        <p class="kpi-value"><?php echo number_format($total_active_accounts); ?></p>
        <small style="margin-top: -10px; display: block;">Total Active Accounts</small>
         <div class="kpi-subvalue">
            <div><span>Total Paid Accounts:</span> <span><?php echo number_format($total_paid_accounts); ?></span></div>
            <div><span>Total FS Accounts:</span> <span><?php echo number_format($total_fs_accounts); ?></span></div>
            <div><span>Total CD Accounts:</span> <span><?php echo number_format($total_cd_accounts); ?></span></div>
        </div>
    </div>

    <!-- CD Account Debt Progress Card -->
    <div class="kpi-card">
        <h3>CD Account Debt Progress</h3>
        <p class="kpi-value">₱<?php echo number_format($cd_debt_remaining, 2); ?></p>
        <small style="margin-top: -10px; display: block;">Total Amount Left</small>
        <div class="kpi-subvalue">
            <div><span>Initial Debt:</span> <span>₱<?php echo number_format($cd_initial_debt, 2); ?></span></div>
            <div><span>Total Paid:</span> <span>₱<?php echo number_format($cd_debt_paid, 2); ?></span></div>
            <div class="progress-bar-container">
                <div class="progress-bar" style="width: <?php echo $cd_progress_percentage; ?>%;"></div>
            </div>
            <div style="text-align: right; font-size: 12px; margin-top: 5px;"><?php echo round($cd_progress_percentage, 2); ?>% Paid</div>
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

<!-- Chart Section -->
<div class="chart-container">
    <h3>Monthly Performance Overview</h3>
    <p style="font-size: 14px; color: #777; margin-top: -8px;">Commissions Paid vs. Codes Generated (Last 12 Months)</p>
    <canvas id="monthlyPerformanceChart"></canvas>
</div>

<!-- Chart.js library -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('monthlyPerformanceChart').getContext('2d');
    
    const monthlyPerformanceChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($chart_labels); ?>,
            datasets: [
                {
                    label: 'Commissions Paid (₱)',
                    data: <?php echo json_encode($chart_commissions_data); ?>,
                    backgroundColor: 'rgba(255, 99, 132, 0.5)',
                    borderColor: 'rgba(255, 99, 132, 1)',
                    borderWidth: 1,
                    yAxisID: 'y-commissions'
                },
                {
                    label: 'Codes Generated',
                    data: <?php echo json_encode($chart_codes_data); ?>,
                    backgroundColor: 'rgba(54, 162, 235, 0.5)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1,
                    yAxisID: 'y-codes'
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                'y-commissions': {
                    type: 'linear',
                    display: true,
                    position: 'left',
                    title: { display: true, text: 'Amount (₱)' },
                    beginAtZero: true
                },
                'y-codes': {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    title: { display: true, text: 'Count' },
                    grid: { drawOnChartArea: false },
                    beginAtZero: true
                }
            },
            plugins: {
                tooltip: { mode: 'index', intersect: false },
                legend: { position: 'top' }
            }
        }
    });
});
</script>

<?php
require_once '../../src/templates/admin_footer.php';
$mysqli->close();
?>
