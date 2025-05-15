<?php
require_once '../auth/check_session.php';
require_once '../includes/header.php';

// Get filter parameters
$month = isset($_GET['month']) ? intval($_GET['month']) : date('n');
$year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');

// Get salary and working hours data
$stmt = $conn->prepare("
    SELECT 
        u.id,
        u.name,
        u.base_salary,
        u.role,
        COALESCE(SUM(TIME_TO_SEC(TIMEDIFF(wh.time_out, wh.time_in)) / 3600), 0) as total_hours,
        COALESCE(AVG(TIME_TO_SEC(TIMEDIFF(wh.time_out, wh.time_in)) / 3600), 0) as avg_hours,
        COALESCE(MAX(TIME_TO_SEC(TIMEDIFF(wh.time_out, wh.time_in)) / 3600), 0) as max_hours,
        COALESCE(MIN(TIME_TO_SEC(TIMEDIFF(wh.time_out, wh.time_in)) / 3600), 0) as min_hours,
        COUNT(DISTINCT wh.date) as days_worked
    FROM users u
    LEFT JOIN work_hours wh ON u.id = wh.employee_id 
        AND MONTH(wh.date) = ? 
        AND YEAR(wh.date) = ?
    WHERE u.role != 'admin'
    GROUP BY u.id, u.name, u.base_salary, u.role
    ORDER BY u.name
");
$stmt->execute([$month, $year]);
$employees = $stmt->fetchAll();

// Calculate statistics
$total_salary = 0;
$total_hours = 0;
$total_employees = count($employees);

foreach ($employees as $employee) {
    $total_salary += $employee['base_salary'];
    $total_hours += $employee['total_hours'];
}

$avg_salary = $total_employees > 0 ? $total_salary / $total_employees : 0;
$avg_hours = $total_employees > 0 ? $total_hours / $total_employees : 0;

// Get month name
$month_name = date('F', mktime(0, 0, 0, $month, 1));
?>

<!-- Include admin CSS -->
<link rel="stylesheet" href="../assets/css/admin.css">
<!-- Include admin utilities -->
<script src="../assets/js/admin-utils.js"></script>

<div class="admin-container">
    <div class="page-header">
        <h2><i class="fas fa-chart-line"></i> Analisis Gaji & Jam Kerja</h2>
    </div>
    
    <!-- Filter Form -->
    <div class="admin-card">
        <div class="card-header">
            <h3><i class="fas fa-filter"></i> Filter</h3>
        </div>
        <div class="card-body">
            <form method="get" class="admin-form">
                <div class="d-flex" style="gap: 20px; flex-wrap: wrap;">
                    <div class="form-group">
                        <label for="month" class="form-label">Bulan:</label>
                        <select name="month" id="month" class="form-input">
                            <?php for($m = 1; $m <= 12; $m++): ?>
                                <option value="<?php echo $m; ?>" <?php echo $m == $month ? 'selected' : ''; ?>>
                                    <?php echo date('F', mktime(0, 0, 0, $m, 1)); ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="year" class="form-label">Tahun:</label>
                        <select name="year" id="year" class="form-input">
                            <?php for($y = date('Y'); $y >= date('Y')-2; $y--): ?>
                                <option value="<?php echo $y; ?>" <?php echo $y == $year ? 'selected' : ''; ?>>
                                    <?php echo $y; ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="form-group" style="align-self: flex-end;">
                        <button type="submit" class="admin-btn admin-btn-primary">Filter</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <div class="stats-cards">
        <div class="stat-card employee-card">
            <div class="stat-icon">
                <i class="fas fa-users"></i>
            </div>
            <div class="stat-details">
                <h3>Total Karyawan</h3>
                <p class="stat-number"><?php echo $total_employees; ?></p>
            </div>
        </div>
        
        <div class="stat-card payroll-card">
            <div class="stat-icon">
                <i class="fas fa-money-bill-wave"></i>
            </div>
            <div class="stat-details">
                <h3>Rata-rata Gaji</h3>
                <p class="stat-number format-currency"><?php echo $avg_salary; ?></p>
            </div>
        </div>
        
        <div class="stat-card pending-card">
            <div class="stat-icon">
                <i class="fas fa-clock"></i>
            </div>
            <div class="stat-details">
                <h3>Rata-rata Jam Kerja</h3>
                <p class="stat-number format-number" data-decimals="1"><?php echo $avg_hours; ?> <span>jam</span></p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon" style="background-color: rgba(23, 162, 184, 0.1); color: var(--info-color);">
                <i class="fas fa-chart-pie"></i>
            </div>
            <div class="stat-details">
                <h3>Total Jam Kerja</h3>
                <p class="stat-number format-number" data-decimals="1"><?php echo $total_hours; ?> <span>jam</span></p>
            </div>
        </div>
    </div>
    
    <div class="admin-card">
        <div class="card-header">
            <h3><i class="fas fa-table"></i> Detail Karyawan - <?php echo $month_name . ' ' . $year; ?></h3>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Nama</th>
                            <th>Jabatan</th>
                            <th>Gaji Pokok</th>
                            <th>Total Jam</th>
                            <th>Rata-rata Jam</th>
                            <th>Min Jam</th>
                            <th>Max Jam</th>
                            <th>Hari Kerja</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($employees as $employee): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($employee['name']); ?></td>
                            <td><?php echo ucfirst($employee['role']); ?></td>
                            <td>Rp <?php echo number_format($employee['base_salary'], 0, ',', '.'); ?></td>
                            <td><?php echo number_format($employee['total_hours'], 1); ?> jam</td>
                            <td><?php echo number_format($employee['avg_hours'], 1); ?> jam</td>
                            <td><?php echo number_format($employee['min_hours'], 1); ?> jam</td>
                            <td><?php echo number_format($employee['max_hours'], 1); ?> jam</td>
                            <td><?php echo $employee['days_worked']; ?> hari</td>
                            <td>
                                <a href="employee_detail.php?id=<?php echo $employee['id']; ?>&month=<?php echo $month; ?>&year=<?php echo $year; ?>" 
                                   class="admin-btn admin-btn-primary admin-btn-sm">
                                    <i class="fas fa-eye"></i> Detail
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?> 