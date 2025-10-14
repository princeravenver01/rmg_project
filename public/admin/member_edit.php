<?php
session_start();
require_once '../../src/templates/admin_header.php';
require_once '../../src/includes/db_connect.php';

if ($_SESSION['role'] !== 'admin') { exit('Access Denied'); }

$member_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($member_id <= 0) { header('Location: member_management.php'); exit(); }

// Fetch member data including their current upline's USERNAME
$stmt = $mysqli->prepare("
    SELECT u.id, u.name, u.username, u.email, gt.upline_id, gt.position, up.username as upline_username
    FROM users u
    LEFT JOIN genealogy_tree gt ON u.id = gt.user_id
    LEFT JOIN users up ON gt.upline_id = up.id
    WHERE u.id = ? AND u.role = 'member'
");
$stmt->bind_param("i", $member_id);
$stmt->execute();
$member = $stmt->get_result()->fetch_assoc();
if (!$member) { header('Location: member_management.php'); exit(); }
$stmt->close();
?>

<style>
    .form-group { margin-bottom: 20px; }
    .form-group label { display: block; margin-bottom: 8px; font-weight: 500; }
    .form-group input, .form-group select { width: 100%; max-width: 500px; padding: 12px; border-radius: 8px; border: 1px solid var(--border-color); }
    .password-note { color: var(--text-muted); font-size: 14px; }
    #upline-validation-msg { margin-top: 5px; font-weight: 500; font-size: 14px; }
    .valid-upline { color: green; } .invalid-upline { color: red; }
</style>

<div class="content-header">
    <h2>Edit Member: <?php echo htmlspecialchars($member['name']); ?></h2>
</div>

<div class="card">
     <?php
    if (isset($_SESSION['message'])) {
        echo '<div style="padding: 15px; margin-bottom: 20px; border-radius: 8px; font-weight: 500; background-color: ' . ($_SESSION['msg_type'] == 'success' ? '#d4edda; color: #155724;' : '#f8d7da; color: #721c24;') . '">' . $_SESSION['message'] . '</div>';
        unset($_SESSION['message'], $_SESSION['msg_type']);
    }
    ?>
    <form action="handle_member_edit.php" method="POST">
        <input type="hidden" name="id" value="<?php echo $member['id']; ?>">
        <!-- This hidden field will be populated by JavaScript after successful validation -->
        <input type="hidden" id="upline_id_validated" name="upline_id" value="<?php echo $member['upline_id']; ?>">
        
        <h4>Member Details</h4>
        <div class="form-group">
            <label for="name">Full Name</label>
            <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($member['name']); ?>" required>
        </div>
        <div class="form-group">
            <label for="username">Username</label>
            <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($member['username']); ?>" required>
        </div>
        <div class="form-group">
            <label for="email">Email Address</label>
            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($member['email']); ?>" required>
        </div>

        <hr style="border:none; border-top: 1px solid #eee; margin: 30px 0;">
        <h4>Change Placement</h4>
        <div class="form-group">
            <label for="upline_username">New Upline Username</label>
            <input type="text" id="upline_username" name="upline_username" value="<?php echo htmlspecialchars($member['upline_username']); ?>">
            <div id="upline-validation-msg"></div>
        </div>
        <div class="form-group">
            <label for="position">Position under New Upline</label>
            <select id="position" name="position" required>
                <option value="L" <?php echo ($member['position'] === 'L') ? 'selected' : ''; ?>>Left</option>
                <option value="R" <?php echo ($member['position'] === 'R') ? 'selected' : ''; ?>>Right</option>
            </select>
        </div>

        <hr style="border:none; border-top: 1px solid #eee; margin: 30px 0;">
        <h4>Reset Password</h4>
        <div class="form-group">
            <label for="password">New Password</label>
            <input type="password" id="password" name="password">
            <p class="password-note">Leave blank to keep the current password.</p>
        </div>
        
        <div class="form-actions" style="margin-top: 20px;">
            <button type="submit" class="btn btn-primary">Update Member</button>
            <a href="member_management.php" class="btn btn-secondary" style="background:#f1f1f1">Cancel</a>
        </div>
    </form>
</div>

<!-- =================================================================== -->
<!--               JAVASCRIPT FOR UPLINE VALIDATION                      -->
<!-- =================================================================== -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const uplineUsernameInput = document.getElementById('upline_username');
    const positionSelect = document.getElementById('position');
    const validationMsgDiv = document.getElementById('upline-validation-msg');
    const validatedUplineIdInput = document.getElementById('upline_id_validated');
    const memberId = <?php echo $member_id; ?>;
    let debounceTimer;

    function validateUpline() {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => {
            const username = uplineUsernameInput.value.trim();
            const position = positionSelect.value;
            
            if (!username) {
                validationMsgDiv.textContent = '';
                return;
            }

            validationMsgDiv.textContent = 'Checking...';
            validationMsgDiv.className = '';

            fetch(`api_check_upline.php?username=${encodeURIComponent(username)}&position=${position}&member_id=${memberId}`)
                .then(response => response.json())
                .then(data => {
                    validationMsgDiv.textContent = data.message;
                    if (data.valid) {
                        validationMsgDiv.className = 'valid-upline';
                        validatedUplineIdInput.value = data.upline_id;
                    } else {
                        validationMsgDiv.className = 'invalid-upline';
                        // On failure, we should reset the hidden ID to the original value to prevent errors
                        validatedUplineIdInput.value = '<?php echo $member['upline_id']; ?>'; 
                    }
                });
        }, 500);
    }

    uplineUsernameInput.addEventListener('keyup', validateUpline);
    positionSelect.addEventListener('change', validateUpline);
});
</script>

<?php require_once '../../src/templates/admin_footer.php'; $mysqli->close(); ?>