<?php
session_start();
require_once '../../src/templates/admin_header.php';
require_once '../../src/includes/db_connect.php';

// Security: Only admins can access
if ($_SESSION['role'] !== 'admin') {
    echo "<h2>Access Denied</h2></header><div class='card'><p>You do not have permission.</p></div>";
    require_once '../../src/templates/admin_footer.php';
    exit();
}

// Get the staff ID from the URL
$staff_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($staff_id <= 0) {
    // Invalid ID, redirect
    header('Location: staff_management.php');
    exit();
}

// Fetch the staff member's data from the database
$sql = "SELECT id, name, email FROM users WHERE id = ? AND role = 'staff'";
if ($stmt = $mysqli->prepare($sql)) {
    $stmt->bind_param("i", $staff_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $staff = $result->fetch_assoc();
    } else {
        // Staff member not found
        $_SESSION['message'] = "Staff member not found.";
        $_SESSION['msg_type'] = "danger";
        header('Location: staff_management.php');
        exit();
    }
    $stmt->close();
}
?>

<!-- Re-use the same styles from staff_add.php -->
<style>
    .form-group { margin-bottom: 20px; }
    .form-group label { display: block; margin-bottom: 8px; font-weight: 500; color: var(--text-muted); }
    .form-group input { width: 100%; padding: 12px; border-radius: 8px; border: 1px solid var(--border-color); box-sizing: border-box; }
    .form-actions { margin-top: 20px; display: flex; gap: 10px; }
    .btn-secondary { background-color: #f1f1f1; color: var(--text-color); }
    .password-note { color: var(--text-muted); font-style: italic; font-size: 14px; }
</style>

<div class="content-header">
    <h2>Edit Staff Member</h2>
</div>

<div class="card">
    <form action="staff_handle_edit.php" method="POST">
        <!-- Hidden input to pass the staff ID -->
        <input type="hidden" name="id" value="<?php echo htmlspecialchars($staff['id']); ?>">
        
        <div class="form-group">
            <label for="name">Full Name</label>
            <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($staff['name']); ?>" required>
        </div>
        <div class="form-group">
            <label for="email">Email Address</label>
            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($staff['email']); ?>" required>
        </div>
        <hr style="border: none; border-top: 1px solid var(--border-color); margin: 30px 0;">
        <div class="form-group">
            <label for="password">New Password</label>
            <input type="password" id="password" name="password">
            <p class="password-note">Leave blank to keep the current password.</p>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Update Staff Member</button>
            <a href="staff_management.php" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>

<?php
require_once '../../src/templates/admin_footer.php';
$mysqli->close();
?>