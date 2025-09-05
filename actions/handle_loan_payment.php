<?php
// actions/handle_loan_payment.php
session_start();
require_once '../config/Database.php';
require_once '../models/Loan.php';

// Authorization check: Must be admin or superadmin
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'superadmin'])) {
    error_log("Unauthorized loan payment action attempt by user ID: " . ($_SESSION['user_id'] ?? 'N/A') . " with role: " . ($_SESSION['user_role'] ?? 'N/A'));
    header("Location: ../index.php?page=loans&error=unauthorized");
    exit();
}

// Check if it's a POST request with the required loan_id
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../index.php?page=loans&status=error&message=" . urlencode("Invalid request method."));
    exit();
}

// CSRF Protection: Verify token
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    error_log("CSRF token mismatch for loan payment action from user ID: " . $_SESSION['user_id']);
    header("Location: ../index.php?page=loans&error=csrf_mismatch");
    exit();
}

// Input validation and sanitization
$loan_id = filter_input(INPUT_POST, 'loan_id', FILTER_VALIDATE_INT);
$payment_date = filter_input(INPUT_POST, 'payment_date', FILTER_SANITIZE_STRING);
$payment_amount = filter_input(INPUT_POST, 'payment_amount', FILTER_VALIDATE_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
$payment_month_no = filter_input(INPUT_POST, 'payment_month_no', FILTER_VALIDATE_INT);
$payment_description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING);

// Basic validation for required fields
if (empty($loan_id) || empty($payment_date) || !is_numeric($payment_amount) || $payment_amount <= 0) {
    header("Location: ../index.php?page=loans&status=error&message=" . urlencode("Invalid payment details provided."));
    exit();
}

$database = new Database();
$db = $database->getConnection();
$loan = new Loan($db);

try {
    // Assign validated/sanitized POST data to the model properties for a new payment
    $loan->id = $loan_id; // The 'id' property of the model is used for loan_id context
    $loan->payment_date = $payment_date;
    $loan->payment_amount = $payment_amount;
    $loan->payment_month_no = ($payment_month_no !== false && $payment_month_no > 0) ? $payment_month_no : null; // Ensure it's a valid integer or null
    $loan->payment_description = $payment_description;
    $loan->created_by_user_id = $_SESSION['user_id'];

    if ($loan->addPayment()) {
        // If payment is added successfully, update the loan's main status (e.g., to 'selesai')
        $loan->updateLoanStatus($loan_id);
    } else {
        throw new Exception("Failed to add payment.");
    }

} catch (Exception $e) {
    error_log("Loan payment action failed for user ID: " . $_SESSION['user_id'] . ". Error: " . $e->getMessage());
    header("Location: ../index.php?page=loans&status=error&message=" . urlencode($e->getMessage()));
    exit();
}

header("Location: ../index.php?page=loans&status=success");
exit();
