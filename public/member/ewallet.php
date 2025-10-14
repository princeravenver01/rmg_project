<?php
session_start();
require_once '../../src/templates/member_header.php';
require_once '../../src/includes/db_connect.php';

$member_id = $_SESSION['user_id'];
$current_balance = 0.00;

// Fetch the current wallet balance
$stmt = $mysqli->prepare("SELECT balance FROM wallets WHERE user_id = ?");
$stmt->bind_param("i", $member_id);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $current_balance = $row['balance'];
}
$stmt->close();
?>

<style>
    .wallet-card {
        background: linear-gradient(45deg, #27ae60, #2ecc71);
        color: white;
        padding: 40px;
        border-radius: 15px;
        text-align: center;
        box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        margin-bottom: 30px;
    }
    .wallet-card .balance-label {
        font-size: 18px;
        opacity: 0.8;
        margin-bottom: 10px;
    }
    .wallet-card .balance-amount {
        font-size: 48px;
        font-weight: 700;
        letter-spacing: -1px;
    }
    .wallet-actions {
        margin-top: 30px;
        display: flex;
        justify-content: center;
        gap: 20px;
    }
    /* White button style for wallet card */
    .btn-light {
        background-color: white;
        color: #27ae60;
        padding: 12px 25px;
        border-radius: 8px;
        text-decoration: none;
        font-weight: 600;
        box-shadow: 0 4px 10px rgba(0,0,0,0.1);
    }
</style>

<div class="content-header">
    <h2>My Wallet</h2>
    <p>View your current balance and request payouts.</p>
</div>

<div class="wallet-card">
    <div class="balance-label">Current Available Balance</div>
    <div class="balance-amount">₱<?php echo number_format($current_balance, 2); ?></div>
    <div class="wallet-actions">
    <a href="request_encashment.php" class="btn-light">Request Encashment</a>
        <a href="commission_history.php" class="btn-light">View History</a>
    </div>
</div>

<div class="card">
    <h3>Recent Transactions</h3>
    <p>Your most recent earnings and withdrawals will be listed here.</p>
    <!-- We will populate this with data from the commission_history.php page later -->
</div>

<?php
require_once '../../src/templates/member_footer.php';
$mysqli->close();
?>