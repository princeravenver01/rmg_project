<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] !== 'member') {
    header('Location: ../login.php');
    exit;
}
// Include the new header, which also handles security checks
require_once '../../src/templates/member_header.php';
require_once '../../src/includes/db_connect.php';


// --- Fetch Member-Specific Data ---
$member_id = $_SESSION['user_id'];

// Get total unprocessed points for Left and Right legs
$sql_points = "SELECT 
                    SUM(CASE WHEN position = 'L' THEN points ELSE 0 END) AS left_points,
                    SUM(CASE WHEN position = 'R' THEN points ELSE 0 END) AS right_points
                FROM binary_points 
                WHERE user_id = ? AND status = 'unprocessed'";

$left_points = 0;
$right_points = 0;

if ($stmt = $mysqli->prepare($sql_points)) {
    $stmt->bind_param("i", $member_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $left_points = $row['left_points'] ?? 0;
        $right_points = $row['right_points'] ?? 0;
    }
    $stmt->close();
}

// Get total earnings (this requires a commissions table which we'll add later)
$total_earnings = 0; // Placeholder for now

// Count direct referrals (people they personally sponsored)
$sql_referrals = "SELECT COUNT(id) AS direct_referrals FROM genealogy_tree WHERE sponsor_id = ?";
$direct_referrals = 0;
if ($stmt_ref = $mysqli->prepare($sql_referrals)) {
    $stmt_ref->bind_param("i", $member_id);
    $stmt_ref->execute();
    $result_ref = $stmt_ref->get_result();
    if ($row_ref = $result_ref->fetch_assoc()) {
        $direct_referrals = $row_ref['direct_referrals'];
    }
    $stmt_ref->close();
}

?>

<div class="content-header">
    <h2>My Dashboard</h2>
    <p>Welcome back, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</p>
</div>

<!-- Statistic Cards -->
<div class="stat-cards-container">
    <div class="stat-card">
        <h3>Left Points (PV)</h3>
        <p class="stat-number"><?php echo number_format($left_points); ?></p>
    </div>
    <div class="stat-card">
        <h3>Right Points (PV)</h3>
        <p class="stat-number"><?php echo number_format($right_points); ?></p>
    </div>
    <div class="stat-card">
        <h3>Total Earnings (PHP)</h3>
        <p class="stat-number"><?php echo number_format($total_earnings, 2); ?></p>
    </div>
    <div class="stat-card">
        <h3>Direct Referrals</h3>
        <p class="stat-number"><?php echo $direct_referrals; ?></p>
    </div>
</div>

<!-- Add more sections here later, like recent activity or rank progress -->

<?php
$mysqli->close();
require_once '../../src/templates/member_footer.php';
?>