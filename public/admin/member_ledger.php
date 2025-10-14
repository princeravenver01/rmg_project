<?php
session_start();
require_once '../../src/templates/admin_header.php';
require_once '../../src/includes/db_connect.php';

if ($_SESSION['role'] !== 'admin') { exit('Access Denied'); }

$member_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($member_id <= 0) { die("Invalid Member ID."); }

// --- 1. Fetch Basic Member Data ---
$stmt = $mysqli->prepare("SELECT * FROM users WHERE id = ? AND role = 'member'");
$stmt->bind_param("i", $member_id);
$stmt->execute();
$member = $stmt->get_result()->fetch_assoc();
if (!$member) { die("Member not found."); }
$stmt->close();


// --- 2. Perform All Data Queries with Corrections ---

// Wallet Balance
$wallet_balance_res = $mysqli->query("SELECT balance FROM wallets WHERE user_id = $member_id");
$wallet_balance = $wallet_balance_res->fetch_assoc()['balance'] ?? 0.00;

// Total Earnings (All Commissions)
$total_earnings_res = $mysqli->query("SELECT SUM(amount) AS total FROM commissions WHERE user_id = $member_id");
$total_earnings = $total_earnings_res->fetch_assoc()['total'] ?? 0.00;

// Total Encashed (Approved Requests)
$total_encashed_res = $mysqli->query("SELECT SUM(amount) AS total FROM encashment_requests WHERE user_id = $member_id AND status = 'approved'");
$total_encashed = $total_encashed_res->fetch_assoc()['total'] ?? 0.00;

// Total Direct Referrals (Corrected Query)
$total_directs_res = $mysqli->query("SELECT COUNT(id) AS count FROM genealogy_tree WHERE sponsor_id = $member_id AND user_id != $member_id");
$total_directs = $total_directs_res->fetch_assoc()['count'];

// Total Points Left/Right
$points_res = $mysqli->query("SELECT SUM(CASE WHEN position = 'L' THEN points ELSE 0 END) AS left_points, SUM(CASE WHEN position = 'R' THEN points ELSE 0 END) AS right_points FROM binary_points WHERE user_id = $member_id AND status = 'unprocessed'");
$points = $points_res->fetch_assoc();
$left_points = $points['left_points'] ?? 0;
$right_points = $points['right_points'] ?? 0;

// Total Points (All Time) - NEW
$total_points_res = $mysqli->query("SELECT SUM(points) AS total FROM binary_points WHERE user_id = $member_id");
$total_points_overall = $total_points_res->fetch_assoc()['total'] ?? 0;

// Total Redundant Points - NEW
$redundant_points_res = $mysqli->query("SELECT redundant_points_balance FROM member_profiles WHERE user_id = $member_id");
$total_redundant_points = $redundant_points_res->fetch_assoc()['redundant_points_balance'] ?? 0;

// Gift Certificates
$gcs_res = $mysqli->query("SELECT * FROM gift_certificates WHERE user_id = $member_id ORDER BY id DESC");

// Encashment History
$encashment_history_res = $mysqli->query("SELECT * FROM encashment_requests WHERE user_id = $member_id ORDER BY id DESC");

// Commission History (Breakdown)
$commission_history_res = $mysqli->query("SELECT * FROM commissions WHERE user_id = $member_id ORDER BY id DESC");

// Product Purchase History - NEW
$product_history_res = $mysqli->query("
    SELECT
        ps.created_at,
        p.name AS product_name,
        psi.quantity,
        psi.price_per_item
    FROM product_sales AS ps
    JOIN product_sale_items AS psi ON ps.id = psi.sale_id
    JOIN products AS p ON psi.product_id = p.id
    WHERE ps.member_id = $member_id
    ORDER BY ps.created_at DESC
");


// --- Complex Queries for Downline Counts ---
function getDownlineCount($mysqli, $parent_id) {
    if (!$parent_id) return 0;
    $count = 0; $queue = [$parent_id]; $visited = [];
    while(!empty($queue)) {
        $current_id = array_shift($queue);
        if(in_array($current_id, $visited)) continue;
        $visited[] = $current_id;
        $count++; // Count the person themselves
        $stmt = $mysqli->prepare("SELECT user_id FROM genealogy_tree WHERE upline_id = ?");
        $stmt->bind_param("i", $current_id); $stmt->execute();
        $result = $stmt->get_result();
        while($row = $result->fetch_assoc()) {
            $queue[] = $row['user_id'];
        }
        $stmt->close();
    }
    return $count;
}

// Find the Left and Right children's IDs
$children_res = $mysqli->query("SELECT user_id, position FROM genealogy_tree WHERE upline_id = $member_id");
$left_child_id = null; $right_child_id = null;
while($child = $children_res->fetch_assoc()) {
    if($child['position'] === 'L') $left_child_id = $child['user_id'];
    if($child['position'] === 'R') $right_child_id = $child['user_id'];
}

// Corrected count: getDownlineCount now returns the total number of people in that branch
$total_left_downline = getDownlineCount($mysqli, $left_child_id);
$total_right_downline = getDownlineCount($mysqli, $right_child_id);
$total_downline = $total_left_downline + $total_right_downline;
?>

<style>
    .ledger-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; }
    .kpi-card { background: #fff; padding: 20px; border-radius: 8px; box-shadow: var(--shadow); }
    .kpi-card h4 { margin-top: 0; border-bottom: 1px solid #eee; padding-bottom: 10px; }
    .kpi-line { display: flex; justify-content: space-between; font-size: 16px; padding: 8px 0; }
    .kpi-line span:first-child { color: #555; }
    .kpi-line span:last-child { font-weight: 600; }
    .history-table { width: 100%; margin-top: 15px; border-collapse: collapse; }
    .history-table th, .history-table td { padding: 8px; border-bottom: 1px solid #eee; font-size: 14px; text-align: left; }
</style>

<div class="content-header">
    <h2>Member Ledger: <?php echo htmlspecialchars($member['name']); ?> ([<?php echo htmlspecialchars($member['username']); ?>])</h2>
</div>

<div class="ledger-grid">
    <!-- Left Column: Financials -->
    <div class="kpi-card">
        <h4>Financial Summary</h4>
        <div class="kpi-line"><span>Total Earnings (All Time):</span> <span>₱<?php echo number_format($total_earnings, 2); ?></span></div>
        <div class="kpi-line"><span>Total Encashed (Approved):</span> <span>₱<?php echo number_format($total_encashed, 2); ?></span></div>
        <div class="kpi-line"><span>Current Wallet Balance:</span> <span style="color:green; font-size: 1.2em;">₱<?php echo number_format($wallet_balance, 2); ?></span></div>
    </div>

    <!-- Right Column: Network Stats -->
    <div class="kpi-card">
        <h4>Network Summary</h4>
        <div class="kpi-line"><span>Total Direct Referrals:</span> <span><?php echo number_format($total_directs); ?></span></div>
        <div class="kpi-line"><span>Total Downline (Binary):</span> <span><?php echo number_format($total_downline); ?></span></div>
        <div class="kpi-line"><span> - Left Leg Members:</span> <span><?php echo number_format($total_left_downline); ?></span></div>
        <div class="kpi-line"><span> - Right Leg Members:</span> <span><?php echo number_format($total_right_downline); ?></span></div>
        <div class="kpi-line"><span>Current Unprocessed Points:</span> <span>L: <?php echo number_format($left_points); ?> | R: <?php echo number_format($right_points); ?></span></div>
        <div class="kpi-line"><span>Total Points (All Time):</span> <span style="color: blue;"><?php echo number_format($total_points_overall); ?></span></div>
        <div class="kpi-line"><span>Total Redundant Points:</span> <span style="color: #c0392b;"><?php echo number_format($total_redundant_points); ?></span></div>
    </div>
</div>

<div class="card" style="margin-top: 30px;">
    <h4>Commission History</h4>
    <table class="history-table">
        <thead><tr><th>Date</th><th>Type</th><th>Amount</th><th>Source ID</th></tr></thead>
        <tbody>
            <?php while($row = $commission_history_res->fetch_assoc()): ?>
            <tr>
                <td><?php echo date('Y-m-d', strtotime($row['created_at'])); ?></td>
                <td><?php echo ucwords(str_replace('_', ' ', $row['type'])); ?></td>
                <td style="color:green;">+₱<?php echo number_format($row['amount'], 2); ?></td>
                <td><?php echo $row['source_user_id'] ?? 'N/A'; ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<div class="card" style="margin-top: 30px;">
    <h4>Encashment History</h4>
    <table class="history-table">
        <thead><tr><th>Date</th><th>Amount</th><th>Status</th></tr></thead>
        <tbody>
            <?php while($row = $encashment_history_res->fetch_assoc()): ?>
            <tr>
                <td><?php echo date('Y-m-d', strtotime($row['requested_at'])); ?></td>
                <td>₱<?php echo number_format($row['amount'], 2); ?></td>
                <td><?php echo ucfirst($row['status']); ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<div class="card" style="margin-top: 30px;">
    <h4>Gift Certificates Earned</h4>
    <table class="history-table">
        <thead><tr><th>Date</th><th>Code</th><th>Amount</th><th>Status</th></tr></thead>
        <tbody>
            <?php while($row = $gcs_res->fetch_assoc()): ?>
            <tr>
                <td><?php echo date('Y-m-d', strtotime($row['created_at'])); ?></td>
                <td><?php echo htmlspecialchars($row['code']); ?></td>
                <td>₱<?php echo number_format($row['amount'], 2); ?></td>
                <td><?php echo ucfirst($row['status']); ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<!-- NEW: Product Purchase History -->
<div class="card" style="margin-top: 30px;">
    <h4>Product Purchase History</h4>
    <table class="history-table">
        <thead>
            <tr>
                <th>Date</th>
                <th>Product Name</th>
                <th>Quantity</th>
                <th>Price per Item</th>
                <th>Subtotal</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($product_history_res->num_rows > 0): ?>
                <?php while($row = $product_history_res->fetch_assoc()): ?>
                <tr>
                    <td><?php echo date('Y-m-d H:i', strtotime($row['created_at'])); ?></td>
                    <td><?php echo htmlspecialchars($row['product_name']); ?></td>
                    <td><?php echo $row['quantity']; ?></td>
                    <td>₱<?php echo number_format($row['price_per_item'], 2); ?></td>
                    <td>₱<?php echo number_format($row['quantity'] * $row['price_per_item'], 2); ?></td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5" style="text-align: center;">No product purchase history found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>


<?php
require_once '../../src/templates/admin_footer.php';
$mysqli->close();
?>