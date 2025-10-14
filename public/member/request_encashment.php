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
    .form-group { margin-bottom: 20px; }
    .form-group label { display: block; margin-bottom: 8px; font-weight: 500; }
    .form-group input { width: 100%; max-width: 400px; padding: 12px; border-radius: 8px; border: 1px solid var(--border-color); }
    .balance-note { color: var(--text-muted); font-size: 14px; margin-top: 5px; }
</style>

<div class="content-header">
    <h2>Request Encashment</h2>
</div>

<div class="card">
    <?php
    if (isset($_SESSION['message'])) {
        echo '<div style="padding: 15px; margin-bottom: 20px; border-radius: 8px; font-weight: 500; background-color: ' . ($_SESSION['msg_type'] == 'success' ? '#d4edda; color: #155724;' : '#f8d7da; color: #721c24;') . '">' . $_SESSION['message'] . '</div>';
        unset($_SESSION['message'], $_SESSION['msg_type']);
    }
    ?>
    <form action="handle_encashment_request.php" method="POST">
        <div class="form-group">
            <label for="amount">Amount to Withdraw</label>
            <input type="number" step="0.01" id="amount" name="amount" min="1" max="<?php echo $current_balance; ?>" required>
            <p class="balance-note">Available Balance: ₱<?php echo number_format($current_balance, 2); ?></p>
        </div>
        
        <p><strong>Note:</strong> Payouts are processed via [Your Payout Method, e.g., Bank Transfer, GCash]. Please ensure your profile information is up to date.</p>

        <div class="form-actions" style="margin-top: 20px;">
            <button type="submit" class="btn btn-primary">Submit Request</button>
            <a href="ewallet.php" class="btn btn-secondary" style="background:#f1f1f1">Cancel</a>
        </div>
    </form>
</div>

<?php
require_once '../../src/templates/member_footer.php';
$mysqli->close();
?>