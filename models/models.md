# Code Dump for models

---

## models/Infaq.php

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

## models/Loan.php

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

## models/Saving.php

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

## models/Transaction.php

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

## models/User.php

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
