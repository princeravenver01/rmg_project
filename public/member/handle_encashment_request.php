<?php
session_start();
require_once '../../src/includes/db_connect.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'member' || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../login.php'); exit();
}

$member_id = $_SESSION['user_id'];
$requested_amount = isset($_POST['amount']) ? (float)$_POST['amount'] : 0;

if ($requested_amount <= 0) {
    $_SESSION['message'] = "Invalid withdrawal amount entered.";
    $_SESSION['msg_type'] = "danger";
    header('Location: request_encashment.php');
    exit();
}

// Fetch Wallet Balance and Debt Balance
$stmt_bal = $mysqli->prepare("SELECT w.balance, mp.debt_balance FROM wallets w LEFT JOIN member_profiles mp ON w.user_id = mp.user_id WHERE w.user_id = ?");
$stmt_bal->bind_param("i", $member_id);
$stmt_bal->execute();
$result = $stmt_bal->get_result();
$balance = 0.00;
$debt_balance = 0.00;
if ($row = $result->fetch_assoc()) {
    $balance = (float)$row['balance'];
    $debt_balance = (float)($row['debt_balance'] ?? 0.00);
}
$stmt_bal->close();

if ($requested_amount > $balance) {
    $_SESSION['message'] = "Insufficient balance.";
    $_SESSION['msg_type'] = "danger";
    header('Location: request_encashment.php');
    exit();
}

// Credit Deduction Logic (10% of cash withdrawal)
$amount_for_payout = $requested_amount;
$amount_for_debt = 0.00;
$debt_is_now_paid = false;

if ($debt_balance > 0) {
    $deduction_payment = $requested_amount * 0.10;
    $amount_for_debt = min($deduction_payment, $debt_balance);
    $amount_for_payout = $requested_amount - $amount_for_debt;
    if (($debt_balance - $amount_for_debt) < 0.01) {
        $debt_is_now_paid = true;
    }
}

// Start Transaction
$mysqli->begin_transaction();
try {
    // 1. Deduct full requested amount from the wallet
    $stmt_wallet = $mysqli->prepare("UPDATE wallets SET balance = balance - ? WHERE user_id = ?");
    $stmt_wallet->bind_param("di", $requested_amount, $member_id);
    $stmt_wallet->execute();
    if ($stmt_wallet->affected_rows !== 1) throw new Exception("Wallet balance update failed.");
    $stmt_wallet->close();
    
    // 2. Insert encashment request for the amount member receives
    $stmt_req = $mysqli->prepare("INSERT INTO encashment_requests (user_id, amount) VALUES (?, ?)");
    $stmt_req->bind_param("id", $member_id, $amount_for_payout);
    $stmt_req->execute();
    $encashment_request_id = $mysqli->insert_id;
    if ($encashment_request_id <= 0) throw new Exception("Failed to create encashment request.");
    $stmt_req->close();

    // 3. Process debt deduction if applicable
    if ($amount_for_debt > 0) {
        $stmt_debt = $mysqli->prepare("UPDATE member_profiles SET debt_balance = debt_balance - ? WHERE user_id = ?");
        $stmt_debt->bind_param("di", $amount_for_debt, $member_id);
        $stmt_debt->execute();
        $stmt_debt->close();
        
        $stmt_log = $mysqli->prepare("INSERT INTO debt_payments (user_id, encashment_request_id, amount_deducted) VALUES (?, ?, ?)");
        $stmt_log->bind_param("iid", $member_id, $encashment_request_id, $amount_for_debt);
        $stmt_log->execute();
        $stmt_log->close();
    }
    
    // 4. Award points IF the debt is now fully paid
    if ($debt_is_now_paid) {
        $stmt_info = $mysqli->prepare("
            SELECT gt.upline_id, gt.position, p.points_value
            FROM genealogy_tree gt
            JOIN activation_codes ac ON gt.user_id = ac.used_by_id
            JOIN packages p ON ac.package_id = p.id
            WHERE gt.user_id = ? LIMIT 1
        ");
        $stmt_info->bind_param("i", $member_id);
        $stmt_info->execute();
        $info_res = $stmt_info->get_result();
        if ($info = $info_res->fetch_assoc()) {
            $upline_id = $info['upline_id'];
            $position = $info['position'];
            $points_to_award = $info['points_value'];
            
            $current_upline_id = $upline_id;
            $current_position = $position;
            $safety_counter = 0;
            while ($current_upline_id > 0 && $safety_counter < 50) {
                $stmt_points = $mysqli->prepare("INSERT INTO binary_points (user_id, points, position, source_user_id) VALUES (?, ?, ?, ?)");
                $stmt_points->bind_param("iisi", $current_upline_id, $points_to_award, $current_position, $member_id);
                $stmt_points->execute();
                $stmt_points->close();
                
                $stmt_next = $mysqli->prepare("SELECT upline_id, position FROM genealogy_tree WHERE user_id = ?");
                $stmt_next->bind_param("i", $current_upline_id);
                $stmt_next->execute();
                if ($next_upline = $stmt_next->get_result()->fetch_assoc()) {
                    $current_upline_id = $next_upline['upline_id'];
                    $current_position = $next_upline['position'];
                } else { $current_upline_id = 0; }
                $stmt_next->close();
                $safety_counter++;
            }
        }
        $stmt_info->close();

        // 5. Update the user's account type to 'Paid Account'
        $stmt_update_user = $mysqli->prepare("UPDATE users SET account_type = 'Paid Account' WHERE id = ?");
        $stmt_update_user->bind_param("i", $member_id);
        $stmt_update_user->execute();
        $stmt_update_user->close();
    }
    
    $mysqli->commit();
    
    $success_message = "Your encashment request for ₱" . number_format($amount_for_payout, 2) . " has been submitted.";
    if ($amount_for_debt > 0) {
        $success_message .= " A payment of ₱" . number_format($amount_for_debt, 2) . " was applied to your CD Account.";
    }
    if ($debt_is_now_paid) {
        $success_message .= " Congratulations, your account is now fully paid! Points have been awarded to your upline.";
    }
    $_SESSION['message'] = $success_message;
    $_SESSION['msg_type'] = "success";
    header('Location: ewallet.php');
    exit();

} catch (Exception $e) {
    $mysqli->rollback();
    $_SESSION['message'] = "A critical error occurred: " . $e->getMessage();
    $_SESSION['msg_type'] = "danger";
    header('Location: request_encashment.php');
    exit();
}

$mysqli->close();
?>