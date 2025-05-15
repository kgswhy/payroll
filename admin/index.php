<?php
require_once '../auth/check_session.php';
require_once '../includes/header.php';

// Get counts for dashboard
$employee_count = $conn->query("SELECT COUNT(*) FROM users WHERE role = 'employee'")->fetchColumn();
$pending_hours = $conn->query("SELECT COUNT(*) FROM work_hours WHERE status = 'pending'")->fetchColumn();
$processed_payroll = $conn->query("SELECT COUNT(*) FROM payroll WHERE status = 'finalized'")->fetchColumn();

// Get recent activity
$stmt = $conn->query("
    SELECT 'Work Hour' as type, wh.id, wh.date, wh.status, u.name as user_name, wh.created_at
    FROM work_hours wh 
    JOIN users u ON wh.employee_id = u.id 
    ORDER BY wh.created_at DESC 
    LIMIT 5
");
$recent_activities = $stmt->fetchAll();
?>

<!-- Include admin CSS -->
<link rel="stylesheet" href="../assets/css/admin.css">

<div class="admin-container">
    <div class="page-header">
        <h2>Dashboard Admin</h2>
        <div class="date-display">
            <i class="fas fa-calendar-alt"></i> <?php echo date('l, d F Y'); ?>
        </div>
    </div>
    
    <div class="stats-cards">
        <div class="stat-card employee-card">
            <div class="stat-icon">
                <i class="fas fa-users"></i>
            </div>
            <div class="stat-details">
                <h3>Total Karyawan</h3>
                <p class="stat-number"><?php echo $employee_count; ?></p>
                <p class="stat-label">Karyawan aktif</p>
            </div>
        </div>
        
        <div class="stat-card pending-card">
            <div class="stat-icon">
                <i class="fas fa-clock"></i>
            </div>
            <div class="stat-details">
                <h3>Menunggu Approval</h3>
                <p class="stat-number"><?php echo $pending_hours; ?></p>
                <p class="stat-label">Jam kerja belum diverifikasi</p>
            </div>
        </div>
        
        <div class="stat-card payroll-card">
            <div class="stat-icon">
                <i class="fas fa-money-bill-wave"></i>
            </div>
            <div class="stat-details">
                <h3>Gaji Diproses</h3>
                <p class="stat-number"><?php echo $processed_payroll; ?></p>
                <p class="stat-label">Transaksi gaji</p>
            </div>
        </div>
    </div>
    
    <div class="admin-card">
        <div class="card-header">
            <h3><i class="fas fa-history"></i> Aktivitas Terbaru</h3>
        </div>
        
        <div class="card-body">
            <?php if(count($recent_activities) > 0): ?>
                <div class="activity-timeline">
                    <?php foreach($recent_activities as $activity): ?>
                        <div class="timeline-item">
                            <div class="timeline-icon <?php echo strtolower($activity['status']); ?>">
                                <?php if($activity['status'] == 'pending'): ?>
                                    <i class="fas fa-hourglass-half"></i>
                                <?php elseif($activity['status'] == 'approved'): ?>
                                    <i class="fas fa-check"></i>
                                <?php elseif($activity['status'] == 'rejected'): ?>
                                    <i class="fas fa-times"></i>
                                <?php else: ?>
                                    <i class="fas fa-edit"></i>
                                <?php endif; ?>
                            </div>
                            <div class="timeline-content">
                                <h4><?php echo $activity['user_name']; ?></h4>
                                <p><?php echo $activity['type']; ?> - <?php echo ucfirst($activity['status']); ?></p>
                                <time datetime="<?php echo $activity['date']; ?>">
                                    <?php echo format_date($activity['date']); ?>
                                </time>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="no-data">
                    <i class="fas fa-inbox"></i>
                    <p>Belum ada aktivitas terbaru</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="admin-card">
        <div class="card-header">
            <h3><i class="fas fa-tasks"></i> Tugas Cepat</h3>
        </div>
        
        <div class="card-body">
            <div class="quick-actions">
                <a href="manage_employees.php" class="quick-action-card">
                    <div class="action-icon"><i class="fas fa-user-plus"></i></div>
                    <h4>Tambah Karyawan</h4>
                </a>
                
                <a href="verify_data.php" class="quick-action-card">
                    <div class="action-icon"><i class="fas fa-clipboard-check"></i></div>
                    <h4>Verifikasi Data</h4>
                </a>
                
                <a href="process_payroll.php" class="quick-action-card">
                    <div class="action-icon"><i class="fas fa-file-invoice-dollar"></i></div>
                    <h4>Proses Gaji</h4>
                </a>
            </div>
        </div>
    </div>
</div>

<style>
/* Dashboard specific styles that are not in admin.css */
.date-display {
    background-color: #f5f7fa;
    padding: 8px 16px;
    border-radius: 8px;
    font-size: 14px;
    color: #666;
}

.stats-cards {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 24px;
    margin-bottom: 32px;
}

.stat-card {
    display: flex;
    background-color: white;
    padding: 24px;
    border-radius: 12px;
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.04);
    border-left: 4px solid #ddd;
    transition: transform 0.3s, box-shadow 0.3s;
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 16px rgba(0, 0, 0, 0.08);
}

.employee-card {
    border-left-color: var(--primary-color);
}

.pending-card {
    border-left-color: var(--warning-color);
}

.payroll-card {
    border-left-color: var(--success-color);
}

.stat-icon {
    background-color: #f5f7fa;
    color: #888;
    height: 60px;
    width: 60px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 12px;
    margin-right: 20px;
}

.employee-card .stat-icon {
    background-color: rgba(63, 81, 181, 0.1);
    color: var(--primary-color);
}

.pending-card .stat-icon {
    background-color: rgba(255, 193, 7, 0.1);
    color: var(--warning-color);
}

.payroll-card .stat-icon {
    background-color: rgba(40, 167, 69, 0.1);
    color: var(--success-color);
}

.stat-icon i {
    font-size: 24px;
}

.stat-details {
    flex: 1;
}

.stat-details h3 {
    font-size: 16px;
    font-weight: 500;
    margin: 0 0 8px 0;
    color: #666;
}

.stat-number {
    font-size: 32px;
    font-weight: 600;
    margin: 0;
    color: #333;
}

.stat-label {
    font-size: 14px;
    color: #888;
    margin: 4px 0 0 0;
}

/* Timeline styles */
.activity-timeline {
    padding: 20px 0;
}

.timeline-item {
    display: flex;
    margin-bottom: 20px;
    position: relative;
}

.timeline-icon {
    width: 40px;
    height: 40px;
    background-color: #f5f7fa;
    border-radius: 50%;
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 15px;
    color: #888;
}

.timeline-icon.pending {
    background-color: rgba(255, 193, 7, 0.1);
    color: var(--warning-color);
}

.timeline-icon.approved {
    background-color: rgba(40, 167, 69, 0.1);
    color: var(--success-color);
}

.timeline-icon.rejected {
    background-color: rgba(220, 53, 69, 0.1);
    color: var(--danger-color);
}

.timeline-content {
    background-color: #f9f9f9;
    padding: 15px;
    border-radius: 8px;
    flex: 1;
}

.timeline-content h4 {
    margin: 0 0 5px;
    font-size: 16px;
    font-weight: 500;
}

.timeline-content p {
    margin: 0 0 5px;
    color: #666;
}

.timeline-content time {
    font-size: 12px;
    color: #888;
}

/* Quick actions */
.quick-actions {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 20px;
}

.quick-action-card {
    background-color: #f9f9f9;
    border-radius: 8px;
    padding: 20px;
    text-align: center;
    text-decoration: none;
    color: var(--text-color);
    transition: transform 0.3s, box-shadow 0.3s;
}

.quick-action-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 6px 12px rgba(0, 0, 0, 0.08);
    background-color: #fff;
}

.action-icon {
    background-color: rgba(63, 81, 181, 0.1);
    color: var(--primary-color);
    width: 50px;
    height: 50px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 15px;
}

.action-icon i {
    font-size: 20px;
}

.quick-action-card h4 {
    margin: 0;
    font-size: 16px;
    font-weight: 500;
}
</style>

<?php require_once '../includes/footer.php'; ?> 