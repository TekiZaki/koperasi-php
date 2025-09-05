-- ==========================
-- USERS (20 data)
-- ==========================
INSERT INTO users (username, password, name, role) VALUES
('user1',  '$2y$10$abc1234567890123456789abcdefghijklmno1234567890pqrs', 'User Satu', 'user'),
('user2',  '$2y$10$abc1234567890123456789abcdefghijklmno1234567890pqrs', 'User Dua', 'user'),
('user3',  '$2y$10$abc1234567890123456789abcdefghijklmno1234567890pqrs', 'User Tiga', 'user'),
('user4',  '$2y$10$abc1234567890123456789abcdefghijklmno1234567890pqrs', 'User Empat', 'user'),
('user5',  '$2y$10$abc1234567890123456789abcdefghijklmno1234567890pqrs', 'User Lima', 'user'),
('user6',  '$2y$10$abc1234567890123456789abcdefghijklmno1234567890pqrs', 'User Enam', 'user'),
('user7',  '$2y$10$abc1234567890123456789abcdefghijklmno1234567890pqrs', 'User Tujuh', 'user'),
('user8',  '$2y$10$abc1234567890123456789abcdefghijklmno1234567890pqrs', 'User Delapan', 'user'),
('user9',  '$2y$10$abc1234567890123456789abcdefghijklmno1234567890pqrs', 'User Sembilan', 'user'),
('user10', '$2y$10$abc1234567890123456789abcdefghijklmno1234567890pqrs', 'User Sepuluh', 'user'),
('admin1', '$2y$10$abc1234567890123456789abcdefghijklmno1234567890pqrs', 'Admin Satu', 'admin'),
('admin2', '$2y$10$abc1234567890123456789abcdefghijklmno1234567890pqrs', 'Admin Dua', 'admin'),
('admin3', '$2y$10$abc1234567890123456789abcdefghijklmno1234567890pqrs', 'Admin Tiga', 'admin'),
('admin4', '$2y$10$abc1234567890123456789abcdefghijklmno1234567890pqrs', 'Admin Empat', 'admin'),
('admin5', '$2y$10$abc1234567890123456789abcdefghijklmno1234567890pqrs', 'Admin Lima', 'admin'),
('super1','$2y$10$abc1234567890123456789abcdefghijklmno1234567890pqrs', 'Super Admin 1', 'superadmin'),
('super2','$2y$10$abc1234567890123456789abcdefghijklmno1234567890pqrs', 'Super Admin 2', 'superadmin'),
('super3','$2y$10$abc1234567890123456789abcdefghijklmno1234567890pqrs', 'Super Admin 3', 'superadmin'),
('super4','$2y$10$abc1234567890123456789abcdefghijklmno1234567890pqrs', 'Super Admin 4', 'superadmin'),
('super5','$2y$10$abc1234567890123456789abcdefghijklmno1234567890pqrs', 'Super Admin 5', 'superadmin');

-- ==========================
-- TRANSACTIONS (20 data)
-- ==========================
INSERT INTO transactions (transaction_date, name, description, type, amount, created_by_user_id) VALUES
('2025-01-01', 'Iuran Bulanan', 'Pembayaran iuran Januari', 'pemasukan', 50000, 2),
('2025-01-02', 'Pembelian ATK', 'Beli kertas & pulpen', 'pengeluaran', 100000, 3),
('2025-01-03', 'Donasi Anggota', 'Donasi sukarela', 'pemasukan', 200000, 4),
('2025-01-05', 'Perawatan AC', 'Service AC koperasi', 'pengeluaran', 300000, 5),
('2025-01-06', 'Iuran Bulanan', 'Pembayaran iuran Februari', 'pemasukan', 60000, 6),
('2025-01-07', 'Listrik', 'Tagihan listrik koperasi', 'pengeluaran', 400000, 7),
('2025-01-08', 'Donasi', 'Donasi dari jamaah', 'pemasukan', 250000, 8),
('2025-01-09', 'Pembelian Kursi', 'Kursi untuk koperasi', 'pengeluaran', 500000, 9),
('2025-01-10', 'Iuran Bulanan', 'Pembayaran iuran Maret', 'pemasukan', 70000, 10),
('2025-01-11', 'Air Galon', 'Isi ulang air minum', 'pengeluaran', 50000, 11),
('2025-01-12', 'Iuran Bulanan', 'Pembayaran iuran April', 'pemasukan', 80000, 12),
('2025-01-13', 'Sound System', 'Perbaikan speaker', 'pengeluaran', 600000, 13),
('2025-01-14', 'Iuran Bulanan', 'Pembayaran iuran Mei', 'pemasukan', 90000, 14),
('2025-01-15', 'Kebersihan', 'Bayar jasa kebersihan', 'pengeluaran', 100000, 15),
('2025-01-16', 'Iuran Bulanan', 'Pembayaran iuran Juni', 'pemasukan', 100000, 16),
('2025-01-17', 'Peralatan Masak', 'Beli panci', 'pengeluaran', 150000, 17),
('2025-01-18', 'Iuran Bulanan', 'Pembayaran iuran Juli', 'pemasukan', 110000, 18),
('2025-01-19', 'Papan Tulis', 'Beli papan tulis', 'pengeluaran', 200000, 19),
('2025-01-20', 'Iuran Bulanan', 'Pembayaran iuran Agustus', 'pemasukan', 120000, 20),
('2025-01-21', 'Sumbangan Buku', 'Beli buku keperluan koperasi', 'pengeluaran', 250000, 2);

-- ==========================
-- LOANS (20 data)
-- ==========================
INSERT INTO loans (member_name, loan_amount, loan_date, tenor_months, status, created_by_user_id) VALUES
('Ahmad', 1000000, '2025-01-01', 10, 'aktif', 2),
('Budi', 2000000, '2025-01-02', 12, 'aktif', 3),
('Citra', 1500000, '2025-01-03', 6, 'selesai', 4),
('Dewi', 2500000, '2025-01-04', 8, 'aktif', 5),
('Eko', 1800000, '2025-01-05', 10, 'gagal', 6),
('Fajar', 1200000, '2025-01-06', 10, 'aktif', 7),
('Gina', 3000000, '2025-01-07', 12, 'aktif', 8),
('Hadi', 2200000, '2025-01-08', 6, 'selesai', 9),
('Indah', 2500000, '2025-01-09', 8, 'aktif', 10),
('Joko', 3500000, '2025-01-10', 10, 'aktif', 11),
('Kiki', 1700000, '2025-01-11', 12, 'aktif', 12),
('Lina', 2800000, '2025-01-12', 6, 'selesai', 13),
('Maya', 2300000, '2025-01-13', 10, 'aktif', 14),
('Nina', 3200000, '2025-01-14', 12, 'gagal', 15),
('Oki', 1900000, '2025-01-15', 8, 'aktif', 16),
('Putri', 2600000, '2025-01-16', 10, 'aktif', 17),
('Qori', 2100000, '2025-01-17', 6, 'selesai', 18),
('Rina', 3300000, '2025-01-18', 12, 'aktif', 19),
('Sinta', 2400000, '2025-01-19', 10, 'aktif', 20),
('Tono', 2900000, '2025-01-20', 8, 'aktif', 2);

-- ==========================
-- LOAN PAYMENTS (20 data)
-- ==========================
INSERT INTO loan_payments (loan_id, payment_date, payment_amount, payment_month_no, description, created_by_user_id) VALUES
(1, '2025-02-01', 100000, 1, 'Angsuran 1', 2),
(1, '2025-03-01', 100000, 2, 'Angsuran 2', 2),
(2, '2025-02-02', 200000, 1, 'Angsuran 1', 3),
(2, '2025-03-02', 200000, 2, 'Angsuran 2', 3),
(3, '2025-02-03', 250000, 1, 'Angsuran 1', 4),
(3, '2025-03-03', 250000, 2, 'Angsuran 2', 4),
(4, '2025-02-04', 300000, 1, 'Angsuran 1', 5),
(4, '2025-03-04', 300000, 2, 'Angsuran 2', 5),
(5, '2025-02-05', 180000, 1, 'Angsuran 1', 6),
(6, '2025-02-06', 120000, 1, 'Angsuran 1', 7),
(7, '2025-02-07', 300000, 1, 'Angsuran 1', 8),
(8, '2025-02-08', 220000, 1, 'Angsuran 1', 9),
(9, '2025-02-09', 250000, 1, 'Angsuran 1', 10),
(10, '2025-02-10', 350000, 1, 'Angsuran 1', 11),
(11, '2025-02-11', 170000, 1, 'Angsuran 1', 12),
(12, '2025-02-12', 280000, 1, 'Angsuran 1', 13),
(13, '2025-02-13', 230000, 1, 'Angsuran 1', 14),
(14, '2025-02-14', 320000, 1, 'Angsuran 1', 15),
(15, '2025-02-15', 190000, 1, 'Angsuran 1', 16),
(16, '2025-02-16', 260000, 1, 'Angsuran 1', 17);

-- ==========================
-- SAVINGS (20 data)
-- ==========================
INSERT INTO savings (member_name, saving_type, saving_date, amount, description, created_by_user_id) VALUES
('Ahmad', 'wajib', '2025-01-01', 50000, 'Simpanan wajib Januari', 2),
('Budi', 'wajib', '2025-01-02', 50000, 'Simpanan wajib Februari', 3),
('Citra', 'sukarela', '2025-01-03', 100000, 'Tabungan sukarela', 4),
('Dewi', 'wajib', '2025-01-04', 50000, 'Simpanan wajib Maret', 5),
('Eko', 'sukarela', '2025-01-05', 200000, 'Tabungan sukarela', 6),
('Fajar', 'wajib', '2025-01-06', 50000, 'Simpanan wajib April', 7),
('Gina', 'sukarela', '2025-01-07', 150000, 'Tabungan sukarela', 8),
('Hadi', 'wajib', '2025-01-08', 50000, 'Simpanan wajib Mei', 9),
('Indah', 'sukarela', '2025-01-09', 120000, 'Tabungan sukarela', 10),
('Joko', 'wajib', '2025-01-10', 50000, 'Simpanan wajib Juni', 11),
('Kiki', 'sukarela', '2025-01-11', 100000, 'Tabungan sukarela', 12),
('Lina', 'wajib', '2025-01-12', 50000, 'Simpanan wajib Juli', 13),
('Maya', 'sukarela', '2025-01-13', 130000, 'Tabungan sukarela', 14),
('Nina', 'wajib', '2025-01-14', 50000, 'Simpanan wajib Agustus', 15),
('Oki', 'sukarela', '2025-01-15', 110000, 'Tabungan sukarela', 16),
('Putri', 'wajib', '2025-01-16', 50000, 'Simpanan wajib September', 17),
('Qori', 'sukarela', '2025-01-17', 140000, 'Tabungan sukarela', 18),
('Rina', 'wajib', '2025-01-18', 50000, 'Simpanan wajib Oktober', 19),
('Sinta', 'sukarela', '2025-01-19', 160000, 'Tabungan sukarela', 20),
('Tono', 'wajib', '2025-01-20', 50000, 'Simpanan wajib November', 2);

-- ==========================
-- INFAQS (20 data)
-- ==========================
INSERT INTO infaqs (infaq_date, description, donor_name, amount, type, created_by_user_id) VALUES
('2025-01-01', 'Donasi Jumat', 'Ahmad', 100000, 'pemasukan', 2),
('2025-01-02', 'Beli Tikar', NULL, 50000, 'pengeluaran', 3),
('2025-01-03', 'Donasi Jamaah', 'Budi', 200000, 'pemasukan', 4),
('2025-01-04', 'Beli Sound System', NULL, 300000, 'pengeluaran', 5),
('2025-01-05', 'Donasi', 'Citra', 150000, 'pemasukan', 6),
('2025-01-06', 'Perbaikan AC', NULL, 250000, 'pengeluaran', 7),
('2025-01-07', 'Donasi', 'Dewi', 180000, 'pemasukan', 8),
('2025-01-08', 'Beli Karpet', NULL, 400000, 'pengeluaran', 9),
('2025-01-09', 'Donasi', 'Eko', 120000, 'pemasukan', 10),
('2025-01-10', 'Renovasi Koperasi', NULL, 600000, 'pengeluaran', 11),
('2025-01-11', 'Donasi', 'Fajar', 250000, 'pemasukan', 12),
('2025-01-12', 'Beli Kursi', NULL, 350000, 'pengeluaran', 13),
('2025-01-13', 'Donasi', 'Gina', 300000, 'pemasukan', 14),
('2025-01-14', 'Bayar Tukang', NULL, 500000, 'pengeluaran', 15),
('2025-01-15', 'Donasi', 'Hadi', 220000, 'pemasukan', 16),
('2025-01-16', 'Beli Lampu', NULL, 150000, 'pengeluaran', 17),
('2025-01-17', 'Donasi', 'Indah', 280000, 'pemasukan', 18),
('2025-01-18', 'Bayar Kebersihan', NULL, 100000, 'pengeluaran', 19),
('2025-01-19', 'Donasi', 'Joko', 320000, 'pemasukan', 20),
('2025-01-20', 'Beli Meja', NULL, 450000, 'pengeluaran', 2);
