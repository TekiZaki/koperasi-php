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
