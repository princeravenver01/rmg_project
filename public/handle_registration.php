<?php
session_start();
require_once '../src/includes/db_connect.php';

function redirectWithError($url, $message) {
    $_SESSION['error'] = $message;
    header("Location: " . $url);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php');
    exit();
}

$sponsor_id = filter_input(INPUT_POST, 'sponsor_id', FILTER_VALIDATE_INT);
$upline_id = filter_input(INPUT_POST, 'upline_id', FILTER_VALIDATE_INT);
$position = isset($_POST['position']) ? trim($_POST['position']) : '';
$activation_code = isset($_POST['activation_code']) ? trim($_POST['activation_code']) : '';
$name = isset($_POST['name']) ? trim($_POST['name']) : '';
$username = isset($_POST['username']) ? trim($_POST['username']) : '';
$email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
$password = $_POST['password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';
$beneficiaries = $_POST['beneficiary'] ?? [];

$error_url = 'register.php?' . http_build_query(['sponsor_id' => $sponsor_id, 'upline_id' => $upline_id, 'pos' => $position]);

if (empty($sponsor_id) || empty($upline_id) || !in_array($position, ['L', 'R']) || empty($activation_code) || empty($name) || empty($username) || empty($email) || empty($password)) {
    redirectWithError($error_url, 'A required field was missing. Please fill out the form completely.');
}
if ($password !== $confirm_password) { redirectWithError($error_url, 'Passwords do not match.'); }
if (strlen($password) < 8) { redirectWithError($error_url, 'Password must be at least 8 characters long.'); }
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { redirectWithError($error_url, 'Invalid email address format.'); }

$mysqli->begin_transaction();
try {
    $stmt = $mysqli->prepare("SELECT id FROM users WHERE username = ? OR email = ? FOR UPDATE");
    $stmt->bind_param("ss", $username, $email);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) { throw new Exception("Username or email is already in use."); }
    $stmt->close();

    $stmt = $mysqli->prepare("SELECT ac.id, ac.account_type, p.price, p.points_value FROM activation_codes ac JOIN packages p ON ac.package_id = p.id WHERE ac.code = ? AND ac.status = 'available' FOR UPDATE");
    $stmt->bind_param("s", $activation_code);
    $stmt->execute();
    $code_result = $stmt->get_result();
    if ($code_result->num_rows !== 1) { throw new Exception("This activation code is invalid or has already been used."); }
    $code_data = $code_result->fetch_assoc();
    $stmt->close();
    
    $activation_code_id = $code_data['id'];
    $account_type = $code_data['account_type'];
    $package_price = $code_data['price'];
    $points_to_award = $code_data['points_value'];

    $stmt = $mysqli->prepare("SELECT id FROM genealogy_tree WHERE upline_id = ? AND position = ? FOR UPDATE");
    $stmt->bind_param("is", $upline_id, $position);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) { throw new Exception("This position in the network was just filled. Please try again."); }
    $stmt->close();

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $stmt_user = $mysqli->prepare("INSERT INTO users (name, username, email, password, role, account_type) VALUES (?, ?, ?, ?, 'member', ?)");
    $stmt_user->bind_param("sssss", $name, $username, $email, $hashed_password, $account_type);
    $stmt_user->execute();
    $new_user_id = $mysqli->insert_id;
    if ($new_user_id <= 0) { throw new Exception("Failed to create the user account."); }
    $stmt_user->close();

    $initial_debt = ($account_type === 'CD Account') ? $package_price : 0.00;
    $stmt_profile = $mysqli->prepare("INSERT INTO member_profiles (user_id, debt_balance) VALUES (?, ?)");
    $stmt_profile->bind_param("id", $new_user_id, $initial_debt);
    $stmt_profile->execute();
    $stmt_profile->close();

    $stmt_code = $mysqli->prepare("UPDATE activation_codes SET status = 'used', used_by_id = ?, used_at = NOW() WHERE id = ?");
    $stmt_code->bind_param("ii", $new_user_id, $activation_code_id);
    $stmt_code->execute();
    if ($stmt_code->affected_rows !== 1) { throw new Exception("Failed to update the activation code status."); }
    $stmt_code->close();

    $stmt_tree = $mysqli->prepare("INSERT INTO genealogy_tree (user_id, sponsor_id, upline_id, position) VALUES (?, ?, ?, ?)");
    $stmt_tree->bind_param("iiis", $new_user_id, $sponsor_id, $upline_id, $position);
    $stmt_tree->execute();
    $stmt_tree->close();

    if ($account_type === 'Paid Account') {
        $current_upline_id = $upline_id;
        $current_position = $position;
        $safety_counter = 0;
        while ($current_upline_id > 0 && $safety_counter < 50) {
            $stmt_points = $mysqli->prepare("INSERT INTO binary_points (user_id, points, position, source_user_id) VALUES (?, ?, ?, ?)");
            $stmt_points->bind_param("iisi", $current_upline_id, $points_to_award, $current_position, $new_user_id);
            $stmt_points->execute();
            $stmt_points->close();

            $stmt_next = $mysqli->prepare("SELECT upline_id, position FROM genealogy_tree WHERE user_id = ?");
            $stmt_next->bind_param("i", $current_upline_id);
            $stmt_next->execute();
            $next_result = $stmt_next->get_result();
            if ($next_upline = $next_result->fetch_assoc()) {
                $current_upline_id = $next_upline['upline_id'];
                $current_position = $next_upline['position'];
            } else {
                $current_upline_id = 0;
            }
            $stmt_next->close();
            $safety_counter++;
        }
    }

    if (!empty($beneficiaries)) {
        $tg_conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, 'kasikas_official');
        if ($tg_conn->connect_error) throw new Exception("Could not connect to Tribute Grace system.");

        $tg_conn->begin_transaction();
        try {
            $agentReferralCode = 'AGN9999';
            $nameParts = explode(' ', $name);
            $firstName = $nameParts[0];
            $lastName = end($nameParts);
            
            $tg_middleName = $_POST['tg_middleName'] ?? null;
            $tg_suffix = $_POST['tg_suffix'] ?? null;
            $tg_birthdate = $_POST['tg_birthdate'] ?? null;
            $tg_gender = $_POST['tg_gender'] ?? null;
            $tg_maritalStatus = $_POST['tg_maritalStatus'] ?? null;
            $tg_contactNumber1 = $_POST['tg_contactNumber1'] ?? null;
            $tg_province = $_POST['tg_province'] ?? null;
            $tg_cityMunicipal = $_POST['tg_cityMunicipal'] ?? null;
            $tg_barangay = $_POST['tg_barangay'] ?? null;
            $tg_street = $_POST['tg_street'] ?? null;
            $tg_lotBlock = $_POST['tg_lotBlock'] ?? null;

            $sql_tg_policy = "INSERT INTO policies (first_name, last_name, middleName, suffix, birthdate, gender, maritalStatus, contactNumber1, province, cityMunicipal, subdivisionBarangay, street, lotBlockPurok, email, plan, status, agentReferralCode) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'family', 'pending', ?)";
            $stmt_tg_policy = $tg_conn->prepare($sql_tg_policy);
            $stmt_tg_policy->bind_param("sssssssssssssss", $firstName, $lastName, $tg_middleName, $tg_suffix, $tg_birthdate, $tg_gender, $tg_maritalStatus, $tg_contactNumber1, $tg_province, $tg_cityMunicipal, $tg_barangay, $tg_street, $tg_lotBlock, $email, $agentReferralCode);
            $stmt_tg_policy->execute();
            $tg_policy_id = $tg_conn->insert_id;
            if ($tg_policy_id <= 0) throw new Exception("Failed to create Tribute Grace policy.");
            $stmt_tg_policy->close();

            $sql_tg_ben = "INSERT INTO beneficiaries (policy_id, first_name, last_name, relation, birthdate) VALUES (?, ?, ?, ?, ?)";
            $stmt_tg_ben = $tg_conn->prepare($sql_tg_ben);
            foreach ($beneficiaries as $ben_data) {
                $ben_fn = trim($ben_data['firstName']);
                $ben_ln = trim($ben_data['lastName']);
                $ben_rel = trim($ben_data['relation']);
                $ben_bday = trim($ben_data['birthdate']);
                if (!empty($ben_fn) && !empty($ben_ln)) {
                    $stmt_tg_ben->bind_param("issss", $tg_policy_id, $ben_fn, $ben_ln, $ben_rel, $ben_bday);
                    $stmt_tg_ben->execute();
                }
            }
            $stmt_tg_ben->close();
            $tg_conn->commit();
            $tg_conn->close();

            $stmt_update_rmg = $mysqli->prepare("UPDATE users SET is_tg_member = 1 WHERE id = ?");
            $stmt_update_rmg->bind_param("i", $new_user_id);
            $stmt_update_rmg->execute();
            $stmt_update_rmg->close();
            
            $stmt_cache_ben = $mysqli->prepare("INSERT INTO tg_beneficiaries (rmg_policy_holder_id, first_name, last_name) VALUES (?, ?, ?)");
            foreach ($beneficiaries as $ben_data) {
                 $ben_fn = trim($ben_data['firstName']);
                 $ben_ln = trim($ben_data['lastName']);
                 if (!empty($ben_fn) && !empty($ben_ln)) {
                    $stmt_cache_ben->bind_param("iss", $new_user_id, $ben_fn, $ben_ln);
                    $stmt_cache_ben->execute();
                 }
            }
            $stmt_cache_ben->close();

        } catch (Exception $e) {
            $tg_conn->rollback();
            $tg_conn->close();
            throw $e;
        }
    }

    $mysqli->commit();
    
    $_SESSION['success'] = 'Registration successful! You can now log in.';
    header("Location: login.php");
    exit();

} catch (Exception $e) {
    $mysqli->rollback();
    redirectWithError($error_url, "Registration failed: " . $e->getMessage());
}

$mysqli->close();
?>