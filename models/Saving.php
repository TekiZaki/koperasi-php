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
