<?php
header('Content-Type: application/json');
require_once '../src/includes/db_connect.php';

$tg_conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, 'kasikas_official');
if ($tg_conn->connect_error) {
    echo json_encode(['eligible' => false, 'reason' => 'System Error.', 'type' => 'system_error']);
    exit();
}

$response = ['eligible' => false, 'reason' => 'Invalid input.', 'type' => 'input_error'];
$firstName = isset($_POST['firstName']) ? trim($_POST['firstName']) : '';
$lastName = isset($_POST['lastName']) ? trim($_POST['lastName']) : '';

if (!empty($firstName) && !empty($lastName)) {
    // Check 1: Does this person already have an RMG account that is a TG member?
    // This checks for the "multiple accounts" rule within RMG.
    $stmt_rmg = $mysqli->prepare("SELECT id FROM users WHERE name LIKE ? AND is_tg_member = 1");
    $searchName = "%" . $firstName . "%" . $lastName . "%";
    $stmt_rmg->bind_param("s", $searchName);
    $stmt_rmg->execute();
    if ($stmt_rmg->get_result()->num_rows > 0) {
        $response['type'] = 'multiple_rmg_accounts';
        $response['reason'] = 'An existing RMG account with this name already has a Tribute Grace policy.';
        echo json_encode($response); exit();
    }
    $stmt_rmg->close();

    // Check 2: Is this person already a policyholder in the TG database?
    $stmt_tg_policy = $tg_conn->prepare("SELECT id FROM policies WHERE first_name = ? AND last_name = ?");
    $stmt_tg_policy->bind_param("ss", $firstName, $lastName);
    $stmt_tg_policy->execute();
    if ($stmt_tg_policy->get_result()->num_rows > 0) {
        $response['type'] = 'is_policy_holder';
        $response['reason'] = 'This name is already registered as a Tribute Grace policy holder.';
        echo json_encode($response); exit();
    }
    $stmt_tg_policy->close();

    // Check 3: Is this person already a beneficiary in the TG database?
    $stmt_tg_ben = $tg_conn->prepare("SELECT p.first_name as holder_fn, p.last_name as holder_ln FROM beneficiaries b JOIN policies p ON b.policy_id = p.id WHERE b.first_name = ? AND b.last_name = ?");
    $stmt_tg_ben->bind_param("ss", $firstName, $lastName);
    $stmt_tg_ben->execute();
    if ($ben_row = $stmt_tg_ben->get_result()->fetch_assoc()) {
        $policy_holder_name = trim($ben_row['holder_fn'] . ' ' . $ben_row['holder_ln']);
        $response['type'] = 'is_beneficiary';
        $response['reason'] = "Not eligible: Already a beneficiary under policy holder '" . htmlspecialchars($policy_holder_name) . "'.";
        echo json_encode($response); exit();
    }
    $stmt_tg_ben->close();

    // If all checks pass, they are eligible!
    $response['eligible'] = true;
    $response['reason'] = 'Eligible for Tribute Grace Plan.';
    $response['type'] = 'eligible';
}

echo json_encode($response);
$mysqli->close();
$tg_conn->close();
?>