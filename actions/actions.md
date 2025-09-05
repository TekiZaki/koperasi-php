# Code Dump for actions

---

## actions/handle_infaq.php

```php
// koperasi-php/actions/handle_infaq.php
<?php
session_start();
require_once '../config/Database.php';
require_once '../models/Infaq.php';
require_once '../includes/header.php'; // Include for CSRF helper functions

// Authorization check: Must be admin or superadmin
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'superadmin'])) {
    header("Location: ../index.php?page=infaq&error=unauthorized");
    exit();
}

// --- CSRF Validation --- NEW
if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
    http_response_code(403);
    die('CSRF token validation failed.');
}
// --- END CSRF Validation ---

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

## actions/handle_loan.php

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

## actions/handle_loan_payment.php

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

## actions/handle_saving.php

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

## actions/handle_transaction.php

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
