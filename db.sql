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
