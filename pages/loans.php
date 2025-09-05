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
