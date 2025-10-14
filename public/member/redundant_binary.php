<?php
session_start();
require_once '../../src/templates/member_header.php';
require_once '../../src/includes/db_connect.php';

$member_id = $_SESSION['user_id'];
$redundant_balance = 0;

// Fetch the member's current redundant points balance
$stmt = $mysqli->prepare("SELECT redundant_points_balance FROM member_profiles WHERE user_id = ?");
$stmt->bind_param("i", $member_id);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $redundant_balance = (int)$row['redundant_points_balance'];
}
$stmt->close();
?>

<style>
    .balance-display { text-align: center; margin-bottom: 30px; }
    .balance-display .amount { font-size: 48px; font-weight: 700; color: var(--primary-color); }
    .balance-display .label { font-size: 16px; color: #777; }
    .placement-form { max-width: 600px; margin: 0 auto; }
    .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
</style>

<div class="content-header">
    <h2>Redundant Binary Points</h2>
    <p>Distribute the points you've earned from product repurchases into your binary legs.</p>
</div>

<div class="card">
    <?php if (isset($_SESSION['message'])) { /* ... message display code ... */ } ?>
    
    <div class="balance-display">
        <div class="amount"><?php echo number_format($redundant_balance); ?></div>
        <div class="label">Available Redundant Points</div>
    </div>

    <?php if ($redundant_balance > 0): ?>
    <form action="handle_place_redundant.php" method="POST" class="placement-form">
        <p style="text-align: center;">Distribute your <strong><?php echo $redundant_balance; ?></strong> points:</p>
        <div class="form-grid">
            <div class="form-group">
                <label for="left_points">Place on LEFT Leg</label>
                <input type="number" id="left_points" name="left_points" value="0" min="0" max="<?php echo $redundant_balance; ?>">
            </div>
            <div class="form-group">
                <label for="right_points">Place on RIGHT Leg</label>
                <input type="number" id="right_points" name="right_points" value="0" min="0" max="<?php echo $redundant_balance; ?>">
            </div>
        </div>
        <p style="text-align: center; font-weight: bold;">Total to Place: <span id="total-to-place">0</span> / <?php echo $redundant_balance; ?></p>
        <div id="placement-error" style="color:red; text-align:center; font-weight:bold; margin-bottom:15px;"></div>

        <button type="submit" id="place-points-btn" class="btn btn-primary" style="width:100%;">Place Points</button>
    </form>
    <?php else: ?>
        <p style="text-align: center;">You have no redundant points to place. Points are earned from personal product repurchases.</p>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const leftInput = document.getElementById('left_points');
    const rightInput = document.getElementById('right_points');
    const totalDisplay = document.getElementById('total-to-place');
    const errorDisplay = document.getElementById('placement-error');
    const placeBtn = document.getElementById('place-points-btn');
    const maxPoints = <?php echo $redundant_balance; ?>;

    function updateTotal() {
        const left = parseInt(leftInput.value) || 0;
        const right = parseInt(rightInput.value) || 0;
        const total = left + right;

        totalDisplay.textContent = total;

        if (total > maxPoints) {
            errorDisplay.textContent = 'Total points placed cannot exceed your available balance!';
            placeBtn.disabled = true;
        } else {
            errorDisplay.textContent = '';
            placeBtn.disabled = false;
        }
    }

    if(leftInput && rightInput) {
        leftInput.addEventListener('input', updateTotal);
        rightInput.addEventListener('input', updateTotal);
    }
});
</script>

<?php require_once '../../src/templates/member_footer.php'; $mysqli->close(); ?>