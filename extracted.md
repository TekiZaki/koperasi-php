# Code Dump for koperasi-php

**Selected Extensions:** .js, .php, .sql
**Ignored Folders:** .git, .vscode, **pycache**, bin, build, dist, node_modules, obj, out, target

---

## koperasi-php/ajax_get_loan_details.php

```php
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

```

## koperasi-php/ajax_get_saving_details.php

```php
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

```

## koperasi-php/crud.php

```php
<?php
// crud.php
require_once "config/Database.php";

$db = new Database();
$conn = $db->getConnection();

// Handle Add User
if (isset($_POST['add'])) {
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
    $name = $_POST['name'];
    $role = $_POST['role'];

    $stmt = $conn->prepare("INSERT INTO users (username, password, name, role) VALUES (?, ?, ?, ?)");
    $stmt->execute([$username, $password, $name, $role]);
    // Redirect back to crud.php after adding
    header("Location: crud.php");
    exit;
}

// Handle Edit User
if (isset($_POST['edit'])) {
    $id = $_POST['id'];
    $username = $_POST['username'];
    $name = $_POST['name'];
    $role = $_POST['role'];

    if (!empty($_POST['password'])) {
        $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
        $stmt = $conn->prepare("UPDATE users SET username=?, password=?, name=?, role=? WHERE id=?");
        $stmt->execute([$username, $password, $name, $role, $id]);
    } else {
        $stmt = $conn->prepare("UPDATE users SET username=?, name=?, role=? WHERE id=?");
        $stmt->execute([$username, $name, $role, $id]);
    }

    // Redirect back to crud.php after editing
    header("Location: crud.php");
    exit;
}

// Handle Delete User
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM users WHERE id=?");
    $stmt->execute([$id]);
    // Redirect back to crud.php after deleting
    header("Location: crud.php");
    exit;
}

// Fetch all users
$stmt = $conn->query("SELECT * FROM users ORDER BY id ASC");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management</title>
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Favicon -->
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='80' font-size='80'>👥</text></svg>">
</head>
<body class="bg-light">

<div class="container py-4">
    <h2 class="mb-4 text-center">👥 User Management</h2>

    <!-- Add User Form -->
    <div class="card mb-4 shadow-sm">
        <div class="card-header bg-primary text-white">Add New User</div>
        <div class="card-body">
            <form method="POST" class="row g-3">
                <div class="col-md-3">
                    <input type="text" name="username" class="form-control" placeholder="Username" required>
                </div>
                <div class="col-md-3">
                    <input type="password" name="password" class="form-control" placeholder="Password" required>
                </div>
                <div class="col-md-3">
                    <input type="text" name="name" class="form-control" placeholder="Full Name" required>
                </div>
                <div class="col-md-2">
                    <select name="role" class="form-select">
                        <option value="user">User</option>
                        <option value="admin">Admin</option>
                        <option value="superadmin">Superadmin</option>
                    </select>
                </div>
                <div class="col-md-1 d-grid">
                    <button type="submit" name="add" class="btn btn-success">Add</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Users Table -->
    <div class="card shadow-sm">
        <div class="card-header bg-dark text-white">Users List</div>
        <div class="card-body table-responsive">
            <table class="table table-bordered table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Name</th>
                        <th>Role</th>
                        <th>Created At</th>
                        <th style="min-width: 200px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                    <tr>
                        <form method="POST" class="row g-2">
                            <td><?= htmlspecialchars($user['id']) ?></td>
                            <td>
                                <input type="text" name="username" class="form-control form-control-sm" value="<?= htmlspecialchars($user['username']) ?>" required>
                            </td>
                            <td>
                                <input type="text" name="name" class="form-control form-control-sm" value="<?= htmlspecialchars($user['name']) ?>" required>
                            </td>
                            <td>
                                <select name="role" class="form-select form-select-sm">
                                    <option value="user" <?= $user['role']=='user'?'selected':'' ?>>User</option>
                                    <option value="admin" <?= $user['role']=='admin'?'selected':'' ?>>Admin</option>
                                    <option value="superadmin" <?= $user['role']=='superadmin'?'selected':'' ?>>Superadmin</option>
                                </select>
                            </td>
                            <td><?= htmlspecialchars($user['created_at']) ?></td>
                            <td>
                                <div class="d-flex flex-wrap gap-1">
                                    <input type="password" name="password" class="form-control form-control-sm" placeholder="New Password (optional)">
                                    <input type="hidden" name="id" value="<?= $user['id'] ?>">
                                    <button type="submit" name="edit" class="btn btn-warning btn-sm">Update</button>
                                    <a href="crud.php?delete=<?= $user['id'] ?>" onclick="return confirm('Delete this user?');" class="btn btn-danger btn-sm">Delete</a>
                                </div>
                            </td>
                        </form>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
```

## koperasi-php/db.sql

```sql
DROP DATABASE IF EXISTS koperasi_php;
CREATE DATABASE koperasi_php;
USE koperasi_php;

CREATE TABLE users (
    id INT(11) PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    name VARCHAR(100) NOT NULL,
    role ENUM('user', 'admin', 'superadmin') NOT NULL DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO users (username, password, name, role) VALUES ('superadmin', '$2y$10$Tmc15tjIG5QSJ4RBuiAFPe0Hsci8nLj9PFq04VM.CajBoFCFSeRCy', 'Super Admin Koperasi', 'superadmin');
-- Password untuk superadmin: "testdzaki231203test". Ganti ini di produksi!
-- Anda harus menghasilkan hash password sendiri dengan password_hash('testdzaki231203test', PASSWORD_BCRYPT)

CREATE TABLE transactions (
    id INT(11) PRIMARY KEY AUTO_INCREMENT,
    transaction_date DATE NOT NULL,
    name VARCHAR(100) NULL,
    description TEXT NOT NULL,
    type ENUM('pemasukan', 'pengeluaran') NOT NULL,
    amount DECIMAL(15, 2) NOT NULL,
    created_by_user_id INT(11) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by_user_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE loans (
    id INT(11) PRIMARY KEY AUTO_INCREMENT,
    member_name VARCHAR(100) NOT NULL,
    loan_amount DECIMAL(15, 2) NOT NULL,
    loan_date DATE NOT NULL,
    tenor_months INT(11) NOT NULL, -- Changed from INT(2) and removed DEFAULT 10
    status ENUM('aktif', 'selesai', 'gagal') NOT NULL DEFAULT 'aktif',
    created_by_user_id INT(11) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by_user_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE loan_payments (
    id INT(11) PRIMARY KEY AUTO_INCREMENT,
    loan_id INT(11) NOT NULL,
    payment_date DATE NOT NULL,
    payment_amount DECIMAL(15, 2) NOT NULL,
    payment_month_no INT(2) NULL,
    description TEXT NULL,
    created_by_user_id INT(11) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (loan_id) REFERENCES loans(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by_user_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE savings (
    id INT(11) PRIMARY KEY AUTO_INCREMENT,
    member_name VARCHAR(100) NOT NULL,
    saving_type ENUM('wajib', 'sukarela') NOT NULL,
    saving_date DATE NOT NULL,
    amount DECIMAL(15, 2) NOT NULL,
    description TEXT NULL,
    created_by_user_id INT(11) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by_user_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE infaqs (
    id INT(11) PRIMARY KEY AUTO_INCREMENT,
    infaq_date DATE NOT NULL,
    description TEXT NOT NULL,
    donor_name VARCHAR(100) NULL,
    amount DECIMAL(15, 2) NOT NULL,
    type ENUM('pemasukan', 'pengeluaran') NOT NULL DEFAULT 'pemasukan',
    created_by_user_id INT(11) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by_user_id) REFERENCES users(id) ON DELETE SET NULL
);

```

## koperasi-php/index.php

```php
<?php
// index.php
session_start();

// Autoload models
spl_autoload_register(function ($class_name) {
    $file = __DIR__ . '/models/' . $class_name . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

require_once 'config/Database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Security: Regenerate session ID to prevent Session Fixation
if (!isset($_SESSION['initiated'])) {
    session_regenerate_id(true);
    $_SESSION['initiated'] = true;
}

// Security: Set a session timeout
$session_timeout = 1800; // 30 minutes (in seconds)
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $session_timeout)) {
    session_unset();
    session_destroy();
    header("Location: login.php?timeout=1");
    exit();
}
$_SESSION['last_activity'] = time(); // Update last activity time

$database = new Database();
$db = $database->getConnection();

// Validate and sanitize the 'page' GET parameter
$allowed_pages = ['dashboard', 'transactions', 'savings', 'loans', 'infaq'];
$page = $_GET['page'] ?? 'dashboard';

if (!in_array($page, $allowed_pages)) {
    // If an invalid page is requested, default to dashboard or show an error
    $page = 'dashboard';
    // Optionally, log this attempt or redirect with an error message
    // error_log("Invalid page requested: " . ($_GET['page'] ?? ''));
}

include 'includes/header.php';

// Page routing
// The includes below are safe because $page has been validated against a whitelist.
switch ($page) {
    case 'transactions':
        include 'pages/transactions.php';
        break;
    case 'savings':
        include 'pages/savings.php';
        break;
    case 'loans':
        include 'pages/loans.php';
        break;
    case 'infaq':
        include 'pages/infaq.php';
        break;
    case 'dashboard':
    default:
        include 'pages/dashboard.php';
        break;
}

include 'includes/footer.php';
?>

```

## koperasi-php/login.php

```php
<?php
// login.php
session_start();
require_once 'config/Database.php';
require_once 'models/User.php';

// If user is already logged in, redirect to dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF protection: Add a token check if forms are protected.
    // For a simple login, it's less critical, but good practice for other forms.

    $database = new Database();
    $db = $database->getConnection();
    $user = new User($db);

    // Sanitize user input immediately
    $username_input = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
    $password_input = $_POST['password']; // Password cannot be sanitized, only validated/hashed

    // Validate input (e.g., length, character set)
    if (empty($username_input) || empty($password_input)) {
        $error_message = "Username dan password harus diisi.";
    } else {
        $user->username = $username_input;
        $stmt = $user->findByUsername();

        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            // Verify password using password_verify for hashed passwords
            if (password_verify($password_input, $row['password'])) {
                // Login successful

                // Security: Regenerate session ID upon successful login to prevent Session Fixation
                session_regenerate_id(true);

                $_SESSION['user_id'] = $row['id'];
                $_SESSION['user_name'] = htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8'); // Sanitize name before storing in session
                $_SESSION['user_role'] = htmlspecialchars($row['role'], ENT_QUOTES, 'UTF-8'); // Sanitize role
                $_SESSION['last_activity'] = time(); // For session timeout
                $_SESSION['initiated'] = true; // Mark session as initiated

                header("Location: index.php");
                exit();
            } else {
                $error_message = "Password salah.";
                // Security: Generic error messages prevent username enumeration
            }
        } else {
            $error_message = "Username tidak ditemukan.";
            // Security: Generic error messages prevent username enumeration
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Koperasi Masjid</title>
    <link rel="icon" type="image/x-icon" href="assets/logo.png" />
    <!-- FIX: Link to the main stylesheet -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<!-- FIX: Add class to body for specific styling -->
<body class="login-page">
    <div class="login-container">
        <h1>Selamat Datang</h1>
        <p>Silakan login untuk melanjutkan</p>
        <?php if (!empty($error_message)): ?>
            <!-- FIX: Use the error-message class from the main stylesheet -->
            <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>
        <form action="login.php" method="POST">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required autocomplete="username">
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required autocomplete="current-password">
            </div>
            <button type="submit">Login</button>
        </form>
    </div>
</body>
</html>

```

## koperasi-php/logout.php

```php
<?php
session_start();
session_unset(); // Unset all session variables
session_destroy(); // Destroy the session

// Security: Clear any session cookies
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

header("Location: login.php");
exit();
?>

```

## koperasi-php/actions/handle_infaq.php

```php
<?php
// actions/handle_infaq.php
session_start();
require_once '../config/Database.php';
require_once '../models/Infaq.php';

// Authorization check: Must be admin or superadmin
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'superadmin'])) {
    // Log unauthorized attempt
    error_log("Unauthorized infaq action attempt by user ID: " . ($_SESSION['user_id'] ?? 'N/A') . " with role: " . ($_SESSION['user_role'] ?? 'N/A'));
    header("Location: ../index.php?page=infaq&error=unauthorized");
    exit();
}

// CSRF Protection: Verify token for POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        // Log CSRF attempt
        error_log("CSRF token mismatch for infaq action from user ID: " . $_SESSION['user_id']);
        header("Location: ../index.php?page=infaq&error=csrf_mismatch");
        exit();
    }
}

$database = new Database();
$db = $database->getConnection();
$infaq = new Infaq($db);

// Sanitize and validate action
$action = filter_input(INPUT_POST, 'action', FILTER_SANITIZE_STRING);

try {
    if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        // Input validation and sanitization
        $infaq->infaq_date = filter_input(INPUT_POST, 'infaq_date', FILTER_SANITIZE_STRING);
        $infaq->donor_name = filter_input(INPUT_POST, 'donor_name', FILTER_SANITIZE_STRING);
        $infaq->description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING);
        $infaq->type = filter_input(INPUT_POST, 'type', FILTER_SANITIZE_STRING);
        $infaq->amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT, FILTER_FLAG_ALLOW_FRACTION);

        // Basic validation
        if (empty($infaq->infaq_date) || empty($infaq->description) || empty($infaq->type) || !is_numeric($infaq->amount) || $infaq->amount <= 0) {
            throw new Exception("Invalid input for creating infaq.");
        }
        if (!in_array($infaq->type, ['pemasukan', 'pengeluaran'])) {
            throw new Exception("Invalid infaq type.");
        }

        $infaq->created_by_user_id = $_SESSION['user_id'];
        $infaq->create();
    } elseif ($action === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $infaq->id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        $infaq->infaq_date = filter_input(INPUT_POST, 'infaq_date', FILTER_SANITIZE_STRING);
        $infaq->donor_name = filter_input(INPUT_POST, 'donor_name', FILTER_SANITIZE_STRING);
        $infaq->description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING);
        $infaq->type = filter_input(INPUT_POST, 'type', FILTER_SANITIZE_STRING);
        $infaq->amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT, FILTER_FLAG_ALLOW_FRACTION);

        // Basic validation
        if (empty($infaq->id) || empty($infaq->infaq_date) || empty($infaq->description) || empty($infaq->type) || !is_numeric($infaq->amount) || $infaq->amount <= 0) {
            throw new Exception("Invalid input for updating infaq.");
        }
        if (!in_array($infaq->type, ['pemasukan', 'pengeluaran'])) {
            throw new Exception("Invalid infaq type.");
        }

        $infaq->update();
    } elseif ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $infaq->id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

        // Basic validation
        if (empty($infaq->id)) {
            throw new Exception("Invalid ID for deleting infaq.");
        }
        $infaq->delete();
    } else {
        throw new Exception("Invalid action or request method.");
    }
} catch (Exception $e) {
    error_log("Infaq action failed for user ID: " . $_SESSION['user_id'] . ". Error: " . $e->getMessage());
    header("Location: ../index.php?page=infaq&status=error&message=" . urlencode($e->getMessage()));
    exit();
}

header("Location: ../index.php?page=infaq&status=success");
exit();
?>

```

## koperasi-php/actions/handle_loan.php

```php
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

```

## koperasi-php/actions/handle_loan_payment.php

```php
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

```

## koperasi-php/actions/handle_saving.php

```php
<?php
// actions/handle_saving.php
session_start();
require_once '../config/Database.php';
require_once '../models/Saving.php';

// Authorization check: Must be admin or superadmin
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'superadmin'])) {
    error_log("Unauthorized saving action attempt by user ID: " . ($_SESSION['user_id'] ?? 'N/A') . " with role: " . ($_SESSION['user_role'] ?? 'N/A'));
    header("Location: ../index.php?page=savings&error=unauthorized");
    exit();
}

// CSRF Protection: Verify token for POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        error_log("CSRF token mismatch for saving action from user ID: " . $_SESSION['user_id']);
        header("Location: ../index.php?page=savings&error=csrf_mismatch");
        exit();
    }
}

$database = new Database();
$db = $database->getConnection();
$saving = new Saving($db);

// Sanitize and validate action
$action = filter_input(INPUT_POST, 'action', FILTER_SANITIZE_STRING);

try {
    if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        // Input validation and sanitization
        $saving->saving_date = filter_input(INPUT_POST, 'saving_date', FILTER_SANITIZE_STRING);
        $saving->member_name = filter_input(INPUT_POST, 'member_name', FILTER_SANITIZE_STRING);
        $saving->saving_type = filter_input(INPUT_POST, 'saving_type', FILTER_SANITIZE_STRING);
        $saving->amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        $saving->description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING);

        // Basic validation
        if (empty($saving->saving_date) || empty($saving->member_name) || empty($saving->saving_type) || !is_numeric($saving->amount) || $saving->amount <= 0) {
            throw new Exception("Invalid input for creating saving.");
        }
        if (!in_array($saving->saving_type, ['wajib', 'sukarela'])) {
            throw new Exception("Invalid saving type.");
        }

        $saving->created_by_user_id = $_SESSION['user_id'];
        $saving->create();
    } elseif ($action === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $saving->id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        $saving->saving_date = filter_input(INPUT_POST, 'saving_date', FILTER_SANITIZE_STRING);
        $saving->member_name = filter_input(INPUT_POST, 'member_name', FILTER_SANITIZE_STRING);
        $saving->saving_type = filter_input(INPUT_POST, 'saving_type', FILTER_SANITIZE_STRING);
        $saving->amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        $saving->description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING);

        // Basic validation
        if (empty($saving->id) || empty($saving->saving_date) || empty($saving->member_name) || empty($saving->saving_type) || !is_numeric($saving->amount) || $saving->amount <= 0) {
            throw new Exception("Invalid input for updating saving.");
        }
        if (!in_array($saving->saving_type, ['wajib', 'sukarela'])) {
            throw new Exception("Invalid saving type.");
        }

        $saving->update();
    } elseif ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $saving->id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

        // Basic validation
        if (empty($saving->id)) {
            throw new Exception("Invalid ID for deleting saving.");
        }
        $saving->delete();
    } else {
        throw new Exception("Invalid action or request method.");
    }
} catch (Exception $e) {
    error_log("Saving action failed for user ID: " . $_SESSION['user_id'] . ". Error: " . $e->getMessage());
    header("Location: ../index.php?page=savings&status=error&message=" . urlencode($e->getMessage()));
    exit();
}

header("Location: ../index.php?page=savings&status=success");
exit();

```

## koperasi-php/actions/handle_transaction.php

```php
<?php
// actions/handle_transaction.php
session_start();
require_once '../config/Database.php';
require_once '../models/Transaction.php';

// Authorization check: Must be admin or superadmin
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'superadmin'])) {
    error_log("Unauthorized transaction action attempt by user ID: " . ($_SESSION['user_id'] ?? 'N/A') . " with role: " . ($_SESSION['user_role'] ?? 'N/A'));
    header("Location: ../index.php?page=transactions&error=unauthorized");
    exit();
}

// CSRF Protection: Verify token for POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        error_log("CSRF token mismatch for transaction action from user ID: " . $_SESSION['user_id']);
        header("Location: ../index.php?page=transactions&error=csrf_mismatch");
        exit();
    }
}

$database = new Database();
$db = $database->getConnection();
$transaction = new Transaction($db);

// Sanitize and validate action
$action = filter_input(INPUT_POST, 'action', FILTER_SANITIZE_STRING);

try {
    if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        // Input validation and sanitization
        $transaction->transaction_date = filter_input(INPUT_POST, 'transaction_date', FILTER_SANITIZE_STRING);
        $transaction->name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
        $transaction->description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING);
        $transaction->type = filter_input(INPUT_POST, 'type', FILTER_SANITIZE_STRING);
        $transaction->amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT, FILTER_FLAG_ALLOW_FRACTION);

        // Basic validation
        if (empty($transaction->transaction_date) || empty($transaction->description) || empty($transaction->type) || !is_numeric($transaction->amount) || $transaction->amount <= 0) {
            throw new Exception("Invalid input for creating transaction.");
        }
        if (!in_array($transaction->type, ['pemasukan', 'pengeluaran'])) {
            throw new Exception("Invalid transaction type.");
        }

        $transaction->created_by_user_id = $_SESSION['user_id'];
        $transaction->create();
    } elseif ($action === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $transaction->id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        $transaction->transaction_date = filter_input(INPUT_POST, 'transaction_date', FILTER_SANITIZE_STRING);
        $transaction->name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
        $transaction->description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING);
        $transaction->type = filter_input(INPUT_POST, 'type', FILTER_SANITIZE_STRING);
        $transaction->amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT, FILTER_FLAG_ALLOW_FRACTION);

        // Basic validation
        if (empty($transaction->id) || empty($transaction->transaction_date) || empty($transaction->description) || empty($transaction->type) || !is_numeric($transaction->amount) || $transaction->amount <= 0) {
            throw new Exception("Invalid input for updating transaction.");
        }
        if (!in_array($transaction->type, ['pemasukan', 'pengeluaran'])) {
            throw new Exception("Invalid transaction type.");
        }

        $transaction->update();
    } elseif ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $transaction->id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

        // Basic validation
        if (empty($transaction->id)) {
            throw new Exception("Invalid ID for deleting transaction.");
        }
        $transaction->delete();
    } else {
        throw new Exception("Invalid action or request method.");
    }
} catch (Exception $e) {
    // Log the error for debugging
    error_log("Transaction action failed for user ID: " . $_SESSION['user_id'] . ". Error: " . $e->getMessage());
    header("Location: ../index.php?page=transactions&status=error&message=" . urlencode($e->getMessage()));
    exit();
}

header("Location: ../index.php?page=transactions&status=success");
exit();

```

## koperasi-php/assets/js/main.js

```js
// assets/js/main.js

document.addEventListener("DOMContentLoaded", function () {
  const userRole = document.body.dataset.userRole || "user";
  const isAdmin = ["admin", "superadmin"].includes(userRole);

  // --- Utility Functions ---

  // Helper to format currency for display
  const formatRupiah = (number) => {
    return new Intl.NumberFormat("id-ID", {
      style: "currency",
      currency: "IDR",
      minimumFractionDigits: 0,
    }).format(number);
  };

  // Helper to format a plain number into Rupiah string for input field
  const formatNumberToRupiahString = (number) => {
    if (isNaN(number) || number === null || number === undefined) return "";
    return new Intl.NumberFormat("id-ID", {
      minimumFractionDigits: 0,
      maximumFractionDigits: 0,
    }).format(number);
  };

  // Helper to parse a Rupiah string from input field to a plain number
  const parseRupiahToNumber = (rupiahString) => {
    if (!rupiahString) return 0;
    // Remove "Rp", dots for thousands, and replace comma with dot for decimals (if any)
    const cleanString = rupiahString
      .replace(/[^0-9,-]+/g, "") // Keep only numbers, comma, hyphen
      .replace(/\./g, "") // Remove thousands separators (dots)
      .replace(/,/g, "."); // Replace decimal comma with dot (if decimal support is needed, currently 0 digits)

    const number = parseFloat(cleanString);
    return isNaN(number) ? 0 : number;
  };

  // --- Number Input Formatting Logic ---
  document.querySelectorAll('input[data-format="number"]').forEach((input) => {
    // Store the raw number value
    input.dataset.rawValue = parseRupiahToNumber(input.value);
    input.value = formatNumberToRupiahString(input.dataset.rawValue);

    input.addEventListener("input", function () {
      // Get caret position before formatting
      let caretPos = this.selectionStart;
      const initialValue = this.value;
      const initialLength = initialValue.length;

      // Clean the input (remove non-digits except comma/dot for potential decimals, then remove thousands separators)
      let cleaned = this.value.replace(/[^0-9]/g, ""); // Only allow digits for now, no decimal support (step="1000")
      if (cleaned === "") {
        this.value = "";
        this.dataset.rawValue = "";
        return;
      }

      // Convert to number, then format for display
      const number = parseInt(cleaned, 10);
      this.dataset.rawValue = number; // Store raw value
      this.value = formatNumberToRupiahString(number);

      // Adjust caret position
      const newLength = this.value.length;
      caretPos += newLength - initialLength;
      this.setSelectionRange(caretPos, caretPos);
    });

    input.addEventListener("focus", function () {
      // On focus, show the raw number without formatting
      const rawValue = this.dataset.rawValue;
      if (rawValue !== "" && rawValue !== undefined) {
        this.value = String(rawValue);
      } else {
        this.value = "";
      }
      // this.select(); // Select all text for easy editing
    });

    input.addEventListener("blur", function () {
      // On blur, format back to Rupiah string
      const rawValue = parseRupiahToNumber(this.value); // Re-parse in case user typed directly
      this.dataset.rawValue = rawValue;
      this.value = formatNumberToRupiahString(rawValue);
    });
  });

  // --- Intercept form submissions to send raw numbers ---
  document.querySelectorAll("form.amount-form").forEach((form) => {
    form.addEventListener("submit", function (event) {
      this.querySelectorAll('input[data-format="number"]').forEach((input) => {
        // Convert displayed formatted value back to raw number before submission
        input.value = parseRupiahToNumber(input.value);
      });
    });
  });

  // --- Mobile Sidebar Toggle ---
  const menuToggle = document.getElementById("menu-toggle");
  const sidebar = document.getElementById("sidebar");
  const mainContent = document.querySelector(".main-content");

  if (menuToggle && sidebar) {
    menuToggle.addEventListener("click", (e) => {
      e.stopPropagation(); // Prevent the mainContent click listener from firing immediately
      sidebar.classList.toggle("aktif");
    });
  }

  if (mainContent && sidebar) {
    // A click on the main content area will close an aktif mobile sidebar
    mainContent.addEventListener("click", () => {
      if (sidebar.classList.contains("aktif")) {
        sidebar.classList.remove("aktif");
      }
    });
  }

  // --- Global Modal Handling ---
  window.openModal = (modalId) => {
    const modal = document.getElementById(modalId);
    if (modal) {
      modal.style.display = "block";
    }
  };

  window.closeModal = (modalId) => {
    const modal = document.getElementById(modalId);
    if (modal) {
      modal.style.display = "none";
    }
  };

  // Close modal if user clicks outside of the modal-content
  window.addEventListener("click", (event) => {
    if (event.target.classList.contains("modal")) {
      event.target.style.display = "none";
    }
  });

  // --- Dynamic Form Population for Editing ---

  // For Transactions Page
  window.openEditTransactionModal = (data) => {
    document.getElementById("edit_id").value = data.id;
    document.getElementById("edit_transaction_date").value =
      data.transaction_date;
    document.getElementById("edit_name").value = data.name;
    document.getElementById("edit_description").value = data.description;
    document.getElementById("edit_type").value = data.type;

    // Format amount for display
    const editAmountInput = document.getElementById("edit_amount");
    editAmountInput.dataset.rawValue = data.amount; // Store raw value
    editAmountInput.value = formatNumberToRupiahString(data.amount); // Display formatted

    openModal("editTransactionModal");
  };

  // For Savings Page
  window.openEditSavingModal = (data) => {
    document.getElementById("edit_saving_id").value = data.id;
    document.getElementById("edit_saving_date").value = data.saving_date;
    document.getElementById("edit_member_name").value = data.member_name;
    document.getElementById("edit_saving_type").value = data.saving_type;

    // Format amount for display
    const editSavingAmountInput = document.getElementById("edit_saving_amount");
    editSavingAmountInput.dataset.rawValue = data.amount;
    editSavingAmountInput.value = formatNumberToRupiahString(data.amount);

    document.getElementById("edit_saving_description").value = data.description;
    openModal("editSavingModal");
  };

  // For Infaq Page
  window.openEditInfaqModal = (data) => {
    document.getElementById("edit_infaq_id").value = data.id;
    document.getElementById("edit_infaq_date").value = data.infaq_date;
    document.getElementById("edit_infaq_donor_name").value = data.donor_name;
    document.getElementById("edit_infaq_description").value = data.description;
    document.getElementById("edit_infaq_type").value = data.type;

    // Format amount for display
    const editInfaqAmountInput = document.getElementById("edit_infaq_amount");
    editInfaqAmountInput.dataset.rawValue = data.amount;
    editInfaqAmountInput.value = formatNumberToRupiahString(data.amount);

    openModal("editInfaqModal");
  };

  // For Loans Page (Main Record)
  window.openEditLoanModal = (data) => {
    document.getElementById("edit_loan_id").value = data.id;
    document.getElementById("edit_loan_date").value = data.loan_date;
    document.getElementById("edit_loan_member_name").value = data.member_name;

    // Format amount for display
    const editLoanAmountInput = document.getElementById("edit_loan_amount");
    editLoanAmountInput.dataset.rawValue = data.loan_amount;
    editLoanAmountInput.value = formatNumberToRupiahString(data.loan_amount);

    document.getElementById("edit_loan_tenor_months").value = data.tenor_months;
    document.getElementById("edit_loan_status").value = data.status;
    openModal("editLoanModal");
  };

  // ** NEW ** For Saving Details (AJAX)
  window.openSavingDetailModal = async (memberName) => {
    openModal("savingDetailModal");

    // Set loading states
    const title = document.getElementById("savingDetailTitle");
    const summaryDiv = document.getElementById("savingDetailSummary");
    const historyTableBody = document.querySelector(
      "#savingHistoryTable tbody"
    );

    const colspan = isAdmin ? 5 : 4;
    title.innerText = `Detail Simpanan: ${memberName}`;
    summaryDiv.innerHTML = "<p>Loading summary...</p>";
    historyTableBody.innerHTML = `<tr><td colspan="${colspan}">Loading history...</td></tr>`;

    try {
      const response = await fetch(
        `ajax_get_saving_details.php?member_name=${encodeURIComponent(
          memberName
        )}`
      );

      if (!response.ok) {
        throw new Error(
          `Network response was not ok. Status: ${response.status}`
        );
      }

      const data = await response.json();
      if (data.error) {
        throw new Error(data.error);
      }

      let totalAmount = 0;
      data.forEach((item) => (totalAmount += parseFloat(item.amount)));

      // Populate summary
      summaryDiv.innerHTML = `
                <div class="detail-grid">
                    <div><strong>Nama Anggota:</strong> ${memberName}</div>
                    <div><strong>Total Simpanan:</strong> ${formatRupiah(
                      totalAmount
                    )}</div>
                    <div><strong>Jumlah Transaksi:</strong> ${data.length}</div>
                </div>
            `;

      // Populate transaction history
      if (data.length > 0) {
        historyTableBody.innerHTML = data
          .map((s) => {
            // Escape the JSON data for use in the onclick attribute
            const rowData = JSON.stringify(s).replace(/"/g, "&quot;");
            const adminActions = isAdmin
              ? `<td class="actions-cell">
                               <button class="btn btn-sm btn-warning" onclick='openEditSavingModal(${rowData})'>Edit</button>
                               <form action="actions/handle_saving.php" method="POST" onsubmit="return confirm('Yakin ingin menghapus data ini?');" style="display:inline-block;">
                                   <input type="hidden" name="id" value="${
                                     s.id
                                   }">
                                   <input type="hidden" name="action" value="delete">
                                   <input type="hidden" name="csrf_token" value="${
                                     document.querySelector(
                                       'meta[name="csrf-token"]'
                                     ).content
                                   }">
                                   <button type="submit" class="btn btn-sm btn-danger">Hapus</button>
                               </form>
                           </td>`
              : "";

            return `
                        <tr>
                            <td>${new Date(s.saving_date).toLocaleDateString(
                              "id-ID",
                              {
                                day: "2-digit",
                                month: "short",
                                year: "numeric",
                              }
                            )}</td>
                            <td><span class="badge badge-info">${
                              s.saving_type.charAt(0).toUpperCase() +
                              s.saving_type.slice(1)
                            }</span></td>
                            <td class="text-right">${formatRupiah(
                              s.amount
                            )}</td>
                            <td>${s.description || "-"}</td>
                            ${adminActions}
                        </tr>
                    `;
          })
          .join("");
      } else {
        historyTableBody.innerHTML = `<tr><td colspan="${colspan}" style="text-align:center;">Tidak ada riwayat simpanan untuk anggota ini.</td></tr>`;
      }
    } catch (error) {
      console.error("Fetch error:", error);
      summaryDiv.innerHTML = `<p class="text-danger">Gagal memuat detail simpanan.</p>`;
      historyTableBody.innerHTML = `<tr><td colspan="${colspan}" class="text-danger">Gagal memuat riwayat.</td></tr>`;
    }
  };

  // For Loan Details & Payments (AJAX)
  window.openLoanDetailModal = async (loanId) => {
    openModal("loanDetailModal");

    // Set loading states
    const contentDiv = document.getElementById("loanDetailContent");
    const historyTableBody = document.querySelector(
      "#paymentHistoryTable tbody"
    );
    const title = document.getElementById("loanDetailTitle");
    title.innerText = "Detail Piutang";
    contentDiv.innerHTML = "<p>Loading details...</p>";
    historyTableBody.innerHTML =
      '<tr><td colspan="4">Loading history...</td></tr>';
    document.getElementById("payment_loan_id").value = loanId;

    try {
      const response = await fetch(
        `ajax_get_loan_details.php?loan_id=${loanId}`
      );
      if (!response.ok) {
        throw new Error(
          "Network response was not ok. Status: " + response.status
        );
      }

      const data = await response.json();
      if (data.error) {
        throw new Error(data.error);
      }

      const { loan, payments } = data;

      // Populate loan details
      title.innerText = `Detail Piutang: ${loan.member_name}`;
      contentDiv.innerHTML = `
                <div class="detail-grid">
                    <div><strong>Nama Peminjam:</strong> ${
                      loan.member_name
                    }</div>
                    <div><strong>Tanggal Pinjam:</strong> ${new Date(
                      loan.loan_date
                    ).toLocaleDateString("id-ID", {
                      day: "2-digit",
                      month: "short",
                      year: "numeric",
                    })}</div>
                    <div><strong>Jumlah Pinjaman:</strong> ${formatRupiah(
                      loan.loan_amount
                    )}</div>
                    <div><strong>Total Bayar:</strong> ${formatRupiah(
                      loan.total_paid
                    )}</div>
                    <div class="text-danger"><strong>Sisa Piutang:</strong> ${formatRupiah(
                      loan.remaining_amount
                    )}</div>
                    <div><strong>Tenor:</strong> ${
                      loan.tenor_months
                    } Bulan</div>
                </div>
            `;

      // Populate payment history
      if (payments.length > 0) {
        historyTableBody.innerHTML = payments
          .map(
            (p) => `
                    <tr>
                        <td>${new Date(p.payment_date).toLocaleDateString(
                          "id-ID",
                          { day: "2-digit", month: "short", year: "numeric" }
                        )}</td>
                        <td class="text-right">${formatRupiah(
                          p.payment_amount
                        )}</td>
                        <td>${p.payment_month_no || "-"}</td>
                        <td>${p.description || "-"}</td>
                    </tr>
                `
          )
          .join("");
      } else {
        historyTableBody.innerHTML =
          '<tr><td colspan="4" style="text-align:center;">Belum ada riwayat pembayaran.</td></tr>';
      }
    } catch (error) {
      console.error("Fetch error:", error);
      contentDiv.innerHTML = `<p class="text-danger">Gagal memuat detail piutang. ${error.message}</p>`;
      historyTableBody.innerHTML =
        '<tr><td colspan="4" class="text-danger">Gagal memuat riwayat.</td></tr>';
    }
  };

  // Add mobile table scroll indicators
  function addScrollIndicators() {
    const tableContainers = document.querySelectorAll(".table-responsive");

    tableContainers.forEach((container) => {
      const table = container.querySelector("table");
      if (!table) return;

      // Create scroll indicator
      const indicator = document.createElement("div");
      indicator.className = "scroll-indicator";
      indicator.innerHTML = "← Geser untuk melihat lebih →";

      // Only add indicator if table is wider than container AND no indicator exists
      if (
        table.scrollWidth > container.clientWidth &&
        !container.previousElementSibling?.classList.contains(
          "scroll-indicator"
        )
      ) {
        container.parentNode.insertBefore(indicator, container);

        // Hide indicator when user scrolls
        container.addEventListener("scroll", function () {
          if (this.scrollLeft > 10) {
            indicator.style.opacity = "0.3";
          } else {
            indicator.style.opacity = "1";
          }
        });
      }
    });
  }

  // Add mobile-friendly table headers (for very narrow screens)
  function enhanceMobileTables() {
    if (window.innerWidth <= 480) {
      const tables = document.querySelectorAll("table");

      tables.forEach((table) => {
        // Only apply if not already applied
        if (!table.classList.contains("table-stacked")) {
          table.classList.add("table-stacked");
          const headers = table.querySelectorAll("th");
          const rows = table.querySelectorAll("tbody tr");

          // Add data-label attributes for CSS styling
          rows.forEach((row) => {
            const cells = row.querySelectorAll("td");
            cells.forEach((cell, index) => {
              if (headers[index]) {
                cell.setAttribute("data-label", headers[index].textContent);
              }
            });
          });
        }
      });
    } else {
      // Remove table-stacked class if screen is wider
      document
        .querySelectorAll("table.table-stacked")
        .forEach((table) => table.classList.remove("table-stacked"));
    }
  }

  // Handle window resize
  function handleResize() {
    // Re-check scroll indicators on resize
    setTimeout(() => {
      // Remove existing indicators
      document
        .querySelectorAll(".scroll-indicator")
        .forEach((el) => el.remove());
      addScrollIndicators();
      enhanceMobileTables();
    }, 100);
  }

  // Initialize mobile enhancements
  if (window.innerWidth <= 768) {
    addScrollIndicators();
    enhanceMobileTables();
  }

  // Listen for window resize
  let resizeTimer;
  window.addEventListener("resize", function () {
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(handleResize, 250);
  });

  // Enhanced modal handling for mobile
  const originalOpenModal = window.openModal;
  window.openModal = function (modalId) {
    originalOpenModal(modalId);

    // Add mobile-specific modal behavior
    const modal = document.getElementById(modalId);
    if (modal && window.innerWidth <= 768) {
      // Prevent body scroll when modal is open
      document.body.style.overflow = "hidden";

      // Focus first input for better mobile UX
      const firstInput = modal.querySelector(
        'input[type="text"], input[type="date"], input[type="number"], select'
      );
      if (firstInput) {
        setTimeout(() => firstInput.focus(), 100);
      }
    }
  };

  const originalCloseModal = window.closeModal;
  window.closeModal = function (modalId) {
    originalCloseModal(modalId);

    // Re-enable body scroll
    document.body.style.overflow = "";

    // Re-apply formatting to any number inputs in the modal that might still be in raw state
    const modal = document.getElementById(modalId);
    if (modal) {
      modal.querySelectorAll('input[data-format="number"]').forEach((input) => {
        const rawValue = parseRupiahToNumber(input.value); // Ensure it's a number
        input.dataset.rawValue = rawValue;
        input.value = formatNumberToRupiahString(rawValue);
      });
    }
  };

  // Add touch-friendly interactions
  if ("ontouchstart" in window) {
    // Add touch class for CSS styling
    document.body.classList.add("touch-device");

    // Improve button tap targets
    const buttons = document.querySelectorAll(".btn-sm");
    buttons.forEach((btn) => {
      btn.style.minHeight = "44px"; // iOS recommended tap target
      btn.style.minWidth = "44px";
    });
  }

  // Optimize form inputs for mobile
  function optimizeMobileInputs() {
    // Prevent zoom on input focus (iOS) - this is primarily for type="text" inputs
    // For type="number", iOS might still zoom, but we've switched to type="text" for amounts
    const allInputs = document.querySelectorAll("input, select, textarea");
    allInputs.forEach((input) => {
      if (window.innerWidth <= 768) {
        const currentFontSize = window.getComputedStyle(input).fontSize;
        if (parseFloat(currentFontSize) < 16) {
          input.style.fontSize = "16px";
        }
      }
    });
  }

  optimizeMobileInputs();

  // Handle orientation change
  window.addEventListener("orientationchange", function () {
    setTimeout(() => {
      handleResize();
      optimizeMobileInputs();
    }, 500);
  });
});
```

## koperasi-php/config/Database.php

```php
<?php
// config/Database.php
class Database {
    private $host = "localhost";
    private $db_name = "koperasi_php";
    private $username = "root";
    private $password = "";
    public $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, $this->username, $this->password);
            $this->conn->exec("set names utf8");
            // Set error mode to exception for better error handling
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            // Disable emulated prepares for stronger type checking and prevention of SQL injection
            $this->conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        } catch(PDOException $exception) {
            // Log connection error instead of echoing it directly
            error_log("Connection error: " . $exception->getMessage(), 0);
            // You might want to throw a custom exception or redirect to an error page
            // For now, re-throwing the PDOException
            throw new Exception("Database connection failed. Please try again later.");
        }
        return $this->conn;
    }
}

```

## koperasi-php/config/pw.php

```php
<?php

echo password_hash('testdzaki231203test', PASSWORD_BCRYPT);
```

## koperasi-php/includes/footer.php

```php
           </main>
        </div>
    </div>
    <script src="assets/js/main.js"></script>
</body>
</html>
<!-- includes/footer.php -->
```

## koperasi-php/includes/header.php

```php
<?php
// includes/header.php
// This check should be at the top of any protected page
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Regenerate session ID every now and then to increase security (e.g., every 5 minutes)
// This helps mitigate session hijacking by making old session IDs invalid.
if (!isset($_SESSION['last_regenerate'])) {
    $_SESSION['last_regenerate'] = time();
} elseif (time() - $_SESSION['last_regenerate'] > 300) { // Regenerate every 5 minutes
    session_regenerate_id(true);
    $_SESSION['last_regenerate'] = time();
}

$user_name = $_SESSION['user_name'] ?? 'Guest';
$user_role = $_SESSION['user_role'] ?? 'user';

// Generate a CSRF token if one doesn't exist (for forms)
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Helper function to check for admin roles
function isAdmin() {
    return in_array($_SESSION['user_role'], ['admin', 'superadmin']);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Koperasi</title>
    <link rel="icon" type="image/x-icon" href="assets/logo.png" />
    <link rel="stylesheet" href="assets/css/style.css">
    <meta name="csrf-token" content="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
</head>
<body data-user-role="<?php echo htmlspecialchars($user_role); ?>">
    <div class="app-container">
        <?php include 'sidebar.php'; ?>
        <div class="main-content">
            <header class="app-header">
                <button class="menu-toggle" id="menu-toggle">
                    &#9776; <!-- Hamburger Icon -->
                </button>
                <div class="user-info">
                    <span>Selamat Datang, <strong><?php echo htmlspecialchars($user_name); ?></strong></span>
                    <a href="logout.php" class="logout-btn">Logout</a>
                </div>
            </header>
            <main class="content-body">


```

## koperasi-php/includes/sidebar.php

```php
<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <img src="assets/logo.png" alt="Koperasi Masjid Logo" style="width: 100px; height: auto;" />
    </div>
    <nav class="sidebar-nav">
        <ul>
            <li><a href="index.php?page=dashboard" class="<?php echo ($page == 'dashboard') ? 'aktif' : ''; ?>">Dashboard</a></li>
            <li><a href="index.php?page=transactions" class="<?php echo ($page == 'transactions') ? 'aktif' : ''; ?>">Kas Umum</a></li>
            <li><a href="index.php?page=savings" class="<?php echo ($page == 'savings') ? 'aktif' : ''; ?>">Simpanan</a></li>
            <li><a href="index.php?page=loans" class="<?php echo ($page == 'loans') ? 'aktif' : ''; ?>">Piutang</a></li>
            <li><a href="index.php?page=infaq" class="<?php echo ($page == 'infaq') ? 'aktif' : ''; ?>">Infaq</a></li>
        </ul>
    </nav>
</aside>
<!-- includes/sidebar.php -->
```

## koperasi-php/models/Infaq.php

```php
<?php
// models/Infaq.php
class Infaq {
    private $conn;
    private $table_name = "infaqs";

    public $id;
    public $infaq_date;
    public $description;
    public $donor_name;
    public $amount;
    public $type; // pemasukan, pengeluaran
    public $created_by_user_id;
    public $created_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Baca semua data infaq
    public function read() {
        // Order by infaq_date and then by id to ensure a consistent, reproducible order
        $query = "SELECT id, infaq_date, description, donor_name, amount, type FROM " . $this->table_name . " ORDER BY infaq_date DESC, id DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    // Buat data infaq baru
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " SET infaq_date = :infaq_date, description = :description, donor_name = :donor_name, amount = :amount, type = :type, created_by_user_id = :created_by_user_id";
        $stmt = $this->conn->prepare($query);

        // No need for htmlspecialchars/strip_tags here, as they are handled by filter_input in the controller
        // PDO bindParam handles SQL injection prevention
        $stmt->bindParam(":infaq_date", $this->infaq_date);
        $stmt->bindParam(":description", $this->description);
        $stmt->bindParam(":donor_name", $this->donor_name);
        $stmt->bindParam(":amount", $this->amount); // Amount should be a float/decimal, bind as is
        $stmt->bindParam(":type", $this->type);
        $stmt->bindParam(":created_by_user_id", $this->created_by_user_id);

        if ($stmt->execute()) {
            return true;
        }
        // Log the error details for debugging
        error_log("Infaq create error: " . implode(" ", $stmt->errorInfo()));
        return false;
    }

    // Perbarui data infaq
    public function update() {
        $query = "UPDATE " . $this->table_name . " SET infaq_date = :infaq_date, description = :description, donor_name = :donor_name, amount = :amount, type = :type WHERE id = :id";
        $stmt = $this->conn->prepare($query);

        // No need for htmlspecialchars/strip_tags here, as they are handled by filter_input in the controller
        $stmt->bindParam(":infaq_date", $this->infaq_date);
        $stmt->bindParam(":description", $this->description);
        $stmt->bindParam(":donor_name", $this->donor_name);
        $stmt->bindParam(":amount", $this->amount);
        $stmt->bindParam(":type", $this->type);
        $stmt->bindParam(":id", $this->id);

        if ($stmt->execute()) {
            return true;
        }
        error_log("Infaq update error: " . implode(" ", $stmt->errorInfo()));
        return false;
    }

    // Hapus data infaq
    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);

        // Ensure ID is an integer; PDO binding will also ensure this for the query.
        $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);

        if ($stmt->execute()) {
            return true;
        }
        error_log("Infaq delete error: " . implode(" ", $stmt->errorInfo()));
        return false;
    }

    // New method to read unique donor names
    public function readUniqueDonorNames() {
        // Only select distinct names that are not null or empty
        $query = "SELECT DISTINCT donor_name FROM " . $this->table_name . " WHERE donor_name IS NOT NULL AND donor_name != '' ORDER BY donor_name ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }
}

```

## koperasi-php/models/Loan.php

```php
<?php
// koperasi-php/models/Loan.php
class Loan {
    private $conn;
    private $table_name = "loans";
    private $payments_table = "loan_payments";

    // Properti untuk tabel loans
    public $id;
    public $member_name;
    public $loan_amount;
    public $loan_date;
    public $tenor_months;
    public $status; // aktif, selesai, gagal
    public $created_by_user_id;
    public $created_at;

    // Properti tambahan untuk pembayaran (jika diperlukan dalam satu operasi)
    public $payment_id;
    public $payment_date;
    public $payment_amount;
    public $payment_month_no;
    public $payment_description;

    public function __construct($db) {
        $this->conn = $db;
    }

    // --- Metode untuk LOANS (Piutang) ---

    // Baca semua data piutang dengan agregasi total pembayaran
    public function read() {
        $query = "
            SELECT
                l.id,
                l.member_name,
                l.loan_amount,
                l.loan_date,
                l.tenor_months,
                l.status,
                COALESCE(SUM(lp.payment_amount), 0) as total_paid,
                (l.loan_amount - COALESCE(SUM(lp.payment_amount), 0)) as remaining_amount
            FROM
                " . $this->table_name . " l
            LEFT JOIN
                " . $this->payments_table . " lp ON l.id = lp.loan_id
            GROUP BY
                l.id
            ORDER BY
                l.loan_date DESC, l.id DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    // Baca detail satu piutang
    public function readOne() {
        $query = "
            SELECT
                l.id,
                l.member_name,
                l.loan_amount,
                l.loan_date,
                l.tenor_months,
                l.status,
                COALESCE(SUM(lp.payment_amount), 0) as total_paid,
                (l.loan_amount - COALESCE(SUM(lp.payment_amount), 0)) as remaining_amount
            FROM
                " . $this->table_name . " l
            LEFT JOIN
                " . $this->payments_table . " lp ON l.id = lp.loan_id
            WHERE
                l.id = :id
            GROUP BY
                l.id
            LIMIT 0,1";

        $stmt = $this->conn->prepare($query);
        // Ensure id is treated as an integer
        $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt;
    }

    // Buat data piutang baru
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " SET member_name = :member_name, loan_amount = :loan_amount, loan_date = :loan_date, tenor_months = :tenor_months, status = :status, created_by_user_id = :created_by_user_id";
        $stmt = $this->conn->prepare($query);

        // No need for htmlspecialchars/strip_tags here, as they are handled by filter_input in the controller
        $stmt->bindParam(":member_name", $this->member_name);
        $stmt->bindParam(":loan_amount", $this->loan_amount);
        $stmt->bindParam(":loan_date", $this->loan_date);
        $stmt->bindParam(":tenor_months", $this->tenor_months, PDO::PARAM_INT);
        $stmt->bindParam(":status", $this->status);
        $stmt->bindParam(":created_by_user_id", $this->created_by_user_id, PDO::PARAM_INT);

        if ($stmt->execute()) {
            return true;
        }
        error_log("Loan create error: " . implode(" ", $stmt->errorInfo()));
        return false;
    }

    // Perbarui data piutang
    public function update() {
        $query = "UPDATE " . $this->table_name . " SET member_name = :member_name, loan_amount = :loan_amount, loan_date = :loan_date, tenor_months = :tenor_months, status = :status WHERE id = :id";
        $stmt = $this->conn->prepare($query);

        // No need for htmlspecialchars/strip_tags here, as they are handled by filter_input in the controller
        $stmt->bindParam(":member_name", $this->member_name);
        $stmt->bindParam(":loan_amount", $this->loan_amount);
        $stmt->bindParam(":loan_date", $this->loan_date);
        $stmt->bindParam(":tenor_months", $this->tenor_months, PDO::PARAM_INT);
        $stmt->bindParam(":status", $this->status);
        $stmt->bindParam(":id", $this->id, PDO::PARAM_INT);

        if ($stmt->execute()) {
            return true;
        }
        error_log("Loan update error: " . implode(" ", $stmt->errorInfo()));
        return false;
    }

    // Hapus data piutang
    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);

        if ($stmt->execute()) {
            return true;
        }
        error_log("Loan delete error: " . implode(" ", $stmt->errorInfo()));
        return false;
    }

    // --- Metode untuk LOAN PAYMENTS (Setoran Pinjaman) ---

    // Baca semua pembayaran untuk piutang tertentu
    public function readLoanPayments() {
        $query = "SELECT id, loan_id, payment_date, payment_amount, payment_month_no, description FROM " . $this->payments_table . " WHERE loan_id = :loan_id ORDER BY payment_date ASC, id ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':loan_id', $this->id, PDO::PARAM_INT); // 'id' properti digunakan sebagai loan_id
        $stmt->execute();
        return $stmt;
    }

    // Tambahkan pembayaran baru untuk piutang
    public function addPayment() {
        $query = "INSERT INTO " . $this->payments_table . " SET loan_id = :loan_id, payment_date = :payment_date, payment_amount = :payment_amount, payment_month_no = :payment_month_no, description = :description, created_by_user_id = :created_by_user_id";
        $stmt = $this->conn->prepare($query);

        // No need for htmlspecialchars/strip_tags here
        $stmt->bindParam(":loan_id", $this->id, PDO::PARAM_INT); // 'id' properti digunakan sebagai loan_id
        $stmt->bindParam(":payment_date", $this->payment_date);
        $stmt->bindParam(":payment_amount", $this->payment_amount);
        $stmt->bindParam(":payment_month_no", $this->payment_month_no, PDO::PARAM_INT);
        $stmt->bindParam(":description", $this->payment_description);
        $stmt->bindParam(":created_by_user_id", $this->created_by_user_id, PDO::PARAM_INT);

        if ($stmt->execute()) {
            return true;
        }
        error_log("Loan payment create error: " . implode(" ", $stmt->errorInfo()));
        return false;
    }

    // Perbarui status pinjaman menjadi 'selesai' jika lunas
    public function updateLoanStatus($loan_id) {
        // Fetch the loan data including total_paid and remaining_amount
        $temp_id = $this->id; // Save current ID
        $this->id = $loan_id;
        $readStmt = $this->readOne();
        $this->id = $temp_id; // Restore ID

        if ($readStmt->rowCount() > 0) {
            $loan_data = $readStmt->fetch(PDO::FETCH_ASSOC);
            $newStatus = ($loan_data['remaining_amount'] <= 0 && $loan_data['loan_amount'] > 0) ? 'selesai' : 'aktif'; // Only set to 'selesai' if loan_amount > 0

            // Only update if the status actually changes
            if ($loan_data['status'] !== $newStatus) {
                $query = "UPDATE " . $this->table_name . " SET status = :status WHERE id = :id";
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(':status', $newStatus);
                $stmt->bindParam(':id', $loan_id, PDO::PARAM_INT);
                $stmt->execute();
            }
        }
    }

    // New method to read unique member names from loans
    public function readUniqueMemberNames() {
        $query = "SELECT DISTINCT member_name FROM " . $this->table_name . " WHERE member_name IS NOT NULL AND member_name != '' ORDER BY member_name ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }
}

```

## koperasi-php/models/Saving.php

```php
<?php
// models/Saving.php
class Saving {
    private $conn;
    private $table_name = "savings";

    public $id;
    public $member_name;
    public $saving_type; // wajib, sukarela
    public $saving_date;
    public $amount;
    public $description;
    public $created_by_user_id;
    public $created_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Baca semua data simpanan (raw data, typically for admin audit or internal logic)
    public function read() {
        $query = "SELECT id, member_name, saving_type, saving_date, amount, description FROM " . $this->table_name . " ORDER BY saving_date DESC, id DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    // Method to read savings grouped by member for the main view (summary)
    public function readGroupedByMember() {
        $query = "SELECT
                    member_name,
                    COUNT(id) as transaction_count,
                    SUM(amount) as total_amount
                  FROM
                    " . $this->table_name . "
                  GROUP BY
                    member_name
                  ORDER BY
                    member_name ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    // Method to read all savings for a specific member for the detail modal
    public function readByMemberName() {
        $query = "SELECT
                    id,
                    saving_date,
                    saving_type,
                    amount,
                    description
                  FROM
                    " . $this->table_name . "
                  WHERE
                    member_name = :member_name
                  ORDER BY
                    saving_date DESC, id DESC";
        $stmt = $this->conn->prepare($query);

        // PDO binding handles SQL injection prevention
        $stmt->bindParam(":member_name", $this->member_name);

        $stmt->execute();
        return $stmt;
    }

    // Buat data simpanan baru
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " SET member_name = :member_name, saving_type = :saving_type, saving_date = :saving_date, amount = :amount, description = :description, created_by_user_id = :created_by_user_id";
        $stmt = $this->conn->prepare($query);

        // No need for htmlspecialchars/strip_tags here, as they are handled by filter_input in the controller
        $stmt->bindParam(":member_name", $this->member_name);
        $stmt->bindParam(":saving_type", $this->saving_type);
        $stmt->bindParam(":saving_date", $this->saving_date);
        $stmt->bindParam(":amount", $this->amount);
        $stmt->bindParam(":description", $this->description);
        $stmt->bindParam(":created_by_user_id", $this->created_by_user_id, PDO::PARAM_INT);

        if ($stmt->execute()) {
            return true;
        }
        error_log("Saving create error: " . implode(" ", $stmt->errorInfo()));
        return false;
    }

    // Perbarui data simpanan
    public function update() {
        $query = "UPDATE " . $this->table_name . " SET member_name = :member_name, saving_type = :saving_type, saving_date = :saving_date, amount = :amount, description = :description WHERE id = :id";
        $stmt = $this->conn->prepare($query);

        // No need for htmlspecialchars/strip_tags here
        $stmt->bindParam(":member_name", $this->member_name);
        $stmt->bindParam(":saving_type", $this->saving_type);
        $stmt->bindParam(":saving_date", $this->saving_date);
        $stmt->bindParam(":amount", $this->amount);
        $stmt->bindParam(":description", $this->description);
        $stmt->bindParam(":id", $this->id, PDO::PARAM_INT);

        if ($stmt->execute()) {
            return true;
        }
        error_log("Saving update error: " . implode(" ", $stmt->errorInfo()));
        return false;
    }

    // Hapus data simpanan
    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);

        if ($stmt->execute()) {
            return true;
        }
        error_log("Saving delete error: " . implode(" ", $stmt->errorInfo()));
        return false;
    }

    // New method to read unique member names from savings
    public function readUniqueMemberNames() {
        $query = "SELECT DISTINCT member_name FROM " . $this->table_name . " WHERE member_name IS NOT NULL AND member_name != '' ORDER BY member_name ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }
}

```

## koperasi-php/models/Transaction.php

```php
<?php
// models/Transaction.php
class Transaction {
    private $conn;
    private $table_name = "transactions";

    public $id;
    public $transaction_date;
    public $name;
    public $description;
    public $type;
    public $amount;
    public $created_by_user_id;
    public $created_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function read() {
        $query = "SELECT id, transaction_date, name, description, type, amount FROM " . $this->table_name . " ORDER BY transaction_date DESC, id DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function create() {
        $query = "INSERT INTO " . $this->table_name . " SET transaction_date = :transaction_date, name = :name, description = :description, type = :type, amount = :amount, created_by_user_id = :created_by_user_id";
        $stmt = $this->conn->prepare($query);

        // No need for htmlspecialchars/strip_tags here, as they are handled by filter_input in the controller
        $stmt->bindParam(":transaction_date", $this->transaction_date);
        $stmt->bindParam(":name", $this->name);
        $stmt->bindParam(":description", $this->description);
        $stmt->bindParam(":type", $this->type);
        $stmt->bindParam(":amount", $this->amount);
        $stmt->bindParam(":created_by_user_id", $this->created_by_user_id, PDO::PARAM_INT);

        if ($stmt->execute()) {
            return true;
        }
        error_log("Transaction create error: " . implode(" ", $stmt->errorInfo()));
        return false;
    }

    public function update() {
        $query = "UPDATE " . $this->table_name . " SET transaction_date = :transaction_date, name = :name, description = :description, type = :type, amount = :amount WHERE id = :id";
        $stmt = $this->conn->prepare($query);

        // No need for htmlspecialchars/strip_tags here
        $stmt->bindParam(":transaction_date", $this->transaction_date);
        $stmt->bindParam(":name", $this->name);
        $stmt->bindParam(":description", $this->description);
        $stmt->bindParam(":type", $this->type);
        $stmt->bindParam(":amount", $this->amount);
        $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);

        if ($stmt->execute()) {
            return true;
        }
        error_log("Transaction update error: " . implode(" ", $stmt->errorInfo()));
        return false;
    }

    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);

        if ($stmt->execute()) {
            return true;
        }
        error_log("Transaction delete error: " . implode(" ", $stmt->errorInfo()));
        return false;
    }

    // New method to read unique names from transactions
    public function readUniqueNames() {
        $query = "SELECT DISTINCT name FROM " . $this->table_name . " WHERE name IS NOT NULL AND name != '' ORDER BY name ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }
}

```

## koperasi-php/models/User.php

```php
<?php
// models/User.php
class User {
    private $conn;
    private $table_name = "users";

    public $id;
    public $username;
    public $password; // Stored hashed password
    public $name;
    public $role;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function findByUsername() {
        $query = "SELECT id, username, password, name, role FROM " . $this->table_name . " WHERE username = :username LIMIT 0,1";
        $stmt = $this->conn->prepare($query);

        // Sanitize and bind parameter for username
        // Using filter_var for basic sanitization, though PDO will handle quoting for SQL injection.
        // It's good practice to ensure input matches expected format.
        $clean_username = filter_var($this->username, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
        $stmt->bindParam(':username', $clean_username);
        $stmt->execute();
        return $stmt;
    }

    public function findById() {
        $query = "SELECT id, username, name, role FROM " . $this->table_name . " WHERE id = :id LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        // Bind parameter as integer to ensure type safety
        $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt;
    }

    /**
     * Creates a new user.
     * Use this method to register new users, ensuring password hashing.
     *
     * @return bool True on success, false on failure.
     */
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " (username, password, name, role) VALUES (:username, :password, :name, :role)";
        $stmt = $this->conn->prepare($query);

        // Sanitize inputs
        $clean_username = filter_var($this->username, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
        $clean_name = filter_var($this->name, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
        $clean_role = filter_var($this->role, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);

        // Hash the password securely
        $hashed_password = password_hash($this->password, PASSWORD_DEFAULT);

        $stmt->bindParam(':username', $clean_username);
        $stmt->bindParam(':password', $hashed_password);
        $stmt->bindParam(':name', $clean_name);
        $stmt->bindParam(':role', $clean_role);

        if ($stmt->execute()) {
            return true;
        }

        error_log("User creation error: " . implode(" ", $stmt->errorInfo()));
        return false;
    }

    /**
     * Updates an existing user's details (excluding password, which should have its own method).
     *
     * @return bool True on success, false on failure.
     */
    public function update() {
        $query = "UPDATE " . $this->table_name . " SET username = :username, name = :name, role = :role WHERE id = :id";
        $stmt = $this->conn->prepare($query);

        // Sanitize inputs
        $clean_username = filter_var($this->username, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
        $clean_name = filter_var($this->name, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
        $clean_role = filter_var($this->role, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);

        $stmt->bindParam(':username', $clean_username);
        $stmt->bindParam(':name', $clean_name);
        $stmt->bindParam(':role', $clean_role);
        $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);

        if ($stmt->execute()) {
            return true;
        }

        error_log("User update error: " . implode(" ", $stmt->errorInfo()));
        return false;
    }

    /**
     * Updates a user's password.
     *
     * @return bool True on success, false on failure.
     */
    public function updatePassword() {
        $query = "UPDATE " . $this->table_name . " SET password = :password WHERE id = :id";
        $stmt = $this->conn->prepare($query);

        // Hash the new password securely
        $hashed_password = password_hash($this->password, PASSWORD_DEFAULT);

        $stmt->bindParam(':password', $hashed_password);
        $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);

        if ($stmt->execute()) {
            return true;
        }

        error_log("Password update error: " . implode(" ", $stmt->errorInfo()));
        return false;
    }

    /**
     * Deletes a user.
     *
     * @return bool True on success, false on failure.
     */
    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);

        if ($stmt->execute()) {
            return true;
        }

        error_log("User delete error: " . implode(" ", $stmt->errorInfo()));
        return false;
    }
}

```

## koperasi-php/pages/dashboard.php

```php
<?php
// pages/dashboard.php
// Instantiate models to get summary data
$transactionModel = new Transaction($db);
$savingModel = new Saving($db);
$loanModel = new Loan($db);
$infaqModel = new Infaq($db);

// --- Calculate Total Kas Umum ---
$allTransactions = $transactionModel->read()->fetchAll(PDO::FETCH_ASSOC);
$totalPemasukanKas = 0;
$totalPengeluaranKas = 0;
foreach ($allTransactions as $t) {
    // Ensure amount is treated as a number
    $amount = is_numeric($t['amount']) ? (float)$t['amount'] : 0;
    if ($t['type'] == 'pemasukan') {
        $totalPemasukanKas += $amount;
    } else {
        $totalPengeluaranKas += $amount;
    }
}
$saldoKas = $totalPemasukanKas - $totalPengeluaranKas;

// --- Calculate Total Simpanan ---
$allSavings = $savingModel->read()->fetchAll(PDO::FETCH_ASSOC);
// Ensure amount is treated as a number before summing
$totalSimpanan = array_sum(array_map(function($s) {
    return is_numeric($s['amount']) ? (float)$s['amount'] : 0;
}, $allSavings));

// --- Calculate Total Piutang (Outstanding Loans) ---
$allLoans = $loanModel->read()->fetchAll(PDO::FETCH_ASSOC);
$totalPiutangAktif = 0;
foreach ($allLoans as $l) {
    // Ensure amounts are treated as numbers
    $remaining_amount = is_numeric($l['remaining_amount']) ? (float)$l['remaining_amount'] : 0;
    if ($l['status'] == 'aktif') {
        $totalPiutangAktif += $remaining_amount;
    }
}

// --- Calculate Total Infaq ---
$allInfaqs = $infaqModel->read()->fetchAll(PDO::FETCH_ASSOC);
$totalPemasukanInfaq = 0;
$totalPengeluaranInfaq = 0;
foreach ($allInfaqs as $i) {
    // Ensure amount is treated as a number
    $amount = is_numeric($i['amount']) ? (float)$i['amount'] : 0;
    if ($i['type'] == 'pemasukan') {
        $totalPemasukanInfaq += $amount;
    } else {
        $totalPengeluaranInfaq += $amount;
    }
}
$saldoInfaq = $totalPemasukanInfaq - $totalPengeluaranInfaq;

// --- Get 5 Recent Activities (Combined) ---
// Note: For a large dataset, a more efficient UNION query in the model would be better.
// For simplicity here, we'll merge and sort in PHP.
$recentActivities = array_merge(
    array_slice($allTransactions, 0, 5),
    array_slice($allSavings, 0, 5),
    array_slice($allInfaqs, 0, 5)
);

// Add a 'date' key for consistent sorting
foreach($recentActivities as &$item) {
    $item['date'] = $item['transaction_date'] ?? $item['saving_date'] ?? $item['infaq_date'];
    // Ensure amounts are cast to float for consistent comparison/display
    $item['amount'] = is_numeric($item['amount']) ? (float)$item['amount'] : 0;
}
unset($item); // Break the reference with the last element

usort($recentActivities, function($a, $b) {
    // Safely convert dates to timestamps for comparison
    $timeA = strtotime($a['date'] ?? '1970-01-01'); // Default to epoch if date is missing
    $timeB = strtotime($b['date'] ?? '1970-01-01');
    return $timeB - $timeA;
});

$recentActivities = array_slice($recentActivities, 0, 5);

?>

<div class="page-header">
    <h1>Dashboard</h1>
</div>

<div class="dashboard-summary">
    <div class="summary-card">
        <h3>Saldo Kas Umum</h3>
        <p>Rp <?php echo number_format($saldoKas, 0, ',', '.'); ?></p>
    </div>
    <div class="summary-card">
        <h3>Total Simpanan Anggota</h3>
        <p>Rp <?php echo number_format($totalSimpanan, 0, ',', '.'); ?></p>
    </div>
    <div class="summary-card">
        <h3>Piutang Aktif</h3>
        <p>Rp <?php echo number_format($totalPiutangAktif, 0, ',', '.'); ?></p>
    </div>
    <div class="summary-card">
        <h3>Saldo Kas Infaq</h3>
        <p>Rp <?php echo number_format($saldoInfaq, 0, ',', '.'); ?></p>
    </div>
</div>

<div class="card mt-4">
    <div class="card-body">
        <h3>Aktivitas Terbaru</h3>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Kategori</th>
                        <th>Deskripsi</th>
                        <th>Jumlah</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recentActivities)): ?>
                        <tr><td colspan="4" style="text-align:center;">Tidak ada aktivitas terbaru.</td></tr>
                    <?php else: ?>
                        <?php foreach($recentActivities as $activity): ?>
                        <tr>
                            <td><?php echo date("d M Y", strtotime(htmlspecialchars($activity['date']))); ?></td>
                            <td>
                                <?php
                                    // Use explicit checks for better clarity and robustness
                                    if (isset($activity['transaction_date'])) {
                                        echo 'Kas Umum';
                                    } elseif (isset($activity['saving_date'])) {
                                        echo 'Simpanan';
                                    } elseif (isset($activity['infaq_date'])) {
                                        echo 'Infaq';
                                    } else {
                                        echo 'N/A'; // Fallback for undefined category
                                    }
                                ?>
                            </td>
                            <td><?php echo htmlspecialchars($activity['description'] ?? ''); ?></td>
                            <td class="text-right <?php echo (isset($activity['type']) && $activity['type'] == 'pemasukan') ? 'text-success' : 'text-danger'; ?>">
                                <?php echo "Rp " . number_format($activity['amount'] ?? 0, 0, ',', '.'); ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

```

## koperasi-php/pages/infaq.php

```php
<?php
// pages/infaq.php
$infaq = new Infaq($db);
$stmt = $infaq->read();
$infaqs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch unique donor names for datalist
$donorNamesStmt = $infaq->readUniqueDonorNames();
$donorNames = $donorNamesStmt->fetchAll(PDO::FETCH_COLUMN); // Fetch as a simple array of strings
?>

<div class="page-header">
    <h1>Kas Infaq</h1>
    <?php if (isAdmin()): ?>
    <button class="btn btn-primary" onclick="openModal('addInfaqModal')">Tambah Infaq</button>
    <?php endif; ?>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Donatur</th>
                        <th>Deskripsi</th>
                        <th>Tipe</th>
                        <th>Jumlah</th>
                        <?php if (isAdmin()): ?>
                        <th>Aksi</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($infaqs)): ?>
                        <tr><td colspan="<?php echo isAdmin() ? '6' : '5'; ?>" style="text-align: center;">Tidak ada data infaq.</td></tr>
                    <?php else: ?>
                        <?php foreach ($infaqs as $row): ?>
                        <tr>
                            <td><?php echo date("d M Y", strtotime(htmlspecialchars($row['infaq_date']))); ?></td>
                            <td><?php echo htmlspecialchars($row['donor_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['description']); ?></td>
                            <td>
                                <span class="badge <?php echo $row['type'] == 'pemasukan' ? 'badge-success' : 'badge-danger'; ?>">
                                    <?php echo htmlspecialchars(ucfirst($row['type'])); ?>
                                </span>
                            </td>
                            <td class="text-right"><?php echo "Rp " . number_format($row['amount'], 0, ',', '.'); ?></td>
                            <?php if (isAdmin()): ?>
                            <td class="actions-cell">
                                <button class="btn btn-sm btn-warning" onclick="openEditInfaqModal(<?php echo htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8'); ?>)">Edit</button>
                                <form action="actions/handle_infaq.php" method="POST" onsubmit="return confirm('Yakin ingin menghapus data ini?');">
                                    <input type="hidden" name="id" value="<?php echo htmlspecialchars($row['id']); ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                    <button type="submit" class="btn btn-sm btn-danger">Hapus</button>
                                </form>
                            </td>
                            <?php endif; ?>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Infaq Modal -->
<div id="addInfaqModal" class="modal">
    <div class="modal-content">
        <span class="close-btn" onclick="closeModal('addInfaqModal')">&times;</span>
        <h2>Tambah Infaq</h2>
        <form action="actions/handle_infaq.php" method="POST" class="amount-form">
            <input type="hidden" name="action" value="create">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            <div class="form-group">
                <label>Tanggal</label>
                <input type="date" name="infaq_date" required>
            </div>
            <div class="form-group">
                <label>Nama Donatur (Opsional)</label>
                <input type="text" name="donor_name" list="donorNamesDatalist">
                <datalist id="donorNamesDatalist">
                    <?php foreach ($donorNames as $name): ?>
                        <option value="<?php echo htmlspecialchars($name); ?>">
                    <?php endforeach; ?>
                </datalist>
            </div>
            <div class="form-group">
                <label>Deskripsi</label>
                <input type="text" name="description" required>
            </div>
            <div class="form-group">
                <label>Tipe</label>
                <select name="type" required>
                    <option value="pemasukan">Pemasukan</option>
                    <option value="pengeluaran">Pengeluaran</option>
                </select>
            </div>
            <div class="form-group">
                <label>Jumlah</label>
                <input type="text" name="amount" data-format="number" inputmode="numeric" pattern="[0-9.,]*" required>
            </div>
            <div class="form-actions">
                <button type="button" class="btn btn-secondary" onclick="closeModal('addInfaqModal')">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Infaq Modal -->
<div id="editInfaqModal" class="modal">
    <div class="modal-content">
        <span class="close-btn" onclick="closeModal('editInfaqModal')">&times;</span>
        <h2>Edit Infaq</h2>
        <form action="actions/handle_infaq.php" method="POST" class="amount-form">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="id" id="edit_infaq_id">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            <div class="form-group">
                <label>Tanggal</label>
                <input type="date" name="infaq_date" id="edit_infaq_date" required>
            </div>
            <div class="form-group">
                <label>Nama Donatur (Opsional)</label>
                <input type="text" name="donor_name" id="edit_infaq_donor_name" list="donorNamesDatalist">
                <!-- Datalist is global, so it doesn't need to be repeated here -->
            </div>
            <div class="form-group">
                <label>Deskripsi</label>
                <input type="text" name="description" id="edit_infaq_description" required>
            </div>
            <div class="form-group">
                <label>Tipe</label>
                <select name="type" id="edit_infaq_type" required>
                    <option value="pemasukan">Pemasukan</option>
                    <option value="pengeluaran">Pengeluaran</option>
                </select>
            </div>
            <div class="form-group">
                <label>Jumlah</label>
                <input type="text" name="amount" id="edit_infaq_amount" data-format="number" inputmode="numeric" pattern="[0-9.,]*" required>
            </div>
            <div class="form-actions">
                <button type="button" class="btn btn-secondary" onclick="closeModal('editInfaqModal')">Batal</button>
                <button type="submit" class="btn btn-primary">Update</button>
            </div>
        </form>
    </div>
</div>

```

## koperasi-php/pages/loans.php

```php
<?php
// pages/loans.php
$loan = new Loan($db);
$stmt = $loan->read();
$loans = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch unique member names for datalist
$memberNamesStmt = $loan->readUniqueMemberNames();
$memberNames = $memberNamesStmt->fetchAll(PDO::FETCH_COLUMN); // Fetch as a simple array of strings
?>

<div class="page-header">
    <h1>Data Piutang</h1>
    <?php if (isAdmin()): ?>
    <button class="btn btn-primary" onclick="openModal('addLoanModal')">Tambah Piutang</button>
    <?php endif; ?>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Tanggal Pinjam</th>
                        <th>Nama Anggota</th>
                        <th>Jumlah Pinjaman</th>
                        <th>Total Bayar</th>
                        <th>Sisa</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($loans)): ?>
                        <tr><td colspan="7" style="text-align: center;">Tidak ada data piutang.</td></tr>
                    <?php else: ?>
                        <?php foreach ($loans as $row): ?>
                        <tr>
                            <td><?php echo date("d M Y", strtotime(htmlspecialchars($row['loan_date']))); ?></td>
                            <td><?php echo htmlspecialchars($row['member_name']); ?></td>
                            <td class="text-right"><?php echo "Rp " . number_format($row['loan_amount'], 0, ',', '.'); ?></td>
                            <td class="text-right"><?php echo "Rp " . number_format($row['total_paid'], 0, ',', '.'); ?></td>
                            <td class="text-right text-danger"><?php echo "Rp " . number_format($row['remaining_amount'], 0, ',', '.'); ?></td>
                            <td>
                                <?php
                                $status_class = 'badge-info';
                                if ($row['status'] == 'selesai') $status_class = 'badge-success';
                                if ($row['status'] == 'gagal') $status_class = 'badge-danger';
                                ?>
                                <span class="badge <?php echo htmlspecialchars($status_class); ?>"><?php echo htmlspecialchars(ucfirst($row['status'])); ?></span>
                            </td>
                            <td class="actions-cell">
                                <button class="btn btn-sm btn-info" onclick="openLoanDetailModal(<?php echo htmlspecialchars($row['id']); ?>)">Detail/Bayar</button>
                                <?php if (isAdmin()): ?>
                                <button class="btn btn-sm btn-warning" onclick="openEditLoanModal(<?php echo htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8'); ?>)">Edit</button>
                                <form action="actions/handle_loan.php" method="POST" onsubmit="return confirm('Yakin menghapus piutang ini? Semua data pembayaran terkait akan ikut terhapus!');">
                                    <input type="hidden" name="id" value="<?php echo htmlspecialchars($row['id']); ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                    <button type="submit" class="btn btn-sm btn-danger">Hapus</button>
                                </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Loan Modal -->
<div id="addLoanModal" class="modal">
    <div class="modal-content">
        <span class="close-btn" onclick="closeModal('addLoanModal')">&times;</span>
        <h2>Tambah Piutang Baru</h2>
        <form action="actions/handle_loan.php" method="POST" class="amount-form">
            <input type="hidden" name="action" value="create">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            <div class="form-group">
                <label>Tanggal Pinjam</label>
                <input type="date" name="loan_date" required>
            </div>
            <div class="form-group">
                <label>Nama Anggota</label>
                <input type="text" name="member_name" list="memberNamesDatalist" required>
                <datalist id="memberNamesDatalist">
                    <?php foreach ($memberNames as $name): ?>
                        <option value="<?php echo htmlspecialchars($name); ?>">
                    <?php endforeach; ?>
                </datalist>
            </div>
            <div class="form-group">
                <label>Jumlah Pinjaman</label>
                <input type="text" name="loan_amount" data-format="number" inputmode="numeric" pattern="[0-9.,]*" required>
            </div>
            <div class="form-group">
                <label>Tenor (Bulan)</label>
                <input type="number" name="tenor_months" min="1" value="10" required>
            </div>
            <div class="form-actions">
                <button type="button" class="btn btn-secondary" onclick="closeModal('addLoanModal')">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Loan Modal -->
<div id="editLoanModal" class="modal">
    <div class="modal-content">
        <span class="close-btn" onclick="closeModal('editLoanModal')">&times;</span>
        <h2>Edit Data Piutang</h2>
        <form action="actions/handle_loan.php" method="POST" class="amount-form">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="id" id="edit_loan_id">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            <div class="form-group">
                <label>Tanggal Pinjam</label>
                <input type="date" name="loan_date" id="edit_loan_date" required>
            </div>
            <div class="form-group">
                <label>Nama Anggota</label>
                <input type="text" name="member_name" id="edit_loan_member_name" list="memberNamesDatalist" required>
                <!-- Datalist is global -->
            </div>
            <div class="form-group">
                <label>Jumlah Pinjaman</label>
                <input type="text" name="loan_amount" id="edit_loan_amount" data-format="number" inputmode="numeric" pattern="[0-9.,]*" required>
            </div>
            <div class="form-group">
                <label>Tenor (Bulan)</label>
                <input type="number" name="tenor_months" id="edit_loan_tenor_months" min="1" required>
            </div>
            <div class="form-group">
                <label>Status</label>
                <select name="status" id="edit_loan_status" required>
                    <option value="aktif">aktif</option>
                    <option value="selesai">selesai</option>
                    <option value="gagal">gagal</option>
                </select>
            </div>
            <div class="form-actions">
                <button type="button" class="btn btn-secondary" onclick="closeModal('editLoanModal')">Batal</button>
                <button type="submit" class="btn btn-primary">Update</button>
            </div>
        </form>
    </div>
</div>

<!-- Loan Detail & Payment Modal -->
<div id="loanDetailModal" class="modal">
    <div class="modal-content modal-lg">
        <span class="close-btn" onclick="closeModal('loanDetailModal')">&times;</span>
        <h2 id="loanDetailTitle">Detail Piutang</h2>

        <div id="loanDetailContent" class="loan-details">
            <!-- Content will be loaded via AJAX -->
            <p>Loading...</p>
        </div>

        <hr>

        <?php if (isAdmin()): ?>
        <h3>Tambah Pembayaran</h3>
        <form action="actions/handle_loan_payment.php" method="POST" class="amount-form">
            <input type="hidden" name="loan_id" id="payment_loan_id">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            <div class="form-row">
                <div class="form-group">
                    <label>Tanggal Bayar</label>
                    <input type="date" name="payment_date" required>
                </div>
                <div class="form-group">
                    <label>Jumlah Bayar</label>
                    <input type="text" name="payment_amount" data-format="number" inputmode="numeric" pattern="[0-9.,]*" required>
                </div>
                <div class="form-group">
                    <label>Bayar Bulan Ke- (Ops)</label>
                    <input type="number" name="payment_month_no" min="1">
                </div>
            </div>
            <div class="form-group">
                <label>Deskripsi</label>
                <input type="text" name="description">
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Tambah Pembayaran</button>
            </div>
        </form>
        <?php endif; ?>

        <h3>Riwayat Pembayaran</h3>
        <div class="table-responsive mt-2">
            <table id="paymentHistoryTable">
                <thead>
                    <tr>
                        <th>Tgl Bayar</th>
                        <th>Jumlah</th>
                        <th>Bulan Ke-</th>
                        <th>Deskripsi</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- History will be loaded via AJAX -->
                    <tr><td colspan="4">Loading...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

```

## koperasi-php/pages/savings.php

```php
<?php
// pages/savings.php
$saving = new Saving($db);
$stmt = $saving->readGroupedByMember();
$savings_summary = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch unique member names for datalist
$memberNamesStmt = $saving->readUniqueMemberNames();
$memberNames = $memberNamesStmt->fetchAll(PDO::FETCH_COLUMN); // Fetch as a simple array of strings
?>

<div class="page-header">
    <h1>Simpanan Anggota</h1>
    <?php if (isAdmin()): ?>
    <button class="btn btn-primary" onclick="openModal('addSavingModal')">Tambah Simpanan</button>
    <?php endif; ?>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Nama Anggota</th>
                        <th>Total Simpanan</th>
                        <th>Jumlah Transaksi</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($savings_summary)): ?>
                        <tr>
                            <td colspan="4" style="text-align: center;">Tidak ada data simpanan.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($savings_summary as $row): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['member_name']); ?></td>
                            <td class="text-right"><?php echo "Rp " . number_format($row['total_amount'], 0, ',', '.'); ?></td>
                            <td style="text-align: center;"><?php echo htmlspecialchars($row['transaction_count']); ?></td>
                            <td class="actions-cell">
                                <button class="btn btn-sm btn-info" onclick="openSavingDetailModal('<?php echo htmlspecialchars($row['member_name'], ENT_QUOTES); ?>')">Detail</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Saving Modal -->
<div id="addSavingModal" class="modal">
    <div class="modal-content">
        <span class="close-btn" onclick="closeModal('addSavingModal')">&times;</span>
        <h2>Tambah Simpanan</h2>
        <form action="actions/handle_saving.php" method="POST" class="amount-form">
            <input type="hidden" name="action" value="create">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            <div class="form-group">
                <label for="add_saving_date">Tanggal</label>
                <input type="date" name="saving_date" required>
            </div>
            <div class="form-group">
                <label for="add_member_name">Nama Anggota</label>
                <input type="text" name="member_name" list="memberNamesDatalist" required>
                <datalist id="memberNamesDatalist">
                    <?php foreach ($memberNames as $name): ?>
                        <option value="<?php echo htmlspecialchars($name); ?>">
                    <?php endforeach; ?>
                </datalist>
            </div>
            <div class="form-group">
                <label for="add_saving_type">Tipe Simpanan</label>
                <select name="saving_type" required>
                    <option value="wajib">Wajib</option>
                    <option value="sukarela">Sukarela</option>
                </select>
            </div>
            <div class="form-group">
                <label for="add_amount">Jumlah</label>
                <input type="text" name="amount" data-format="number" inputmode="numeric" pattern="[0-9.,]*" required>
            </div>
            <div class="form-group">
                <label for="add_description">Deskripsi</label>
                <input type="text" name="description">
            </div>
            <div class="form-actions">
                <button type="button" class="btn btn-secondary" onclick="closeModal('addSavingModal')">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Saving Modal -->
<div id="editSavingModal" class="modal">
    <div class="modal-content">
        <span class="close-btn" onclick="closeModal('editSavingModal')">&times;</span>
        <h2>Edit Simpanan</h2>
        <form action="actions/handle_saving.php" method="POST" class="amount-form">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="id" id="edit_saving_id">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            <div class="form-group">
                <label for="edit_saving_date">Tanggal</label>
                <input type="date" name="saving_date" id="edit_saving_date" required>
            </div>
            <div class="form-group">
                <label for="edit_member_name">Nama Anggota</label>
                <input type="text" name="member_name" id="edit_member_name" list="memberNamesDatalist" required>
                <!-- Datalist is global -->
            </div>
            <div class="form-group">
                <label for="edit_saving_type">Tipe Simpanan</label>
                <select name="saving_type" id="edit_saving_type" required>
                    <option value="wajib">Wajib</option>
                    <option value="sukarela">Sukarela</option>
                </select>
            </div>
            <div class="form-group">
                <label for="edit_saving_amount">Jumlah</label>
                <input type="text" name="amount" id="edit_saving_amount" data-format="number" inputmode="numeric" pattern="[0-9.,]*" required>
            </div>
            <div class="form-group">
                <label for="edit_saving_description">Deskripsi</label>
                <input type="text" name="description" id="edit_saving_description">
            </div>
            <div class="form-actions">
                <button type="button" class="btn btn-secondary" onclick="closeModal('editSavingModal')">Batal</button>
                <button type="submit" class="btn btn-primary">Update</button>
            </div>
        </form>
    </div>
</div>

<!-- ** NEW ** Saving Detail Modal -->
<div id="savingDetailModal" class="modal">
    <div class="modal-content modal-lg">
        <span class="close-btn" onclick="closeModal('savingDetailModal')">&times;</span>
        <h2 id="savingDetailTitle">Detail Simpanan Anggota</h2>

        <div id="savingDetailSummary" class="loan-details">
            <!-- Summary will be loaded via AJAX -->
        </div>

        <hr>

        <h3>Riwayat Simpanan</h3>
        <div class="table-responsive mt-2">
            <table id="savingHistoryTable">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Tipe</th>
                        <th>Jumlah</th>
                        <th>Deskripsi</th>
                        <?php if (isAdmin()): ?>
                        <th>Aksi</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <!-- History will be loaded via AJAX -->
                    <tr><td colspan="<?php echo isAdmin() ? '5' : '4'; ?>">Loading...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

```

## koperasi-php/pages/transactions.php

```php
<?php
// pages/transactions.php
$transaction = new Transaction($db);
$stmt = $transaction->read();
$transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch unique names for datalist
$transactionNamesStmt = $transaction->readUniqueNames();
$transactionNames = $transactionNamesStmt->fetchAll(PDO::FETCH_COLUMN); // Fetch as a simple array of strings
?>

<div class="page-header">
    <h1>Kas Umum</h1>
    <?php if (isAdmin()): ?>
    <button class="btn btn-primary" onclick="openModal('addTransactionModal')">Tambah Transaksi</button>
    <?php endif; ?>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Nama</th>
                        <th>Deskripsi</th>
                        <th>Tipe</th>
                        <th>Jumlah</th>
                        <?php if (isAdmin()): ?>
                        <th>Aksi</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($transactions)): ?>
                        <tr>
                            <td colspan="<?php echo isAdmin() ? '6' : '5'; ?>" style="text-align: center;">Tidak ada data.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($transactions as $row): ?>
                        <tr>
                            <td><?php echo date("d M Y", strtotime(htmlspecialchars($row['transaction_date']))); ?></td>
                            <td><?php echo htmlspecialchars($row['name']); ?></td>
                            <td><?php echo htmlspecialchars($row['description']); ?></td>
                            <td>
                                <span class="badge <?php echo $row['type'] == 'pemasukan' ? 'badge-success' : 'badge-danger'; ?>">
                                    <?php echo htmlspecialchars(ucfirst($row['type'])); ?>
                                </span>
                            </td>
                            <td class="text-right"><?php echo "Rp " . number_format($row['amount'], 0, ',', '.'); ?></td>
                            <?php if (isAdmin()): ?>
                            <td class="actions-cell">
                                <button class="btn btn-sm btn-warning" onclick="openEditTransactionModal(<?php echo htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8'); ?>)">Edit</button>
                                <form action="actions/handle_transaction.php" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus data ini?');">
                                    <input type="hidden" name="id" value="<?php echo htmlspecialchars($row['id']); ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                    <button type="submit" class="btn btn-sm btn-danger">Hapus</button>
                                </form>
                            </td>
                            <?php endif; ?>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Transaction Modal -->
<div id="addTransactionModal" class="modal">
    <div class="modal-content">
        <span class="close-btn" onclick="closeModal('addTransactionModal')">&times;</span>
        <h2>Tambah Transaksi Baru</h2>
        <form action="actions/handle_transaction.php" method="POST" class="amount-form">
            <input type="hidden" name="action" value="create">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            <div class="form-group">
                <label for="add_transaction_date">Tanggal Transaksi</label>
                <input type="date" id="add_transaction_date" name="transaction_date" required>
            </div>
            <div class="form-group">
                <label for="add_name">Nama (Opsional)</label>
                <input type="text" id="add_name" name="name" list="transactionNamesDatalist">
                <datalist id="transactionNamesDatalist">
                    <?php foreach ($transactionNames as $name): ?>
                        <option value="<?php echo htmlspecialchars($name); ?>">
                    <?php endforeach; ?>
                </datalist>
            </div>
            <div class="form-group">
                <label for="add_description">Deskripsi</label>
                <input type="text" id="add_description" name="description" required>
            </div>
            <div class="form-group">
                <label for="add_type">Tipe</label>
                <select id="add_type" name="type" required>
                    <option value="pemasukan">Pemasukan</option>
                    <option value="pengeluaran">Pengeluaran</option>
                </select>
            </div>
            <div class="form-group">
                <label for="add_amount">Jumlah</label>
                <input type="text" id="add_amount" name="amount" data-format="number" inputmode="numeric" pattern="[0-9.,]*" required>
            </div>
            <div class="form-actions">
                <button type="button" class="btn btn-secondary" onclick="closeModal('addTransactionModal')">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Transaction Modal -->
<div id="editTransactionModal" class="modal">
    <div class="modal-content">
        <span class="close-btn" onclick="closeModal('editTransactionModal')">&times;</span>
        <h2>Edit Transaksi</h2>
        <form action="actions/handle_transaction.php" method="POST" class="amount-form">
            <input type="hidden" name="action" value="update">
            <input type="hidden" id="edit_id" name="id">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            <div class="form-group">
                <label for="edit_transaction_date">Tanggal Transaksi</label>
                <input type="date" id="edit_transaction_date" name="transaction_date" required>
            </div>
            <div class="form-group">
                <label for="edit_name">Nama (Opsional)</label>
                <input type="text" id="edit_name" name="name" list="transactionNamesDatalist">
                <!-- Datalist is global -->
            </div>
            <div class="form-group">
                <label for="edit_description">Deskripsi</label>
                <input type="text" id="edit_description" name="description" required>
            </div>
            <div class="form-group">
                <label for="edit_type">Tipe</label>
                <select id="edit_type" name="type" required>
                    <option value="pemasukan">Pemasukan</option>
                    <option value="pengeluaran">Pengeluaran</option>
                </select>
            </div>
            <div class="form-group">
                <label for="edit_amount">Jumlah</label>
                <input type="text" id="edit_amount" name="amount" data-format="number" inputmode="numeric" pattern="[0-9.,]*" required>
            </div>
            <div class="form-actions">
                <button type="button" class="btn btn-secondary" onclick="closeModal('editTransactionModal')">Batal</button>
                <button type="submit" class="btn btn-primary">Update</button>
            </div>
        </form>
    </div>
</div>

```
