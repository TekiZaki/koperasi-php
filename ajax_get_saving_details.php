<?php
// ajax_get_saving_details.php
session_start();
header('Content-Type: application/json');

// Ensure only authenticated users can access
if (!isset($_SESSION['user_id'])) {
    http_response_code(401); // Unauthorized
    echo json_encode(['error' => 'Unauthorized access.']);
    exit();
}

// Basic security checks for GET parameters
if (!isset($_GET['member_name']) || empty($_GET['member_name'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Bad Request: Member name is required.']);
    exit();
}

require_once 'config/Database.php';
require_once 'models/Saving.php';

$database = new Database();
$db = $database->getConnection();
$saving = new Saving($db);

// Sanitize member name from GET parameter.
// The model's readByMemberName() method will further sanitize it for the database query.
$saving->member_name = urldecode($_GET['member_name']);

// Fetch saving details for the member
// This method handles database interaction and internal sanitization.
$stmt = $saving->readByMemberName();

$savings_details = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Sanitize output data to prevent XSS before sending as JSON
// This is crucial, especially if descriptions or names could contain user-generated content.
$sanitized_savings_details = array_map(function($s) {
    return array_map('htmlspecialchars', $s);
}, $savings_details);

// Return as JSON, even if it's an empty array
echo json_encode($sanitized_savings_details);
?>
