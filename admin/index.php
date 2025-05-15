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

<div class="dashboard-container">
    <div class="dashboard-header">
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
    
    <div class="dashboard-section">
        <div class="section-header">
            <h3><i class="fas fa-history"></i> Aktivitas Terbaru</h3>
        </div>
        
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
            <div class="empty-state">
                <div class="empty-icon">
                    <i class="fas fa-inbox"></i>
                </div>
                <p>Belum ada aktivitas terbaru</p>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="dashboard-section">
        <div class="section-header">
            <h3><i class="fas fa-tasks"></i> Tugas Cepat</h3>
        </div>
        
        <div class="quick-actions">
            <a href="<?php echo BASE_URL; ?>/admin/manage_employees.php" class="quick-action-card">
                <div class="action-icon"><i class="fas fa-user-plus"></i></div>
                <h4>Tambah Karyawan</h4>
            </a>
            
            <a href="<?php echo BASE_URL; ?>/admin/verify_workhours.php" class="quick-action-card">
                <div class="action-icon"><i class="fas fa-clipboard-check"></i></div>
                <h4>Verifikasi Jam Kerja</h4>
            </a>
            
            <a href="<?php echo BASE_URL; ?>/admin/process_payroll.php" class="quick-action-card">
                <div class="action-icon"><i class="fas fa-file-invoice-dollar"></i></div>
                <h4>Proses Gaji</h4>
            </a>
        </div>
    </div>
</div>

<style>
.dashboard-container {
    padding: 24px;
    max-width: 1280px;
    margin: 0 auto;
}

.dashboard-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
    padding-bottom: 16px;
    border-bottom: 1px solid #f0f0f0;
}

.dashboard-header h2 {
    font-weight: 500;
    color: #333;
    margin: 0;
}

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
    border-left-color: #4f86f7;
}

.pending-card {
    border-left-color: #f7a14f;
}

.payroll-card {
    border-left-color: #4fc95f;
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
    background-color: rgba(79, 134, 247, 0.1);
    color: #4f86f7;
}

.pending-card .stat-icon {
    background-color: rgba(247, 161, 79, 0.1);
    color: #f7a14f;
}

.payroll-card .stat-icon {
    background-color: rgba(79, 201, 95, 0.1);
    color: #4fc95f;
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

.dashboard-section {
    background-color: white;
    border-radius: 12px;
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.04);
    margin-bottom: 32px;
    overflow: hidden;
}

.section-header {
    padding: 16px 24px;
    border-bottom: 1px solid #f0f0f0;
}

.section-header h3 {
    margin: 0;
    font-weight: 500;
    font-size: 18px;
    color: #333;
}

.section-header h3 i {
    margin-right: 8px;
    color: #666;
}

.activity-timeline {
    padding: 24px;
}

.timeline-item {
    display: flex;
    margin-bottom: 24px;
    position: relative;
}

.timeline-item:last-child {
    margin-bottom: 0;
}

.timeline-item:before {
    content: '';
    position: absolute;
    left: 20px;
    top: 40px;
    bottom: -15px;
    width: 2px;
    background-color: #f0f0f0;
    z-index: 1;
}

.timeline-item:last-child:before {
    display: none;
}

.timeline-icon {
    height: 40px;
    width: 40px;
    border-radius: 50%;
    background-color: #eee;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 16px;
    z-index: 2;
}

.timeline-icon.pending {
    background-color: rgba(247, 161, 79, 0.1);
    color: #f7a14f;
}

.timeline-icon.approved {
    background-color: rgba(79, 201, 95, 0.1);
    color: #4fc95f;
}

.timeline-icon.rejected {
    background-color: rgba(239, 83, 80, 0.1);
    color: #ef5350;
}

.timeline-icon.corrected {
    background-color: rgba(79, 134, 247, 0.1);
    color: #4f86f7;
}

.timeline-content {
    flex: 1;
    padding-top: 4px;
}

.timeline-content h4 {
    margin: 0 0 8px 0;
    font-size: 16px;
    font-weight: 500;
}

.timeline-content p {
    margin: 0 0 4px 0;
    font-size: 14px;
    color: #666;
}

.timeline-content time {
    font-size: 13px;
    color: #888;
}

.empty-state {
    padding: 40px;
    text-align: center;
    color: #888;
}

.empty-icon {
    font-size: 48px;
    margin-bottom: 16px;
    color: #ddd;
}

.quick-actions {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 16px;
    padding: 24px;
}

.quick-action-card {
    background-color: #f5f7fa;
    padding: 20px;
    border-radius: 8px;
    text-align: center;
    text-decoration: none;
    color: #333;
    transition: all 0.3s;
}

.quick-action-card:hover {
    background-color: #eef2f8;
    transform: translateY(-3px);
}

.action-icon {
    background-color: white;
    width: 60px;
    height: 60px;
    border-radius: 50%;
    margin: 0 auto 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    color: #1976d2;
}

.quick-action-card h4 {
    margin: 0;
    font-weight: 500;
    font-size: 16px;
}

@media (max-width: 768px) {
    .stats-cards {
        grid-template-columns: 1fr;
    }
    
    .dashboard-header {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .date-display {
        margin-top: 8px;
    }
}
</style>

<?php require_once '../includes/footer.php'; ?> 