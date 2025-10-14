<?php
session_start();
require_once '../../src/templates/admin_header.php';
require_once '../../src/includes/db_connect.php';
if ($_SESSION['role'] !== 'admin') { exit('Access Denied'); }
?>

<style>
    .grid-container { display: grid; grid-template-columns: 2fr 1fr; gap: 30px; align-items: flex-start; }
    .action-link { text-decoration: none; }
</style>

<div class="content-header">
    <h2>Gift Certificate Management</h2>
</div>

<div class="grid-container">
    <!-- Left Side: List of GCs -->
    <div class="card">
        <h4>All Gift Certificates</h4>
        <table style="width:100%; border-collapse: collapse;">
            <thead>
                <tr>
                    <th>Code</th><th>Amount</th><th>Status</th><th>Owner</th><th>Used In Sale</th><th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $sql = "SELECT gc.*, u.username 
                        FROM gift_certificates gc
                        JOIN users u ON gc.user_id = u.id
                        ORDER BY gc.id DESC";
                $result = $mysqli->query($sql);
                while ($gc = $result->fetch_assoc()):
                ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($gc['code']); ?></strong></td>
                    <td>₱<?php echo number_format($gc['amount'], 2); ?></td>
                    <td>
                        <?php if ($gc['status'] === 'active'): ?>
                            <span style="color:green; font-weight:bold;">Active</span>
                        <?php else: ?>
                            <span style="color:red; font-weight:bold;">Used</span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo htmlspecialchars($gc['username']); ?></td>
                    <td>
                        <?php if ($gc['used_in_sale_id']): ?>
                            <a href="generate_receipt.php?sale_id=<?php echo $gc['used_in_sale_id']; ?>" target="_blank">
                                Sale #<?php echo $gc['used_in_sale_id']; ?>
                            </a>
                        <?php else: echo 'N/A'; endif; ?>
                    </td>
                    <td>
                        <a href="gc_edit.php?id=<?php echo $gc['id']; ?>" class="action-link">Edit</a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <!-- Right Side: Generate New GC -->
    <div class="card">
        <h4>Manually Generate GC</h4>
        <p><small>Use this to issue a gift certificate to a member for promotions, refunds, etc.</small></p>
        
        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-<?php echo $_SESSION['msg_type']; ?>"><?php echo $_SESSION['message']; ?></div>
            <?php unset($_SESSION['message'], $_SESSION['msg_type']); ?>
        <?php endif; ?>

        <form action="handle_gc_generate.php" method="POST">
            <div class="form-group">
                <label for="member_id">Member's User ID</label>
                <input type="number" id="member_id" name="member_id" required>
            </div>
            <div class="form-group">
                <label for="amount">Amount (₱)</label>
                <input type="number" step="0.01" id="amount" name="amount" required>
            </div>
            <button type="submit" class="btn btn-primary" style="width:100%;">Generate GC</button>
        </form>
    </div>
</div>

<?php require_once '../../src/templates/admin_footer.php'; $mysqli->close(); ?>