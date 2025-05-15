<?php
require_once '../auth/check_session.php';
require_once '../includes/header.php';

// Process verification
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify'])) {
    $month = intval($_POST['month']);
    $year = intval($_POST['year']);
    
    try {
        // Check if data is already verified
        $stmt = $conn->prepare("
            SELECT COUNT(*) FROM payroll 
            WHERE month = ? AND year = ? AND status = 'verified'
        ");
        $stmt->execute([$month, $year]);
        $verified_count = $stmt->fetchColumn();
        
        if ($verified_count > 0) {
            set_alert('warning', 'Data for this period has already been verified');
        } else {
            // Update all payroll records for this period to 'verified'
            $stmt = $conn->prepare("
                UPDATE payroll 
                SET status = 'verified' 
                WHERE month = ? AND year = ?
            ");
            $stmt->execute([$month, $year]);
            
            set_alert('success', 'Payroll data has been verified successfully');
        }
    } catch (PDOException $e) {
        set_alert('danger', 'Error: ' . $e->getMessage());
    }
}

// Get all payroll periods with verification status
$stmt = $conn->query("
    SELECT 
        month,
        year,
        COUNT(*) as total_records,
        SUM(CASE WHEN status = 'verified' THEN 1 ELSE 0 END) as verified_count,
        SUM(CASE WHEN status = 'finalized' THEN 1 ELSE 0 END) as finalized_count
    FROM payroll
    GROUP BY month, year
    ORDER BY year DESC, month DESC
");
$periods = $stmt->fetchAll();
?>

<div class="container">
    <div class="page-header">
        <h2><i class="fas fa-check-circle"></i> Verifikasi Data Payroll</h2>
    </div>
    
    <div class="verify-data card">
        <div class="card-header">
            <h3><i class="fas fa-clipboard-check"></i> Verifikasi Data Baru</h3>
        </div>
        <div class="card-body">
            <form method="post" action="" class="form-grid">
                <div class="form-group">
                    <label for="month">Bulan:</label>
                    <select name="month" id="month" required>
                        <?php for($i = 1; $i <= 12; $i++): ?>
                        <option value="<?php echo $i; ?>" <?php echo ($i == date('n')) ? 'selected' : ''; ?>>
                            <?php echo date('F', mktime(0, 0, 0, $i, 1)); ?>
                        </option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="year">Tahun:</label>
                    <select name="year" id="year" required>
                        <?php for($i = date('Y') - 2; $i <= date('Y') + 1; $i++): ?>
                        <option value="<?php echo $i; ?>" <?php echo ($i == date('Y')) ? 'selected' : ''; ?>>
                            <?php echo $i; ?>
                        </option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="form-group">
                    <button type="submit" name="verify" class="btn-primary">
                        <i class="fas fa-check-circle"></i> Verifikasi Data
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <div class="verification-list card">
        <div class="card-header">
            <h3><i class="fas fa-history"></i> Riwayat Verifikasi</h3>
        </div>
        <div class="card-body">
            <?php if(count($periods) > 0): ?>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Periode</th>
                                <th>Total Karyawan</th>
                                <th>Status</th>
                                <th>Progress</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($periods as $period): 
                                $progress = ($period['total_records'] > 0) ? 
                                    ($period['verified_count'] / $period['total_records']) * 100 : 0;
                            ?>
                            <tr>
                                <td><?php echo get_month_name($period['month']) . ' ' . $period['year']; ?></td>
                                <td><?php echo $period['total_records']; ?></td>
                                <td>
                                    <?php if($period['finalized_count'] > 0): ?>
                                        <span class="status-badge finalized">Ditutup</span>
                                    <?php elseif($period['verified_count'] > 0): ?>
                                        <span class="status-badge verified">Terverifikasi</span>
                                    <?php else: ?>
                                        <span class="status-badge pending">Menunggu</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="progress-bar">
                                        <div class="progress-fill" style="width: <?php echo $progress; ?>%"></div>
                                        <span class="progress-label"><?php echo number_format($progress, 1); ?>%</span>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="no-data">
                    <i class="fas fa-clipboard-check"></i>
                    <p>Belum ada data payroll untuk diverifikasi.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.form-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    align-items: end;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.form-group label {
    color: #666;
    font-size: 14px;
}

.form-group select {
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 14px;
}

.btn-primary {
    background: #1976d2;
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 6px;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 14px;
    transition: background 0.2s;
}

.btn-primary:hover {
    background: #1565c0;
}

.progress-bar {
    width: 100%;
    height: 20px;
    background: #f0f0f0;
    border-radius: 10px;
    overflow: hidden;
    position: relative;
}

.progress-fill {
    height: 100%;
    background: #4caf50;
    transition: width 0.3s ease;
}

.progress-label {
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    font-size: 12px;
    color: #fff;
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
}

.status-badge {
    padding: 5px 10px;
    border-radius: 15px;
    font-size: 12px;
    font-weight: 500;
}

.status-badge.pending {
    background: #fff3e0;
    color: #f57c00;
}

.status-badge.verified {
    background: #e8f5e9;
    color: #2e7d32;
}

.status-badge.finalized {
    background: #e3f2fd;
    color: #1976d2;
}

@media (max-width: 768px) {
    .form-grid {
        grid-template-columns: 1fr;
    }
    
    .btn-primary {
        width: 100%;
        justify-content: center;
    }
}
</style>

<?php require_once '../includes/footer.php'; ?> 