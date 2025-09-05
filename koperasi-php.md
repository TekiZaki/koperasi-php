# Code Dump for koperasi-php

---

## koperasi-php/ajax_get_loan_details.php

```php
<?php
// ajax_get_loan_details.php
session_start();
header('Content-Type: application/json');

// Basic security checks
if (!isset($_SESSION['user_id']) || !isset($_GET['loan_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Bad Request']);
    exit();
}

require_once 'config/Database.php';
require_once 'models/Loan.php';

$database = new Database();
$db = $database->getConnection();
$loan = new Loan($db);

$loan->id = intval($_GET['loan_id']);

// Fetch loan details
$loan_stmt = $loan->readOne();
if ($loan_stmt->rowCount() == 0) {
    http_response_code(404);
    echo json_encode(['error' => 'Loan not found']);
    exit();
}
$loan_details = $loan_stmt->fetch(PDO::FETCH_ASSOC);

// Fetch payment history
$payments_stmt = $loan->readLoanPayments();
$payments = $payments_stmt->fetchAll(PDO::FETCH_ASSOC);

// Combine and return as JSON
echo json_encode([
    'loan' => $loan_details,
    'payments' => $payments
]);
```

## koperasi-php/ajax_get_saving_details.php

```php
<?php
// ajax_get_saving_details.php
session_start();
header('Content-Type: application/json');

// Basic security checks
if (!isset($_SESSION['user_id']) || !isset($_GET['member_name'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Bad Request']);
    exit();
}

require_once 'config/Database.php';
require_once 'models/Saving.php';

$database = new Database();
$db = $database->getConnection();
$saving = new Saving($db);

// Sanitize member name from GET parameter
$saving->member_name = urldecode($_GET['member_name']);

// Fetch saving details for the member
$stmt = $saving->readByMemberName();

$savings_details = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Return as JSON, even if it's an empty array
echo json_encode($savings_details);
```

## koperasi-php/index.php

```php
<?php
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

$database = new Database();
$db = $database->getConnection();

$page = $_GET['page'] ?? 'dashboard';

include 'includes/header.php';

// Page routing
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
    $database = new Database();
    $db = $database->getConnection();
    $user = new User($db);

    $user->username = $_POST['username'];
    $stmt = $user->findByUsername();

    if ($stmt->rowCount() > 0) {
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (password_verify($_POST['password'], $row['password'])) {
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['user_name'] = $row['name'];
            $_SESSION['user_role'] = $row['role'];
            header("Location: index.php");
            exit();
        } else {
            $error_message = "Password salah.";
        }
    } else {
        $error_message = "Username tidak ditemukan.";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Koperasi Masjid</title>
    <!-- FIX: Link to the main stylesheet -->
    <link rel="stylesheet" href="assets/css/style.css">
    <!-- FIX: The <style> block has been removed and its contents moved to style.css -->
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
                <input type="text" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
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
session_unset();
session_destroy();
header("Location: login.php");
exit();
```

## koperasi-php/actions/handle_infaq.php

```php
<?php
session_start();
require_once '../config/Database.php';
require_once '../models/Infaq.php';

// Authorization check: Must be admin or superadmin
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'superadmin'])) {
    header("Location: ../index.php?page=infaq&error=unauthorized");
    exit();
}

$database = new Database();
$db = $database->getConnection();
$infaq = new Infaq($db);

$action = $_POST['action'] ?? '';

try {
    if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $infaq->infaq_date = $_POST['infaq_date'];
        $infaq->donor_name = $_POST['donor_name'];
        $infaq->description = $_POST['description'];
        $infaq->type = $_POST['type'];
        $infaq->amount = $_POST['amount'];
        $infaq->created_by_user_id = $_SESSION['user_id'];
        $infaq->create();
    } elseif ($action === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $infaq->id = $_POST['id'];
        $infaq->infaq_date = $_POST['infaq_date'];
        $infaq->donor_name = $_POST['donor_name'];
        $infaq->description = $_POST['description'];
        $infaq->type = $_POST['type'];
        $infaq->amount = $_POST['amount'];
        $infaq->update();
    } elseif ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $infaq->id = $_POST['id'];
        $infaq->delete();
    }
} catch (Exception $e) {
    header("Location: ../index.php?page=infaq&status=error");
    exit();
}

header("Location: ../index.php?page=infaq&status=success");
exit();
```

## koperasi-php/actions/handle_loan.php

```php
<?php
session_start();
require_once '../config/Database.php';
require_once '../models/Loan.php';

// Authorization check: Must be admin or superadmin
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'superadmin'])) {
    header("Location: ../index.php?page=loans&error=unauthorized");
    exit();
}

$database = new Database();
$db = $database->getConnection();
$loan = new Loan($db);

$action = $_POST['action'] ?? '';

try {
    if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $loan->loan_date = $_POST['loan_date'];
        $loan->member_name = $_POST['member_name'];
        $loan->loan_amount = $_POST['loan_amount'];
        $loan->tenor_months = $_POST['tenor_months'];
        $loan->status = 'aktif'; // New loans are always aktif
        $loan->created_by_user_id = $_SESSION['user_id'];
        $loan->create();
    } elseif ($action === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $loan->id = $_POST['id'];
        $loan->loan_date = $_POST['loan_date'];
        $loan->member_name = $_POST['member_name'];
        $loan->loan_amount = $_POST['loan_amount'];
        $loan->tenor_months = $_POST['tenor_months'];
        $loan->status = $_POST['status'];

        if ($loan->update()) {
            // After updating, re-check the status in case the loan amount changed
            $loan->updateLoanStatus($_POST['id']);
        }

    } elseif ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $loan->id = $_POST['id'];
        $loan->delete();
    }
} catch (Exception $e) {
    header("Location: ../index.php?page=loans&status=error");
    exit();
}

header("Location: ../index.php?page=loans&status=success");
exit();
```

## koperasi-php/actions/handle_loan_payment.php

```php
<?php
session_start();
require_once '../config/Database.php';
require_once '../models/Loan.php';

// Authorization check: Must be admin or superadmin
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'superadmin'])) {
    header("Location: ../index.php?page=loans&error=unauthorized");
    exit();
}

// Check if it's a POST request with the required loan_id
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['loan_id'])) {
    header("Location: ../index.php?page=loans&status=error");
    exit();
}

$database = new Database();
$db = $database->getConnection();
$loan = new Loan($db);

$loan_id = $_POST['loan_id'];

try {
    // Assign POST data to the model properties for a new payment
    $loan->id = $loan_id; // The 'id' property of the model is used for loan_id context
    $loan->payment_date = $_POST['payment_date'];
    $loan->payment_amount = $_POST['payment_amount'];
    $loan->payment_month_no = !empty($_POST['payment_month_no']) ? $_POST['payment_month_no'] : null;
    $loan->payment_description = $_POST['description'];
    $loan->created_by_user_id = $_SESSION['user_id'];

    if ($loan->addPayment()) {
        // If payment is added successfully, update the loan's main status (e.g., to 'selesai')
        $loan->updateLoanStatus($loan_id);
    } else {
        throw new Exception("Failed to add payment.");
    }

} catch (Exception $e) {
    header("Location: ../index.php?page=loans&status=error");
    exit();
}

header("Location: ../index.php?page=loans&status=success");
exit();
```

## koperasi-php/actions/handle_saving.php

```php
<?php
session_start();
require_once '../config/Database.php';
require_once '../models/Saving.php';

// Authorization check: Must be admin or superadmin
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'superadmin'])) {
    header("Location: ../index.php?page=savings&error=unauthorized");
    exit();
}

$database = new Database();
$db = $database->getConnection();
$saving = new Saving($db);

$action = $_POST['action'] ?? '';

try {
    if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $saving->saving_date = $_POST['saving_date'];
        $saving->member_name = $_POST['member_name'];
        $saving->saving_type = $_POST['saving_type'];
        $saving->amount = $_POST['amount'];
        $saving->description = $_POST['description'];
        $saving->created_by_user_id = $_SESSION['user_id'];
        $saving->create();
    } elseif ($action === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $saving->id = $_POST['id'];
        $saving->saving_date = $_POST['saving_date'];
        $saving->member_name = $_POST['member_name'];
        $saving->saving_type = $_POST['saving_type'];
        $saving->amount = $_POST['amount'];
        $saving->description = $_POST['description'];
        $saving->update();
    } elseif ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $saving->id = $_POST['id'];
        $saving->delete();
    }
} catch (Exception $e) {
    header("Location: ../index.php?page=savings&status=error");
    exit();
}

header("Location: ../index.php?page=savings&status=success");
exit();
```

## koperasi-php/actions/handle_transaction.php

```php
<?php
session_start();
require_once '../config/Database.php';
require_once '../models/Transaction.php';

// Authorization check: Must be admin or superadmin
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'superadmin'])) {
    header("Location: ../index.php?page=transactions&error=unauthorized");
    exit();
}

$database = new Database();
$db = $database->getConnection();
$transaction = new Transaction($db);

$action = $_POST['action'] ?? '';

try {
    if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $transaction->transaction_date = $_POST['transaction_date'];
        $transaction->name = $_POST['name'];
        $transaction->description = $_POST['description'];
        $transaction->type = $_POST['type'];
        $transaction->amount = $_POST['amount'];
        $transaction->created_by_user_id = $_SESSION['user_id'];
        $transaction->create();
    } elseif ($action === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $transaction->id = $_POST['id'];
        $transaction->transaction_date = $_POST['transaction_date'];
        $transaction->name = $_POST['name'];
        $transaction->description = $_POST['description'];
        $transaction->type = $_POST['type'];
        $transaction->amount = $_POST['amount'];
        $transaction->update();
    } elseif ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $transaction->id = $_POST['id'];
        $transaction->delete();
    }
} catch (Exception $e) {
    // Optional: Log error
    // error_log("Transaction action failed: " . $e->getMessage());
    header("Location: ../index.php?page=transactions&status=error");
    exit();
}

header("Location: ../index.php?page=transactions&status=success");
exit();
```

## koperasi-php/assets/css/style.css

```css
/* --- General & Variables --- */
:root {
  --primary-color: #007bff;
  --secondary-color: #6c757d;
  --success-color: #28a745;
  --danger-color: #dc3545;
  --warning-color: #ffc107;
  --light-color: #f8f9fa;
  --dark-color: #343a40;
  --bg-color: #f4f7f6;
  --sidebar-bg: #ffffff;
  --sidebar-width: 250px;
  --header-height: 60px;
  --border-color: #dee2e6;
  --card-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}

* {
  box-sizing: border-box;
  margin: 0;
  padding: 0;
}

/* --- Force consistent viewport handling --- */
html {
  overflow-x: hidden; /* Prevent horizontal scroll on html */
}

body {
  font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto,
    "Helvetica Neue", Arial, sans-serif;
  background-color: var(--bg-color);
  color: var(--dark-color);
  line-height: 1.6;
  overflow-x: hidden; /* Prevent horizontal scroll on body */
  min-width: 320px; /* Minimum supported width */
}

/* --- Layout --- */
.app-container {
  display: flex;
  min-height: 100vh;
}

.sidebar {
  width: var(--sidebar-width);
  background-color: var(--sidebar-bg);
  border-right: 1px solid var(--border-color);
  position: fixed;
  top: 0;
  left: 0;
  height: 100%;
  transition: transform 0.3s ease-in-out;
  z-index: 1000;
}

.main-content {
  flex-grow: 1;
  margin-left: var(--sidebar-width);
  display: flex;
  flex-direction: column;
  transition: margin-left 0.3s ease-in-out;
}

.content-body {
  padding: 1.5rem;
  flex-grow: 1;
}

/* --- Header --- */
.app-header {
  height: var(--header-height);
  background-color: #fff;
  border-bottom: 1px solid var(--border-color);
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 1.5rem;
  position: sticky;
  top: 0;
  z-index: 999;
}

.menu-toggle {
  display: none;
  background: none;
  border: none;
  font-size: 1.5rem;
  cursor: pointer;
}

.user-info {
  display: flex;
  align-items: center;
}

.user-info .logout-btn {
  margin-left: 1rem;
  background-color: var(--danger-color);
  color: #fff;
  padding: 0.3rem 0.8rem;
  border-radius: 5px;
  text-decoration: none;
  font-size: 0.9rem;
}

/* --- Sidebar --- */
.sidebar-header {
  padding: 1.5rem;
  text-align: center;
  border-bottom: 1px solid var(--border-color);
}
.sidebar-nav ul {
  list-style: none;
  padding: 1rem 0;
}
.sidebar-nav a {
  display: block;
  padding: 0.8rem 1.5rem;
  color: var(--dark-color);
  text-decoration: none;
  transition: background-color 0.2s;
  border-left: 3px solid transparent;
}
.sidebar-nav a:hover {
  background-color: #f1f1f1;
}
.sidebar-nav a.aktif {
  background-color: #e9ecef;
  font-weight: 600;
  color: var(--primary-color);
  border-left-color: var(--primary-color);
}

/* --- Content --- */
.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
}

.card {
  background-color: #fff;
  border: 1px solid var(--border-color);
  border-radius: 0.375rem;
  box-shadow: var(--card-shadow);
}
.card-body {
  padding: 1.5rem;
}

/* --- Tables --- */
.table-responsive {
  width: 100%;
  overflow-x: auto;
}
table {
  width: 100%;
  border-collapse: collapse;
}
th,
td {
  padding: 0.75rem;
  text-align: left;
  border-bottom: 1px solid var(--border-color);
  vertical-align: middle;
}
th {
  background-color: var(--light-color);
  font-weight: 600;
}
tbody tr:hover {
  background-color: #f8f9fa;
}
.text-right {
  text-align: right;
}
/* FIX: Keep action buttons on one line in tables */
td.actions-cell {
  white-space: nowrap;
}
td.actions-cell form {
  display: inline-block;
  margin-left: 4px;
}

/* --- Forms & Buttons --- */
.btn {
  padding: 0.5rem 1rem;
  border: 1px solid transparent;
  border-radius: 0.25rem;
  cursor: pointer;
  font-size: 0.9rem;
  text-decoration: none;
  display: inline-block;
}
.btn-sm {
  padding: 0.25rem 0.5rem;
  font-size: 0.8rem;
}
.btn-primary {
  background-color: var(--primary-color);
  color: #fff;
}
.btn-secondary {
  background-color: var(--secondary-color);
  color: #fff;
}
.btn-danger {
  background-color: var(--danger-color);
  color: #fff;
}
.btn-warning {
  background-color: var(--warning-color);
  color: #212529;
}
.form-group {
  margin-bottom: 1rem;
}
.form-group label {
  display: block;
  margin-bottom: 0.5rem;
  font-weight: 500;
}
.form-group input,
.form-group select,
.form-group textarea {
  width: 100%;
  padding: 0.5rem;
  border: 1px solid var(--border-color);
  border-radius: 0.25rem;
  font-family: inherit;
  font-size: 1rem;
}
.form-actions {
  margin-top: 1.5rem;
  text-align: right;
}
.form-actions .btn {
  margin-left: 0.5rem;
}

/* --- Badges --- */
.badge {
  padding: 0.3em 0.6em;
  font-size: 75%;
  font-weight: 700;
  border-radius: 0.25rem;
  color: #fff;
}
.badge-success {
  background-color: var(--success-color);
}
.badge-danger {
  background-color: var(--danger-color);
}
.badge-warning {
  background-color: var(--warning-color);
  color: #212529;
}
.badge-info {
  background-color: #17a2b8;
}

/* --- Modal --- */
.modal {
  display: none;
  position: fixed;
  z-index: 1050;
  left: 0;
  top: 0;
  width: 100%;
  height: 100%;
  overflow: auto;
  background-color: rgba(0, 0, 0, 0.5);
}
.modal-content {
  background-color: #fefefe;
  margin: 5% auto;
  padding: 2rem;
  border: 1px solid #888;
  width: 80%;
  max-width: 600px;
  border-radius: 8px;
  position: relative;
  animation: slide-down 0.3s ease-out;
}
@keyframes slide-down {
  from {
    transform: translateY(-30px);
    opacity: 0;
  }
  to {
    transform: translateY(0);
    opacity: 1;
  }
}
.close-btn {
  color: #aaa;
  position: absolute;
  top: 10px;
  right: 20px;
  font-size: 28px;
  font-weight: bold;
  cursor: pointer;
}
.close-btn:hover,
.close-btn:focus {
  color: black;
}

/* --- Login Page Styles (Moved from login.php) --- */
.login-page {
  font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
  background-color: #f0f2f5;
  display: flex;
  justify-content: center;
  align-items: center;
  height: 100vh;
  margin: 0;
  padding: 1rem;
}
.login-container {
  background-color: #fff;
  padding: 2rem 3rem;
  border-radius: 8px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
  text-align: center;
  width: 100%;
  max-width: 400px;
}
.login-container h1 {
  color: #333;
  margin-bottom: 0.5rem;
}
.login-container p {
  color: #777;
  margin-bottom: 2rem;
}
.login-container .form-group {
  margin-bottom: 1.5rem;
  text-align: left;
}
.login-container label {
  display: block;
  margin-bottom: 0.5rem;
  color: #555;
  font-weight: 600;
}
.login-container input[type="text"],
.login-container input[type="password"] {
  width: 100%;
  padding: 0.8rem;
  border: 1px solid #ddd;
  border-radius: 4px;
  box-sizing: border-box;
  font-size: 1rem;
}
.login-container button {
  width: 100%;
  padding: 0.9rem;
  background-color: #007bff;
  color: white;
  border: none;
  border-radius: 4px;
  font-size: 1rem;
  font-weight: 600;
  cursor: pointer;
  transition: background-color 0.3s ease;
}
.login-container button:hover {
  background-color: #0056b3;
}
.error-message {
  color: #d93025;
  background-color: #f8d7da;
  border: 1px solid #f5c6cb;
  padding: 0.75rem;
  border-radius: 4px;
  margin-bottom: 1rem;
}

/* --- Responsive Design --- */
@media (max-width: 992px) {
  :root {
    --sidebar-width: 220px;
  }
}

@media (max-width: 768px) {
  .sidebar {
    transform: translateX(-100%);
  }
  .sidebar.aktif {
    transform: translateX(0);
    box-shadow: 0 0 15px rgba(0, 0, 0, 0.2);
  }
  .main-content {
    margin-left: 0;
    /* Ensure main content never exceeds viewport width */
    max-width: 100vw;
    overflow-x: hidden;
  }
  .menu-toggle {
    display: block;
    /* FIX: Ensure toggle button is always clickable by placing it on top of the sidebar */
    position: relative;
    z-index: 1001;
  }
  .page-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 1rem;
    margin-bottom: 1rem;
  }
  .page-header h1 {
    margin-bottom: 0;
    font-size: 1.5rem;
  }
  .modal-content {
    width: 95%;
    margin: 10% auto;
    padding: 1.5rem;
    max-height: 90vh;
    overflow-y: auto;
  }
  /* FIX: Reduce padding for better mobile view */
  .content-body {
    padding: 1rem 0.5rem; /* Reduce horizontal padding more on mobile */
  }
  th,
  td {
    padding: 0.5rem;
  }
  /* FIX: Adjust login container padding for small screens */
  .login-container {
    padding: 2rem 1.5rem;
  }

  /* Card improvements for mobile */
  .card {
    margin-left: 0.5rem;
    margin-right: 0.5rem;
  }

  .card-body {
    padding: 1rem 0.5rem; /* Reduce card padding */
  }

  /* Table responsive container with better control */
  .table-responsive {
    max-width: 100%;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch; /* Smooth scrolling on iOS */
    border: 1px solid var(--border-color);
    border-radius: 0.25rem;
  }

  /* Table styling for mobile */
  table {
    min-width: 600px; /* Set minimum width to ensure readability */
    font-size: 0.85rem; /* Slightly smaller font on mobile */
  }

  th,
  td {
    padding: 0.4rem 0.3rem; /* Reduce padding further */
    white-space: nowrap; /* Prevent text wrapping in cells */
  }

  /* Make specific columns more compact on mobile */
  th:first-child,
  td:first-child {
    min-width: 80px; /* Date column */
  }

  .text-right {
    min-width: 90px; /* Amount columns */
  }

  /* Action buttons - stack vertically on very small screens */
  .actions-cell {
    min-width: 120px;
  }

  .actions-cell form {
    display: block;
    margin-top: 0.25rem;
    margin-left: 0;
  }

  .actions-cell .btn-sm {
    padding: 0.2rem 0.4rem;
    font-size: 0.75rem;
    width: 100%;
  }

  /* Badge adjustments */
  .badge {
    font-size: 0.65rem;
    padding: 0.2em 0.4em;
  }

  /* Modal adjustments for mobile */
  .modal-content.modal-lg {
    width: 98%;
    max-width: none;
  }

  /* Form improvements in modals */
  .form-row {
    flex-direction: column;
  }

  .form-group input,
  .form-group select,
  .form-group textarea {
    font-size: 16px; /* Prevent zoom on iOS */
  }

  /* Dashboard summary grid */
  .dashboard-summary {
    grid-template-columns: 1fr;
    gap: 1rem;
  }

  .summary-card {
    padding: 1rem;
  }

  .summary-card p {
    font-size: 1.5rem; /* Smaller summary numbers on mobile */
  }
}

/* --- Very small screens (phones in portrait) --- */
@media (max-width: 480px) {
  .content-body {
    padding: 0.75rem 0.25rem;
  }

  .card {
    margin-left: 0.25rem;
    margin-right: 0.25rem;
  }

  table {
    font-size: 0.8rem;
  }

  th,
  td {
    padding: 0.3rem 0.2rem;
  }

  /* Even more compact action buttons */
  .actions-cell {
    min-width: 100px;
  }

  .btn-sm {
    font-size: 0.7rem;
    padding: 0.15rem 0.3rem;
  }

  /* Login page adjustments */
  .login-container {
    padding: 1.5rem 1rem;
    margin: 0 0.5rem;
  }

  .login-container h1 {
    font-size: 1.3rem;
  }
}

/* --- Landscape orientation on mobile --- */
@media (max-width: 768px) and (orientation: landscape) {
  .modal-content {
    max-height: 85vh;
    margin: 2% auto;
  }

  .app-header {
    height: 50px; /* Smaller header in landscape */
  }

  .content-body {
    padding-top: 1rem;
  }
}

/* --- Additional Dashboard Styles --- */
.dashboard-summary {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 1.5rem;
}
.summary-card {
  background-color: #fff;
  padding: 1.5rem;
  border-radius: 8px;
  box-shadow: var(--card-shadow);
  border-left: 5px solid var(--primary-color);
}
.summary-card:nth-child(2) {
  border-color: var(--success-color);
}
.summary-card:nth-child(3) {
  border-color: var(--warning-color);
}
.summary-card:nth-child(4) {
  border-color: #17a2b8;
}
.summary-card h3 {
  margin-bottom: 0.5rem;
  font-size: 1rem;
  color: #6c757d;
}
.summary-card p {
  font-size: 1.75rem;
  font-weight: 600;
}
.mt-4 {
  margin-top: 2rem;
}
.text-success {
  color: var(--success-color);
}
.text-danger {
  color: var(--danger-color);
}

/* --- Additional Loan Modal Styles --- */
.modal-content.modal-lg {
  max-width: 800px;
}
.loan-details .detail-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0.75rem;
  margin-bottom: 1rem;
}
.form-row {
  display: flex;
  gap: 1rem;
}
.form-row .form-group {
  flex: 1;
}
@media (max-width: 576px) {
  .form-row {
    flex-direction: column;
    gap: 0; /* form-group margin-bottom will handle spacing */
  }
  /* FIX: Stack loan detail grid on small screens */
  .loan-details .detail-grid {
    grid-template-columns: 1fr;
    gap: 0.5rem;
  }
}
#paymentHistoryTable {
  margin-top: 1rem;
}

/* --- Mobile Table Enhancements (moved from main.js) --- */
.scroll-indicator {
  background-color: #e9ecef;
  color: #6c757d;
  text-align: center;
  padding: 0.5rem;
  font-size: 0.85rem;
  border: 1px solid var(--border-color);
  border-bottom: none;
  border-radius: 0.25rem 0.25rem 0 0;
  transition: opacity 0.3s ease;
}

@media (min-width: 769px) {
  .scroll-indicator {
    display: none;
  }
}

/* Touch device improvements */
.touch-device .btn:hover {
  background-color: var(--primary-color); /* Remove hover effects on touch */
}

.touch-device .sidebar-nav a:hover {
  background-color: transparent; /* Remove hover effects on touch */
}

/* Alternative table layout for very small screens */
@media (max-width: 480px) {
  .table-stacked {
    display: block;
  }

  .table-stacked thead {
    display: none;
  }

  .table-stacked tbody,
  .table-stacked tr,
  .table-stacked td {
    display: block;
  }

  .table-stacked tr {
    border: 1px solid var(--border-color);
    margin-bottom: 1rem;
    padding: 0.5rem;
    border-radius: 0.25rem;
  }

  .table-stacked td {
    text-align: left !important;
    padding: 0.25rem 0;
    border: none;
  }

  .table-stacked td:before {
    content: attr(data-label) ": ";
    font-weight: bold;
    color: var(--primary-color);
  }

  .table-stacked .actions-cell {
    text-align: center;
    padding-top: 0.5rem;
    margin-top: 0.5rem;
    border-top: 1px solid var(--border-color);
  }

  .table-stacked .actions-cell:before {
    content: "";
  }
}
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
                                   <input type="hidden" name="id" value="${s.id}">
                                   <input type="hidden" name="action" value="delete">
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
// koperasi/config/Database.php
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
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $exception) {
            echo "Connection error: " . $exception->getMessage();
        }
        return $this->conn;
    }
}
```

## koperasi-php/includes/footer.php

```php
           </main>
        </div>
    </div>
    <script src="assets/js/main.js"></script>
</body>
</html>
```

## koperasi-php/includes/header.php

```php
<?php
//
// This check should be at the top of any protected page
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
$user_name = $_SESSION['user_name'] ?? 'Guest';
$user_role = $_SESSION['user_role'] ?? 'user';

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
    <link rel="stylesheet" href="assets/css/style.css">
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
        <h3>Koperasi Masjid</h3>
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
```

## koperasi-php/models/Infaq.php

```php
<?php
// koperasi-php/models/Infaq.php
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
        $query = "SELECT id, infaq_date, description, donor_name, amount, type FROM " . $this->table_name . " ORDER BY infaq_date DESC, id DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    // Buat data infaq baru
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " SET infaq_date = :infaq_date, description = :description, donor_name = :donor_name, amount = :amount, type = :type, created_by_user_id = :created_by_user_id";
        $stmt = $this->conn->prepare($query);

        // Sanitize
        $this->infaq_date = htmlspecialchars(strip_tags($this->infaq_date));
        $this->description = htmlspecialchars(strip_tags($this->description));
        $this->donor_name = htmlspecialchars(strip_tags($this->donor_name));
        $this->amount = htmlspecialchars(strip_tags($this->amount));
        $this->type = htmlspecialchars(strip_tags($this->type));
        $this->created_by_user_id = htmlspecialchars(strip_tags($this->created_by_user_id));

        // Bind values
        $stmt->bindParam(":infaq_date", $this->infaq_date);
        $stmt->bindParam(":description", $this->description);
        $stmt->bindParam(":donor_name", $this->donor_name);
        $stmt->bindParam(":amount", $this->amount);
        $stmt->bindParam(":type", $this->type);
        $stmt->bindParam(":created_by_user_id", $this->created_by_user_id);

        if ($stmt->execute()) {
            return true;
        }
        error_log("Infaq create error: " . implode(" ", $stmt->errorInfo()));
        return false;
    }

    // Perbarui data infaq
    public function update() {
        $query = "UPDATE " . $this->table_name . " SET infaq_date = :infaq_date, description = :description, donor_name = :donor_name, amount = :amount, type = :type WHERE id = :id";
        $stmt = $this->conn->prepare($query);

        // Sanitize and bind
        $this->infaq_date = htmlspecialchars(strip_tags($this->infaq_date));
        $this->description = htmlspecialchars(strip_tags($this->description));
        $this->donor_name = htmlspecialchars(strip_tags($this->donor_name));
        $this->amount = htmlspecialchars(strip_tags($this->amount));
        $this->type = htmlspecialchars(strip_tags($this->type));
        $this->id = htmlspecialchars(strip_tags($this->id));

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

        $this->id = htmlspecialchars(strip_tags($this->id));
        $stmt->bindParam(':id', $this->id);

        if ($stmt->execute()) {
            return true;
        }
        error_log("Infaq delete error: " . implode(" ", $stmt->errorInfo()));
        return false;
    }

    // New method to read unique donor names
    public function readUniqueDonorNames() {
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
    public $tenor_months; // Changed to INT(11) in DB, PHP handles it fine
    public $status; // aktif, selesai, gagal
    public $created_by_user_id;
    public $created_at;

    // Properti tambahan untuk pembayaran (jika diperlukan dalam satu operasi)
    public $payment_id;
    public $payment_date;
    public $payment_amount;
    public $payment_month_no;
    public $payment_description; // Menggunakan prefix 'payment_' agar tidak bentrok

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
        $this->id = htmlspecialchars(strip_tags($this->id));
        $stmt->bindParam(':id', $this->id);
        $stmt->execute();
        return $stmt;
    }

    // Buat data piutang baru
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " SET member_name = :member_name, loan_amount = :loan_amount, loan_date = :loan_date, tenor_months = :tenor_months, status = :status, created_by_user_id = :created_by_user_id";
        $stmt = $this->conn->prepare($query);

        // Sanitize
        $this->member_name = htmlspecialchars(strip_tags($this->member_name));
        $this->loan_amount = htmlspecialchars(strip_tags($this->loan_amount));
        $this->loan_date = htmlspecialchars(strip_tags($this->loan_date));
        $this->tenor_months = htmlspecialchars(strip_tags($this->tenor_months)); // Still sanitize, but no longer limited to 10
        $this->status = htmlspecialchars(strip_tags($this->status));
        $this->created_by_user_id = htmlspecialchars(strip_tags($this->created_by_user_id));

        // Bind values
        $stmt->bindParam(":member_name", $this->member_name);
        $stmt->bindParam(":loan_amount", $this->loan_amount);
        $stmt->bindParam(":loan_date", $this->loan_date);
        $stmt->bindParam(":tenor_months", $this->tenor_months);
        $stmt->bindParam(":status", $this->status);
        $stmt->bindParam(":created_by_user_id", $this->created_by_user_id);

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

        // Sanitize and bind
        $this->member_name = htmlspecialchars(strip_tags($this->member_name));
        $this->loan_amount = htmlspecialchars(strip_tags($this->loan_amount));
        $this->loan_date = htmlspecialchars(strip_tags($this->loan_date));
        $this->tenor_months = htmlspecialchars(strip_tags($this->tenor_months)); // Still sanitize, but no longer limited to 10
        $this->status = htmlspecialchars(strip_tags($this->status));
        $this->id = htmlspecialchars(strip_tags($this->id));

        $stmt->bindParam(":member_name", $this->member_name);
        $stmt->bindParam(":loan_amount", $this->loan_amount);
        $stmt->bindParam(":loan_date", $this->loan_date);
        $stmt->bindParam(":tenor_months", $this->tenor_months);
        $stmt->bindParam(":status", $this->status);
        $stmt->bindParam(":id", $this->id);

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

        $this->id = htmlspecialchars(strip_tags($this->id));
        $stmt->bindParam(':id', $this->id);

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
        $this->id = htmlspecialchars(strip_tags($this->id)); // 'id' properti digunakan sebagai loan_id
        $stmt->bindParam(':loan_id', $this->id);
        $stmt->execute();
        return $stmt;
    }

    // Tambahkan pembayaran baru untuk piutang
    public function addPayment() {
        $query = "INSERT INTO " . $this->payments_table . " SET loan_id = :loan_id, payment_date = :payment_date, payment_amount = :payment_amount, payment_month_no = :payment_month_no, description = :description, created_by_user_id = :created_by_user_id";
        $stmt = $this->conn->prepare($query);

        // Sanitize
        $this->id = htmlspecialchars(strip_tags($this->id)); // 'id' properti digunakan sebagai loan_id
        $this->payment_date = htmlspecialchars(strip_tags($this->payment_date));
        $this->payment_amount = htmlspecialchars(strip_tags($this->payment_amount));
        $this->payment_month_no = htmlspecialchars(strip_tags($this->payment_month_no));
        $this->payment_description = htmlspecialchars(strip_tags($this->payment_description));
        $this->created_by_user_id = htmlspecialchars(strip_tags($this->created_by_user_id));

        // Bind values
        $stmt->bindParam(":loan_id", $this->id);
        $stmt->bindParam(":payment_date", $this->payment_date);
        $stmt->bindParam(":payment_amount", $this->payment_amount);
        $stmt->bindParam(":payment_month_no", $this->payment_month_no);
        $stmt->bindParam(":description", $this->payment_description);
        $stmt->bindParam(":created_by_user_id", $this->created_by_user_id);

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
            $newStatus = $loan_data['remaining_amount'] <= 0 ? 'selesai' : 'aktif';

            // Only update if the status actually changes
            if ($loan_data['status'] !== $newStatus) {
                $query = "UPDATE " . $this->table_name . " SET status = :status WHERE id = :id";
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(':status', $newStatus);
                $stmt->bindParam(':id', $loan_id);
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
// koperasi-php/models/Saving.php
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

    // Baca semua data simpanan
    public function read() {
        $query = "SELECT id, member_name, saving_type, saving_date, amount, description FROM " . $this->table_name . " ORDER BY saving_date DESC, id DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    // ** NEW ** method to read savings grouped by member for the main view
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

    // ** NEW ** method to read all savings for a specific member for the detail modal
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

        // Sanitize
        $this->member_name = htmlspecialchars(strip_tags($this->member_name));
        // Bind
        $stmt->bindParam(":member_name", $this->member_name);

        $stmt->execute();
        return $stmt;
    }

    // Buat data simpanan baru
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " SET member_name = :member_name, saving_type = :saving_type, saving_date = :saving_date, amount = :amount, description = :description, created_by_user_id = :created_by_user_id";
        $stmt = $this->conn->prepare($query);

        // Sanitize
        $this->member_name = htmlspecialchars(strip_tags($this->member_name));
        $this->saving_type = htmlspecialchars(strip_tags($this->saving_type));
        $this->saving_date = htmlspecialchars(strip_tags($this->saving_date));
        $this->amount = htmlspecialchars(strip_tags($this->amount));
        $this->description = htmlspecialchars(strip_tags($this->description));
        $this->created_by_user_id = htmlspecialchars(strip_tags($this->created_by_user_id));

        // Bind values
        $stmt->bindParam(":member_name", $this->member_name);
        $stmt->bindParam(":saving_type", $this->saving_type);
        $stmt->bindParam(":saving_date", $this->saving_date);
        $stmt->bindParam(":amount", $this->amount);
        $stmt->bindParam(":description", $this->description);
        $stmt->bindParam(":created_by_user_id", $this->created_by_user_id);

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

        // Sanitize and bind
        $this->member_name = htmlspecialchars(strip_tags($this->member_name));
        $this->saving_type = htmlspecialchars(strip_tags($this->saving_type));
        $this->saving_date = htmlspecialchars(strip_tags($this->saving_date));
        $this->amount = htmlspecialchars(strip_tags($this->amount));
        $this->description = htmlspecialchars(strip_tags($this->description));
        $this->id = htmlspecialchars(strip_tags($this->id));

        $stmt->bindParam(":member_name", $this->member_name);
        $stmt->bindParam(":saving_type", $this->saving_type);
        $stmt->bindParam(":saving_date", $this->saving_date);
        $stmt->bindParam(":amount", $this->amount);
        $stmt->bindParam(":description", $this->description);
        $stmt->bindParam(":id", $this->id);

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

        $this->id = htmlspecialchars(strip_tags($this->id));
        $stmt->bindParam(':id', $this->id);

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
// koperasi-php/models/Transaction.php
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

        // Sanitize
        $this->transaction_date = htmlspecialchars(strip_tags($this->transaction_date));
        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->description = htmlspecialchars(strip_tags($this->description));
        $this->type = htmlspecialchars(strip_tags($this->type));
        $this->amount = htmlspecialchars(strip_tags($this->amount));
        $this->created_by_user_id = htmlspecialchars(strip_tags($this->created_by_user_id));

        // Bind values
        $stmt->bindParam(":transaction_date", $this->transaction_date);
        $stmt->bindParam(":name", $this->name);
        $stmt->bindParam(":description", $this->description);
        $stmt->bindParam(":type", $this->type);
        $stmt->bindParam(":amount", $this->amount);
        $stmt->bindParam(":created_by_user_id", $this->created_by_user_id);

        if ($stmt->execute()) {
            return true;
        }
        error_log("Transaction create error: " . implode(" ", $stmt->errorInfo()));
        return false;
    }

    public function update() {
        $query = "UPDATE " . $this->table_name . " SET transaction_date = :transaction_date, name = :name, description = :description, type = :type, amount = :amount WHERE id = :id";
        $stmt = $this->conn->prepare($query);

        // Sanitize and bind
        $this->transaction_date = htmlspecialchars(strip_tags($this->transaction_date));
        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->description = htmlspecialchars(strip_tags($this->description));
        $this->type = htmlspecialchars(strip_tags($this->type));
        $this->amount = htmlspecialchars(strip_tags($this->amount));
        $this->id = htmlspecialchars(strip_tags($this->id));

        $stmt->bindParam(":transaction_date", $this->transaction_date);
        $stmt->bindParam(":name", $this->name);
        $stmt->bindParam(":description", $this->description);
        $stmt->bindParam(":type", $this->type);
        $stmt->bindParam(":amount", $this->amount);
        $stmt->bindParam(':id', $this->id);

        if ($stmt->execute()) {
            return true;
        }
        error_log("Transaction update error: " . implode(" ", $stmt->errorInfo()));
        return false;
    }

    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);

        $this->id = htmlspecialchars(strip_tags($this->id));
        $stmt->bindParam(':id', $this->id);

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
// koperasi-php/models/User.php
class User {
    private $conn;
    private $table_name = "users";

    public $id;
    public $username;
    public $password;
    public $name;
    public $role;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function findByUsername() {
        $query = "SELECT id, username, password, name, role FROM " . $this->table_name . " WHERE username = :username LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $this->username = htmlspecialchars(strip_tags($this->username));
        $stmt->bindParam(':username', $this->username);
        $stmt->execute();
        return $stmt;
    }

    public function findById() {
        $query = "SELECT id, username, name, role FROM " . $this->table_name . " WHERE id = :id LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->id);
        $stmt->execute();
        return $stmt;
    }
}

```

## koperasi-php/pages/dashboard.php

```php
<?php
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
    if ($t['type'] == 'pemasukan') {
        $totalPemasukanKas += $t['amount'];
    } else {
        $totalPengeluaranKas += $t['amount'];
    }
}
$saldoKas = $totalPemasukanKas - $totalPengeluaranKas;

// --- Calculate Total Simpanan ---
$allSavings = $savingModel->read()->fetchAll(PDO::FETCH_ASSOC);
$totalSimpanan = array_sum(array_column($allSavings, 'amount'));

// --- Calculate Total Piutang (Outstanding Loans) ---
$allLoans = $loanModel->read()->fetchAll(PDO::FETCH_ASSOC);
$totalPiutangAktif = 0;
foreach ($allLoans as $l) {
    if ($l['status'] == 'aktif') {
        $totalPiutangAktif += $l['remaining_amount'];
    }
}

// --- Calculate Total Infaq ---
$allInfaqs = $infaqModel->read()->fetchAll(PDO::FETCH_ASSOC);
$totalPemasukanInfaq = 0;
$totalPengeluaranInfaq = 0;
foreach ($allInfaqs as $i) {
    if ($i['type'] == 'pemasukan') {
        $totalPemasukanInfaq += $i['amount'];
    } else {
        $totalPengeluaranInfaq += $i['amount'];
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
}

usort($recentActivities, function($a, $b) {
    return strtotime($b['date']) - strtotime($a['date']);
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
                            <td><?php echo date("d M Y", strtotime($activity['date'])); ?></td>
                            <td>
                                <?php
                                    if (isset($activity['transaction_date'])) echo 'Kas Umum';
                                    elseif (isset($activity['saving_date'])) echo 'Simpanan';
                                    elseif (isset($activity['infaq_date'])) echo 'Infaq';
                                ?>
                            </td>
                            <td><?php echo htmlspecialchars($activity['description']); ?></td>
                            <td class="text-right <?php echo ($activity['type'] ?? 'pemasukan') == 'pemasukan' ? 'text-success' : 'text-danger'; ?>">
                                <?php echo "Rp " . number_format($activity['amount'], 0, ',', '.'); ?>
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
                            <td><?php echo date("d M Y", strtotime($row['infaq_date'])); ?></td>
                            <td><?php echo htmlspecialchars($row['donor_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['description']); ?></td>
                            <td>
                                <span class="badge <?php echo $row['type'] == 'pemasukan' ? 'badge-success' : 'badge-danger'; ?>">
                                    <?php echo ucfirst($row['type']); ?>
                                </span>
                            </td>
                            <td class="text-right"><?php echo "Rp " . number_format($row['amount'], 0, ',', '.'); ?></td>
                            <?php if (isAdmin()): ?>
                            <td class="actions-cell"> <!-- FIX: Add class here -->
                                <button class="btn btn-sm btn-warning" onclick="openEditInfaqModal(<?php echo htmlspecialchars(json_encode($row)); ?>)">Edit</button>
                                <form action="actions/handle_infaq.php" method="POST" onsubmit="return confirm('Yakin ingin menghapus data ini?');">
                                    <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                    <input type="hidden" name="action" value="delete">
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
                            <td><?php echo date("d M Y", strtotime($row['loan_date'])); ?></td>
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
                                <span class="badge <?php echo $status_class; ?>"><?php echo ucfirst($row['status']); ?></span>
                            </td>
                            <td class="actions-cell"> <!-- FIX: Add class here -->
                                <button class="btn btn-sm btn-info" onclick="openLoanDetailModal(<?php echo $row['id']; ?>)">Detail/Bayar</button>
                                <?php if (isAdmin()): ?>
                                <button class="btn btn-sm btn-warning" onclick="openEditLoanModal(<?php echo htmlspecialchars(json_encode($row)); ?>)">Edit</button>
                                <form action="actions/handle_loan.php" method="POST" onsubmit="return confirm('Yakin menghapus piutang ini? Semua data pembayaran terkait akan ikut terhapus!');">
                                    <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                    <input type="hidden" name="action" value="delete">
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
                <!-- Removed max="10" -->
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
                <!-- Removed max="10" -->
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
                    <!-- Removed max="10" here too, as it relates to tenor -->
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
                            <td style="text-align: center;"><?php echo $row['transaction_count']; ?></td>
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

<!-- Add Saving Modal (Unchanged) -->
<div id="addSavingModal" class="modal">
    <div class="modal-content">
        <span class="close-btn" onclick="closeModal('addSavingModal')">&times;</span>
        <h2>Tambah Simpanan</h2>
        <form action="actions/handle_saving.php" method="POST" class="amount-form">
            <input type="hidden" name="action" value="create">
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

<!-- Edit Saving Modal (Unchanged) -->
<div id="editSavingModal" class="modal">
    <div class="modal-content">
        <span class="close-btn" onclick="closeModal('editSavingModal')">&times;</span>
        <h2>Edit Simpanan</h2>
        <form action="actions/handle_saving.php" method="POST" class="amount-form">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="id" id="edit_saving_id">
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
                            <td><?php echo date("d M Y", strtotime($row['transaction_date'])); ?></td>
                            <td><?php echo htmlspecialchars($row['name']); ?></td>
                            <td><?php echo htmlspecialchars($row['description']); ?></td>
                            <td>
                                <span class="badge <?php echo $row['type'] == 'pemasukan' ? 'badge-success' : 'badge-danger'; ?>">
                                    <?php echo ucfirst($row['type']); ?>
                                </span>
                            </td>
                            <td class="text-right"><?php echo "Rp " . number_format($row['amount'], 0, ',', '.'); ?></td>
                            <?php if (isAdmin()): ?>
                            <td class="actions-cell"> <!-- FIX: Add class here -->
                                <button class="btn btn-sm btn-warning" onclick="openEditTransactionModal(<?php echo htmlspecialchars(json_encode($row)); ?>)">Edit</button>
                                <form action="actions/handle_transaction.php" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus data ini?');">
                                    <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                    <input type="hidden" name="action" value="delete">
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
