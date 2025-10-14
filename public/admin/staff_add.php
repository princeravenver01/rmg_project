<?php
session_start();
require_once '../../src/templates/admin_header.php';

// --- Security Check: Only Admins can add staff ---
if ($_SESSION['role'] !== 'admin') {
    echo "<h2>Access Denied</h2></header><div class='card'><p>You do not have permission to perform this action.</p></div>";
    require_once '../../src/templates/admin_footer.php';
    exit();
}
?>

<!-- Custom CSS for form elements -->
<style>
    .form-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
    }
    .form-group {
        margin-bottom: 20px;
    }
    .form-group label {
        display: block;
        margin-bottom: 8px;
        font-weight: 500;
        color: var(--text-muted);
    }
    .form-group input {
        width: 100%;
        padding: 12px;
        border-radius: 8px;
        border: 1px solid var(--border-color);
        box-sizing: border-box; /* Important for padding to work correctly */
    }
    .form-actions {
        margin-top: 20px;
        display: flex;
        gap: 10px;
    }
    .btn-secondary {
        background-color: #f1f1f1;
        color: var(--text-color);
    }
    .alert {
        padding: 15px;
        margin-bottom: 20px;
        border-radius: 8px;
        font-weight: 500;
    }
    .alert-success { background-color: #d4edda; color: #155724; }
    .alert-danger { background-color: #f8d7da; color: #721c24; }
</style>

<div class="content-header">
    <h2>Add New Staff Member</h2>
</div>

<div class="card">
    <?php
    // Display any success or error messages stored in the session
    if (isset($_SESSION['message'])) {
        echo '<div class="alert alert-' . $_SESSION['msg_type'] . '">' . $_SESSION['message'] . '</div>';
        unset($_SESSION['message']);
        unset($_SESSION['msg_type']);
    }
    ?>
    <form action="staff_handle_add.php" method="POST">
        <div class="form-grid">
            <div class="form-group">
                <label for="name">Full Name</label>
                <input type="text" id="name" name="name" required>
            </div>
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" required>
            </div>
        </div>
        <div class="form-grid">
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Save Staff Member</button>
            <a href="staff_management.php" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>

<?php
require_once '../../src/templates/admin_footer.php';
?>