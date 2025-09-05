# Rekomendasi Peningkatan Keamanan Aplikasi Koperasi PHP

## 🚨 Kelemahan Keamanan Kritis

### 1. **SQL Injection Prevention**

**Status**: Sudah baik dengan prepared statements, tetapi perlu validasi tambahan

**Perbaikan**:

```php
// Di semua model, tambahkan validasi tipe data
public function create() {
    // Validasi amount adalah numeric
    if (!is_numeric($this->amount) || $this->amount < 0) {
        throw new InvalidArgumentException("Invalid amount value");
    }

    // Validasi date format
    if (!DateTime::createFromFormat('Y-m-d', $this->transaction_date)) {
        throw new InvalidArgumentException("Invalid date format");
    }

    $query = "INSERT INTO " . $this->table_name . " SET ...";
    // ... rest of code
}
```

### 2. **Cross-Site Scripting (XSS) Protection**

**Kelemahan saat ini**: Menggunakan `htmlspecialchars()` tapi tidak konsisten

**Perbaikan**:

```php
// Buat fungsi helper untuk sanitasi output
function sanitizeOutput($data) {
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

// Gunakan di semua output:
<td><?php echo sanitizeOutput($row['member_name']); ?></td>

// Untuk JSON output di AJAX:
echo json_encode($data, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
```

### 3. **CSRF Protection**

**Kelemahan**: Tidak ada proteksi CSRF

**Implementasi**:

```php
// Di session_start(), tambahkan:
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Fungsi helper:
function generateCSRFToken() {
    return $_SESSION['csrf_token'];
}

function validateCSRFToken($token) {
    return hash_equals($_SESSION['csrf_token'], $token);
}

// Di setiap form, tambahkan:
<input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">

// Di setiap action handler:
if (!validateCSRFToken($_POST['csrf_token'])) {
    http_response_code(403);
    die('CSRF token validation failed');
}
```

### 4. **Session Security Enhancement**

**Perbaikan di setiap file yang menggunakan session**:

```php
// Ganti session_start() dengan:
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1); // Jika menggunakan HTTPS
ini_set('session.use_strict_mode', 1);
session_start();

// Regenerate session ID setelah login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && /* login success */) {
    session_regenerate_id(true);
    $_SESSION['user_id'] = $row['id'];
    // ...
}
```

### 5. **Input Validation & Sanitization**

**Buat class validator**:

```php
// config/Validator.php
class Validator {
    public static function validateAmount($amount) {
        if (!is_numeric($amount) || $amount < 0 || $amount > 999999999) {
            throw new InvalidArgumentException("Invalid amount");
        }
        return floatval($amount);
    }

    public static function validateDate($date) {
        if (!DateTime::createFromFormat('Y-m-d', $date)) {
            throw new InvalidArgumentException("Invalid date format");
        }
        return $date;
    }

    public static function validateString($string, $maxLength = 255) {
        $string = trim($string);
        if (strlen($string) > $maxLength) {
            throw new InvalidArgumentException("String too long");
        }
        return $string;
    }

    public static function validateEnum($value, $allowedValues) {
        if (!in_array($value, $allowedValues)) {
            throw new InvalidArgumentException("Invalid enum value");
        }
        return $value;
    }
}
```

### 6. **Database Security**

**Perbaikan di Database.php**:

```php
class Database {
    private $host = "localhost";
    private $db_name = "koperasi_php";
    private $username = "koperasi_user"; // Jangan gunakan root
    private $password = "strong_random_password"; // Password yang kuat

    public function getConnection() {
        $this->conn = null;
        try {
            $dsn = "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false, // Gunakan real prepared statements
                PDO::MYSQL_ATTR_FOUND_ROWS => true
            ];
            $this->conn = new PDO($dsn, $this->username, $this->password, $options);
        } catch(PDOException $exception) {
            error_log("Database connection error: " . $exception->getMessage());
            throw new Exception("Database connection failed");
        }
        return $this->conn;
    }
}
```

### 7. **File Access Security**

**Perbaikan di AJAX files**:

```php
// ajax_get_loan_details.php
<?php
// Rate limiting
session_start();
$user_id = $_SESSION['user_id'];
$current_time = time();

if (!isset($_SESSION['last_ajax_request'])) {
    $_SESSION['last_ajax_request'] = $current_time;
} else {
    if ($current_time - $_SESSION['last_ajax_request'] < 1) { // 1 detik cooldown
        http_response_code(429);
        echo json_encode(['error' => 'Too many requests']);
        exit();
    }
    $_SESSION['last_ajax_request'] = $current_time;
}

// Validasi loan_id
$loan_id = filter_input(INPUT_GET, 'loan_id', FILTER_VALIDATE_INT);
if ($loan_id === false || $loan_id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid loan ID']);
    exit();
}
```

### 8. **Error Handling & Logging**

**Implementasi error handling yang aman**:

```php
// config/ErrorHandler.php
class ErrorHandler {
    public static function handleError($message, $context = []) {
        // Log error secara aman
        error_log(date('Y-m-d H:i:s') . " - " . $message . " - Context: " . json_encode($context));

        // Jangan tampilkan detail error ke user di production
        if (defined('DEBUG_MODE') && DEBUG_MODE) {
            return $message;
        } else {
            return "An error occurred. Please try again.";
        }
    }
}

// Di setiap action handler:
try {
    // ... kode operasi
} catch (Exception $e) {
    $error = ErrorHandler::handleError($e->getMessage(), [
        'user_id' => $_SESSION['user_id'],
        'action' => $action,
        'file' => __FILE__
    ]);
    header("Location: ../index.php?page=loans&status=error&message=" . urlencode($error));
    exit();
}
```

### 9. **Content Security Policy (CSP)**

**Tambahkan di header.php**:

```php
<?php
// CSP headers
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; font-src 'self'");
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");
header("Strict-Transport-Security: max-age=31536000; includeSubDomains");
?>
```

### 10. **Authorization Improvements**

**Perbaikan kontrol akses**:

```php
// config/Auth.php
class Auth {
    public static function requireRole($requiredRoles) {
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            header("Location: login.php");
            exit();
        }

        if (!in_array($_SESSION['user_role'], $requiredRoles)) {
            http_response_code(403);
            header("Location: index.php?error=access_denied");
            exit();
        }

        // Check session timeout (30 menit)
        if (isset($_SESSION['last_activity']) &&
            (time() - $_SESSION['last_activity'] > 1800)) {
            session_destroy();
            http_response_code(401);
            header("Location: login.php?timeout=1");
            exit();
        }

        $_SESSION['last_activity'] = time();
    }
}

// Gunakan di setiap action handler:
Auth::requireRole(['admin', 'superadmin']);
```

## 🔐 Implementasi Password Security

### Password Hashing yang Lebih Kuat

```php
// Saat membuat user baru:
$hashedPassword = password_hash($password, PASSWORD_ARGON2ID, [
    'memory_cost' => 65536,
    'time_cost' => 4,
    'threads' => 3
]);

// Saat login:
if (password_verify($inputPassword, $hashedPassword)) {
    // Check if rehashing needed
    if (password_needs_rehash($hashedPassword, PASSWORD_ARGON2ID)) {
        $newHash = password_hash($inputPassword, PASSWORD_ARGON2ID, [
            'memory_cost' => 65536,
            'time_cost' => 4,
            'threads' => 3
        ]);
        // Update database with new hash
    }
}
```

## 🛡️ Environment & Configuration Security

### 1. Environment Variables

```php
// config/Environment.php
class Environment {
    public static function load() {
        $env = parse_ini_file('.env');
        foreach ($env as $key => $value) {
            putenv("$key=$value");
        }
    }

    public static function get($key, $default = null) {
        return getenv($key) ?: $default;
    }
}

// .env file (jangan commit ke git):
DB_HOST=localhost
DB_NAME=koperasi_php
DB_USER=koperasi_user
DB_PASS=strong_random_password
DEBUG_MODE=false
```

### 2. File Permissions

```bash
# Set proper permissions
chmod 644 *.php
chmod 600 config/Database.php
chmod 600 .env
chmod 755 assets/
chmod 644 assets/css/*
chmod 644 assets/js/*
```

## 📋 Security Checklist

- [ ] Implement CSRF protection
- [ ] Add input validation everywhere
- [ ] Secure session configuration
- [ ] Database user with minimal privileges
- [ ] Error logging without information disclosure
- [ ] Rate limiting for AJAX requests
- [ ] Content Security Policy headers
- [ ] Regular security updates
- [ ] Password complexity requirements
- [ ] Session timeout implementation
- [ ] Secure file permissions
- [ ] Environment variables for sensitive data

## 🚀 Prioritas Implementasi

1. **Urgent** (Implementasi segera):

   - CSRF Protection
   - Input validation
   - Session security

2. **High Priority**:

   - Database security
   - Error handling
   - Authorization improvements

3. **Medium Priority**:

   - CSP headers
   - Rate limiting
   - Environment variables

4. **Low Priority**:
   - Advanced password hashing
   - Audit logging

## 💡 Rekomendasi Tambahan

1. **Backup & Recovery**: Implementasi backup database otomatis
2. **Audit Trail**: Log semua perubahan data penting
3. **Two-Factor Authentication**: Untuk admin accounts
4. **Regular Security Scans**: Gunakan tools seperti OWASP ZAP
5. **Code Review**: Review kode secara berkala untuk keamanan
