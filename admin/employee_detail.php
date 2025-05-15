<?php
require_once '../auth/check_session.php';
require_once '../includes/header.php';

// Get parameters
$employee_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$month = isset($_GET['month']) ? intval($_GET['month']) : date('n');
$year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');

// Get employee data
$stmt = $conn->prepare("
    SELECT 
        u.*,
        COALESCE(SUM(TIME_TO_SEC(TIMEDIFF(wh.time_out, wh.time_in)) / 3600), 0) as total_hours,
        COUNT(DISTINCT wh.date) as days_worked
    FROM users u
    LEFT JOIN work_hours wh ON u.id = wh.employee_id 
        AND MONTH(wh.date) = ? 
        AND YEAR(wh.date) = ?
    WHERE u.id = ? AND u.role != 'admin'
    GROUP BY u.id
");
$stmt->execute([$month, $year, $employee_id]);
$employee = $stmt->fetch();

if (!$employee) {
    header('Location: salary_hours_analysis.php');
    exit;
}

// Get daily work hours
$stmt = $conn->prepare("
    SELECT 
        date,
        TIME_FORMAT(time_in, '%H:%i') as time_in,
        TIME_FORMAT(time_out, '%H:%i') as time_out,
        TIME_TO_SEC(TIMEDIFF(time_out, time_in)) / 3600 as hours,
        status,
        notes
    FROM work_hours
    WHERE employee_id = ? 
        AND MONTH(date) = ? 
        AND YEAR(date) = ?
    ORDER BY date DESC
");
$stmt->execute([$employee_id, $month, $year]);
$work_hours = $stmt->fetchAll();

// Calculate statistics
$total_hours = 0;
$total_days = count($work_hours);
$late_days = 0;
$early_leaves = 0;

foreach ($work_hours as $record) {
    $total_hours += $record['hours'];
    if ($record['status'] == 'late') $late_days++;
    if ($record['status'] == 'early_leave') $early_leaves++;
}

$avg_hours = $total_days > 0 ? $total_hours / $total_days : 0;

// Get month name
$month_name = date('F', mktime(0, 0, 0, $month, 1));
?>

<!-- Include admin CSS -->
<link rel="stylesheet" href="../assets/css/admin.css">
<!-- Include admin utilities -->
<script src="../assets/js/admin-utils.js"></script>

<div class="admin-container">
    <div class="page-header d-flex justify-content-between align-items-center">
        <h2><i class="fas fa-user"></i> Detail Karyawan - <?php echo htmlspecialchars($employee['name']); ?></h2>
        <div>
            <a href="salary_hours_analysis.php" class="admin-btn">
                <i class="fas fa-arrow-left"></i> Kembali
            </a>
            <span class="ml-2"><?php echo $month_name . ' ' . $year; ?></span>
        </div>
    </div>
    
    <div class="stats-cards">
        <div class="stat-card" style="border-left-color: var(--info-color);">
            <div class="stat-icon" style="background-color: rgba(23, 162, 184, 0.1); color: var(--info-color);">
                <i class="fas fa-clock"></i>
            </div>
            <div class="stat-details">
                <h3>Total Jam Kerja</h3>
                <p class="stat-number format-number" data-decimals="1"><?php echo $total_hours; ?> <span>jam</span></p>
            </div>
        </div>
        
        <div class="stat-card employee-card">
            <div class="stat-icon">
                <i class="fas fa-calendar-check"></i>
            </div>
            <div class="stat-details">
                <h3>Hari Kerja</h3>
                <p class="stat-number"><?php echo $total_days; ?> <span>hari</span></p>
            </div>
        </div>
        
        <div class="stat-card payroll-card">
            <div class="stat-icon">
                <i class="fas fa-hourglass-half"></i>
            </div>
            <div class="stat-details">
                <h3>Rata-rata Jam/Hari</h3>
                <p class="stat-number format-number" data-decimals="1"><?php echo $avg_hours; ?> <span>jam</span></p>
            </div>
        </div>
        
        <div class="stat-card pending-card">
            <div class="stat-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div class="stat-details">
                <h3>Keterlambatan</h3>
                <p class="stat-number"><?php echo $late_days; ?> <span>hari</span></p>
            </div>
        </div>
    </div>
    
    <div class="admin-card">
        <div class="card-header">
            <h3><i class="fas fa-table"></i> Riwayat Kehadiran</h3>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Jam Masuk</th>
                            <th>Jam Keluar</th>
                            <th>Total Jam</th>
                            <th>Status</th>
                            <th>Keterangan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(count($work_hours) > 0): ?>
                            <?php foreach ($work_hours as $record): ?>
                            <tr>
                                <td><?php echo date('d/m/Y', strtotime($record['date'])); ?></td>
                                <td><?php echo $record['time_in']; ?></td>
                                <td><?php echo $record['time_out']; ?></td>
                                <td><?php echo number_format($record['hours'], 1); ?> jam</td>
                                <td>
                                    <?php if ($record['status'] == 'late'): ?>
                                        <span class="status-badge warning">Terlambat</span>
                                    <?php elseif ($record['status'] == 'early_leave'): ?>
                                        <span class="status-badge rejected">Pulang Awal</span>
                                    <?php else: ?>
                                        <span class="status-badge approved">Normal</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($record['notes'] ?? '-'); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center">Tidak ada data kehadiran untuk bulan ini</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
/* Additional styles for status badges not in admin.css */
.status-badge.warning {
    background-color: var(--warning-color);
    color: #212529;
}
</style>

<?php require_once '../includes/footer.php'; ?> 