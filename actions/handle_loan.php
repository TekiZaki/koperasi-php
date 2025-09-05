<?php
// actions/handle_loan.php
session_start();
require_once '../config/Database.php';
require_once '../models/Loan.php';

// Authorization check: Must be admin or superadmin
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'superadmin'])) {
    error_log("Unauthorized loan action attempt by user ID: " . ($_SESSION['user_id'] ?? 'N/A') . " with role: " . ($_SESSION['user_role'] ?? 'N/A'));
    header("Location: ../index.php?page=loans&error=unauthorized");
    exit();
}

// CSRF Protection: Verify token for POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        error_log("CSRF token mismatch for loan action from user ID: " . $_SESSION['user_id']);
        header("Location: ../index.php?page=loans&error=csrf_mismatch");
        exit();
    }
}

$database = new Database();
$db = $database->getConnection();
$loan = new Loan($db);

// Sanitize and validate action
$action = filter_input(INPUT_POST, 'action', FILTER_SANITIZE_STRING);

try {
    if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        // Input validation and sanitization
        $loan->loan_date = filter_input(INPUT_POST, 'loan_date', FILTER_SANITIZE_STRING);
        $loan->member_name = filter_input(INPUT_POST, 'member_name', FILTER_SANITIZE_STRING);
        $loan->loan_amount = filter_input(INPUT_POST, 'loan_amount', FILTER_VALIDATE_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        $loan->tenor_months = filter_input(INPUT_POST, 'tenor_months', FILTER_VALIDATE_INT);

        // Basic validation
        if (empty($loan->loan_date) || empty($loan->member_name) || !is_numeric($loan->loan_amount) || $loan->loan_amount <= 0 || !is_numeric($loan->tenor_months) || $loan->tenor_months <= 0) {
            throw new Exception("Invalid input for creating loan.");
        }

        $loan->status = 'aktif'; // New loans are always aktif
        $loan->created_by_user_id = $_SESSION['user_id'];
        $loan->create();
    } elseif ($action === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $loan->id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        $loan->loan_date = filter_input(INPUT_POST, 'loan_date', FILTER_SANITIZE_STRING);
        $loan->member_name = filter_input(INPUT_POST, 'member_name', FILTER_SANITIZE_STRING);
        $loan->loan_amount = filter_input(INPUT_POST, 'loan_amount', FILTER_VALIDATE_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        $loan->tenor_months = filter_input(INPUT_POST, 'tenor_months', FILTER_VALIDATE_INT);
        $loan->status = filter_input(INPUT_POST, 'status', FILTER_SANITIZE_STRING);

        // Basic validation
        if (empty($loan->id) || empty($loan->loan_date) || empty($loan->member_name) || !is_numeric($loan->loan_amount) || $loan->loan_amount <= 0 || !is_numeric($loan->tenor_months) || $loan->tenor_months <= 0 || !in_array($loan->status, ['aktif', 'selesai', 'gagal'])) {
            throw new Exception("Invalid input for updating loan.");
        }

        if ($loan->update()) {
            // After updating, re-check the status in case the loan amount changed
            $loan->updateLoanStatus($loan->id); // Use the validated ID
        }

    } elseif ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $loan->id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

        // Basic validation
        if (empty($loan->id)) {
            throw new Exception("Invalid ID for deleting loan.");
        }
        $loan->delete();
    } else {
        throw new Exception("Invalid action or request method.");
    }
} catch (Exception $e) {
    error_log("Loan action failed for user ID: " . $_SESSION['user_id'] . ". Error: " . $e->getMessage());
    header("Location: ../index.php?page=loans&status=error&message=" . urlencode($e->getMessage()));
    exit();
}

header("Location: ../index.php?page=loans&status=success");
exit();
