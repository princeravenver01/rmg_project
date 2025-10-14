<?php
session_start();
require_once '../../src/templates/admin_header.php';
require_once '../../src/includes/db_connect.php';
if ($_SESSION['role'] !== 'admin') { exit('Access Denied'); }

$gc_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($gc_id <= 0) { header('Location: manage_gcs.php'); exit(); }

$stmt = $mysqli->prepare("SELECT gc.*, u.username FROM gift_certificates gc JOIN users u ON gc.user_id = u.id WHERE gc.id = ?");
$stmt->bind_param("i", $gc_id);
$stmt->execute();
$gc = $stmt->get_result()->fetch_assoc();
if (!$gc) { header('Location: manage_gcs.php'); exit(); }
$stmt->close();
?>

<div class="content-header"><h2>Edit Gift Certificate</h2></div>

<div class="card" style="max-width: 600px; margin: auto;">
    <form action="handle_gc_edit.php" method="POST">
        <input type="hidden" name="id" value="<?php echo $gc['id']; ?>">
        <div class="form-group">
            <label>Code</label>
            <input type="text" value="<?php echo htmlspecialchars($gc['code']); ?>" readonly>
        </div>
        <div class="form-group">
            <label>Owner</label>
            <input type="text" value="<?php echo htmlspecialchars($gc['username']); ?>" readonly>
        </div>
        <div class="form-group">
            <label for="amount">Amount (₱)</label>
            <input type="number" step="0.01" id="amount" name="amount" value="<?php echo htmlspecialchars($gc['amount']); ?>" <?php echo $gc['status'] === 'used' ? 'readonly' : ''; ?> required>
            <?php if ($gc['status'] === 'used'): ?>
                <small>Cannot edit amount of a used certificate.</small>
            <?php endif; ?>
        </div>
        <button type="submit" class="btn btn-primary" <?php echo $gc['status'] === 'used' ? 'disabled' : ''; ?>>Update Amount</button>
        <a href="manage_gcs.php" class="btn btn-secondary">Cancel</a>
    </form>
</div>

<?php require_once '../../src/templates/admin_footer.php'; $mysqli->close(); ?>