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
