<?php
// ajax_get_loan_details.php
session_start();
header('Content-Type: application/json');

// Ensure only authenticated users can access
if (!isset($_SESSION['user_id'])) {
    http_response_code(401); // Unauthorized
    echo json_encode(['error' => 'Unauthorized access.']);
    exit();
}

// Basic security checks for GET parameters
if (!isset($_GET['loan_id']) || !is_numeric($_GET['loan_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Bad Request: Invalid loan ID.']);
    exit();
}

require_once 'config/Database.php';
require_once 'models/Loan.php';

$database = new Database();
$db = $database->getConnection();
$loan = new Loan($db);

$loan->id = intval($_GET['loan_id']);

// Fetch loan details
// Using readOne() directly leverages the model's existing sanitization for the ID.
$loan_stmt = $loan->readOne();
if ($loan_stmt->rowCount() == 0) {
    http_response_code(404);
    echo json_encode(['error' => 'Loan not found.']);
    exit();
}
$loan_details = $loan_stmt->fetch(PDO::FETCH_ASSOC);

// IMPORTANT: Implement authorization check here.
// Only allow users to see loans they are associated with or if they are an admin.
// For now, assuming any authenticated user can view any loan detail.
// In a real application, you might check if $_SESSION['user_id'] is the loan creator or a related member.
// Or if $_SESSION['user_role'] is 'admin' or 'superadmin'.
// Example: if (!isAdmin() && $loan_details['created_by_user_id'] !== $_SESSION['user_id']) { /* ... deny ... */ }

// Fetch payment history
// Using readLoanPayments() directly leverages the model's existing sanitization for the ID.
$payments_stmt = $loan->readLoanPayments();
$payments = $payments_stmt->fetchAll(PDO::FETCH_ASSOC);

// Sanitize output data to prevent XSS before sending as JSON
// This is crucial, especially if descriptions or names could contain user-generated content.
$sanitized_loan_details = array_map('htmlspecialchars', $loan_details);
$sanitized_payments = array_map(function($payment) {
    return array_map('htmlspecialchars', $payment);
}, $payments);


// Combine and return as JSON
echo json_encode([
    'loan' => $sanitized_loan_details,
    'payments' => $sanitized_payments
]);
?>
