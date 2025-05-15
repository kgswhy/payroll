<?php
require_once '../auth/check_session.php';
require_once '../includes/header.php';

// Process form submission
if($_SERVER['REQUEST_METHOD'] === 'POST') {
    if(isset($_POST['filter_workhours'])) {
        $employee_id = !empty($_POST['employee_id']) ? intval($_POST['employee_id']) : null;
        $status = !empty($_POST['status']) ? sanitize_input($_POST['status']) : null;
        $date_from = !empty($_POST['date_from']) ? sanitize_input($_POST['date_from']) : null;
        $date_to = !empty($_POST['date_to']) ? sanitize_input($_POST['date_to']) : null;
        
        // Store filter in session for pagination
        $_SESSION['wh_filter'] = [
            'employee_id' => $employee_id,
            'status' => $status,
            'date_from' => $date_from,
            'date_to' => $date_to
        ];
    } elseif(isset($_POST['clear_filter'])) {
        unset($_SESSION['wh_filter']);
    } elseif(isset($_POST['change_status'])) {
        $id = intval($_POST['id']);
        $status = sanitize_input($_POST['status']);
        $notes = sanitize_input($_POST['notes']);
        
        try {
            $stmt = $conn->prepare("
                UPDATE work_hours 
                SET status = ?, notes = CONCAT(IFNULL(notes, ''), '\n[Admin Update: ', ?, ']') 
                WHERE id = ?
            ");
            $result = $stmt->execute([$status, $notes, $id]);
            
            if($result) {
                set_alert('success', 'Work hour status updated');
            } else {
                set_alert('danger', 'Failed to update status');
            }
        } catch (PDOException $e) {
            set_alert('danger', 'Error: ' . $e->getMessage());
        }
    }
}

// Pagination parameters
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Build query based on filters
$where_clauses = [];
$params = [];

// Apply filters if set
if(isset($_SESSION['wh_filter'])) {
    $filter = $_SESSION['wh_filter'];
    
    if(!empty($filter['employee_id'])) {
        $where_clauses[] = "wh.employee_id = ?";
        $params[] = $filter['employee_id'];
    }
    
    if(!empty($filter['status'])) {
        $where_clauses[] = "wh.status = ?";
        $params[] = $filter['status'];
    }
    
    if(!empty($filter['date_from'])) {
        $where_clauses[] = "wh.date >= ?";
        $params[] = $filter['date_from'];
    }
    
    if(!empty($filter['date_to'])) {
        $where_clauses[] = "wh.date <= ?";
        $params[] = $filter['date_to'];
    }
}

// Build the WHERE clause
$where_sql = "";
if(count($where_clauses) > 0) {
    $where_sql = "WHERE " . implode(" AND ", $where_clauses);
}

// Get total count for pagination
$count_sql = "
    SELECT COUNT(*) 
    FROM work_hours wh 
    $where_sql
";
$stmt = $conn->prepare($count_sql);
$stmt->execute($params);
$total_records = $stmt->fetchColumn();
$total_pages = ceil($total_records / $limit);

// Get work hours data
$sql = "
    SELECT wh.*, u.name as employee_name, m.name as manager_name 
    FROM work_hours wh 
    JOIN users u ON wh.employee_id = u.id 
    LEFT JOIN users m ON u.manager_id = m.id 
    $where_sql 
    ORDER BY wh.date DESC, wh.created_at DESC 
    LIMIT $limit OFFSET $offset
";
$stmt = $conn->prepare($sql);
$stmt->execute($params);
$work_hours = $stmt->fetchAll();

// Get all employees for filter dropdown
$stmt = $conn->query("SELECT id, name FROM users WHERE role = 'employee' ORDER BY name");
$employees = $stmt->fetchAll();
?>

<!-- Include admin CSS -->
<link rel="stylesheet" href="../assets/css/admin.css">

<div class="admin-container">
    <div class="page-header">
        <h2><i class="fas fa-clipboard-check"></i> Verifikasi Jam Kerja</h2>
    </div>
    
    <?php echo display_alert(); ?>
    
    <div class="admin-card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3><i class="fas fa-filter"></i> Filter</h3>
            <button type="button" class="admin-btn" id="filter-toggle" onclick="toggleFilterPanel()">
                <i class="fas fa-chevron-down" id="filter-toggle-icon"></i>
            </button>
        </div>
        <div class="card-body" id="filter-panel">
            <form method="post" action="" class="admin-form">
                <div class="d-flex" style="gap: 20px; flex-wrap: wrap;">
                    <div class="form-group">
                        <label for="employee_id" class="form-label">Karyawan:</label>
                        <select name="employee_id" id="employee_id" class="form-input">
                            <option value="">Semua Karyawan</option>
                            <?php foreach($employees as $e): ?>
                            <option value="<?php echo $e['id']; ?>" <?php 
                                echo (isset($_SESSION['wh_filter']['employee_id']) && $_SESSION['wh_filter']['employee_id'] == $e['id']) ? 'selected' : ''; 
                            ?>><?php echo $e['name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="status" class="form-label">Status:</label>
                        <select name="status" id="status" class="form-input">
                            <option value="">Semua Status</option>
                            <option value="pending" <?php echo (isset($_SESSION['wh_filter']['status']) && $_SESSION['wh_filter']['status'] == 'pending') ? 'selected' : ''; ?>>Pending</option>
                            <option value="approved" <?php echo (isset($_SESSION['wh_filter']['status']) && $_SESSION['wh_filter']['status'] == 'approved') ? 'selected' : ''; ?>>Approved</option>
                            <option value="corrected" <?php echo (isset($_SESSION['wh_filter']['status']) && $_SESSION['wh_filter']['status'] == 'corrected') ? 'selected' : ''; ?>>Corrected</option>
                            <option value="rejected" <?php echo (isset($_SESSION['wh_filter']['status']) && $_SESSION['wh_filter']['status'] == 'rejected') ? 'selected' : ''; ?>>Rejected</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="date_from" class="form-label">Tanggal Mulai:</label>
                        <input type="date" name="date_from" id="date_from" class="form-input" value="<?php echo isset($_SESSION['wh_filter']['date_from']) ? $_SESSION['wh_filter']['date_from'] : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="date_to" class="form-label">Tanggal Selesai:</label>
                        <input type="date" name="date_to" id="date_to" class="form-input" value="<?php echo isset($_SESSION['wh_filter']['date_to']) ? $_SESSION['wh_filter']['date_to'] : ''; ?>">
                    </div>
                </div>
                <div class="form-actions">
                    <button type="submit" name="filter_workhours" class="admin-btn admin-btn-primary">
                        <i class="fas fa-search"></i> Terapkan Filter
                    </button>
                    <button type="submit" name="clear_filter" class="admin-btn">
                        <i class="fas fa-undo"></i> Reset Filter
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <div class="admin-card mt-4">
        <div class="card-header">
            <h3><i class="fas fa-clock"></i> Jam Kerja <span class="count-badge"><?php echo $total_records; ?> entri</span></h3>
        </div>
        <div class="card-body">
            <?php if(count($work_hours) > 0): ?>
                <div class="table-responsive">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Karyawan</th>
                                <th>Tanggal</th>
                                <th>Jam Masuk</th>
                                <th>Jam Keluar</th>
                                <th>Jam</th>
                                <th>Status</th>
                                <th>Manager</th>
                                <th>Catatan</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($work_hours as $wh): ?>
                            <tr>
                                <td><?php echo $wh['id']; ?></td>
                                <td><?php echo htmlspecialchars($wh['employee_name']); ?></td>
                                <td><?php echo format_date($wh['date']); ?></td>
                                <td><?php echo date('H:i', strtotime($wh['time_in'])); ?></td>
                                <td><?php echo date('H:i', strtotime($wh['time_out'])); ?></td>
                                <td><?php echo calculate_hours($wh['time_in'], $wh['time_out']); ?> jam</td>
                                <td>
                                    <span class="status-badge <?php echo $wh['status']; ?>">
                                        <?php echo ucfirst($wh['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($wh['manager_name'] ?? '-'); ?></td>
                                <td><?php echo nl2br(htmlspecialchars($wh['notes'] ?? '-')); ?></td>
                                <td>
                                    <button class="admin-btn admin-btn-sm admin-btn-primary" onclick="openEditModal('<?php echo $wh['id']; ?>')">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <?php if($total_pages > 1): ?>
                <div class="pagination mt-3">
                    <?php if($page > 1): ?>
                        <a href="?page=<?php echo $page-1; ?>" class="pagination-item">
                            <i class="fas fa-chevron-left"></i> Prev
                        </a>
                    <?php endif; ?>
                    
                    <?php
                    $start_page = max(1, $page - 2);
                    $end_page = min($total_pages, $page + 2);
                    
                    if($start_page > 1): ?>
                        <a href="?page=1" class="pagination-item">1</a>
                        <?php if($start_page > 2): ?>
                            <span class="pagination-ellipsis">...</span>
                        <?php endif; ?>
                    <?php endif; ?>
                    
                    <?php for($i = $start_page; $i <= $end_page; $i++): ?>
                        <a href="?page=<?php echo $i; ?>" class="pagination-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                    
                    <?php if($end_page < $total_pages): ?>
                        <?php if($end_page < $total_pages - 1): ?>
                            <span class="pagination-ellipsis">...</span>
                        <?php endif; ?>
                        <a href="?page=<?php echo $total_pages; ?>" class="pagination-item"><?php echo $total_pages; ?></a>
                    <?php endif; ?>
                    
                    <?php if($page < $total_pages): ?>
                        <a href="?page=<?php echo $page+1; ?>" class="pagination-item">
                            Next <i class="fas fa-chevron-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
            <?php else: ?>
                <div class="no-data">
                    <i class="fas fa-inbox"></i>
                    <p>No work hours found with the current filters.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal for editing work hours -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <span class="modal-close" onclick="closeModal()">&times;</span>
        <h2>Edit Work Hour Status</h2>
        <form id="editForm" method="post" action="" class="admin-form">
            <input type="hidden" name="id" id="edit_id">
            
            <div class="form-group">
                <label for="edit_status" class="form-label">Status:</label>
                <select name="status" id="edit_status" class="form-input" required>
                    <option value="pending">Pending</option>
                    <option value="approved">Approved</option>
                    <option value="corrected">Corrected</option>
                    <option value="rejected">Rejected</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="edit_notes" class="form-label">Notes:</label>
                <textarea name="notes" id="edit_notes" class="form-input" rows="3"></textarea>
                <div class="form-note">Add notes about why you're changing the status.</div>
            </div>
            
            <div class="form-actions">
                <button type="submit" name="change_status" class="admin-btn admin-btn-primary">Update</button>
                <button type="button" class="admin-btn" onclick="closeModal()">Cancel</button>
            </div>
        </form>
    </div>
</div>

<style>
/* Additional styles for elements not in admin.css */
.count-badge {
    font-size: 0.85rem;
    background-color: var(--bg-light);
    padding: 3px 8px;
    border-radius: 12px;
    margin-left: 10px;
    color: var(--text-color);
}

/* Modal styles */
.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    overflow: auto;
}

.modal-content {
    background-color: white;
    margin: 10% auto;
    padding: 25px;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    width: 90%;
    max-width: 500px;
    position: relative;
}

.modal-close {
    position: absolute;
    top: 15px;
    right: 20px;
    font-size: 24px;
    cursor: pointer;
    color: #888;
}

.modal-close:hover {
    color: #000;
}

/* Pagination styles */
.pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    flex-wrap: wrap;
    gap: 5px;
}

.pagination-item {
    display: inline-block;
    padding: 6px 12px;
    border: 1px solid var(--border-color);
    border-radius: 4px;
    text-decoration: none;
    color: var(--text-color);
    background-color: white;
    min-width: 34px;
    text-align: center;
}

.pagination-item:hover {
    background-color: var(--bg-light);
}

.pagination-item.active {
    background-color: var(--primary-color);
    color: white;
    border-color: var(--primary-color);
}

.pagination-ellipsis {
    padding: 6px 12px;
    color: var(--text-color);
}
</style>

<script>
// Toggle filter panel
function toggleFilterPanel() {
    const panel = document.getElementById('filter-panel');
    const icon = document.getElementById('filter-toggle-icon');
    
    if (panel.style.display === 'none') {
        panel.style.display = 'block';
        icon.classList.remove('fa-chevron-down');
        icon.classList.add('fa-chevron-up');
    } else {
        panel.style.display = 'none';
        icon.classList.remove('fa-chevron-up');
        icon.classList.add('fa-chevron-down');
    }
}

// Modal functions
function openEditModal(id) {
    document.getElementById('edit_id').value = id;
    document.getElementById('editModal').style.display = 'block';
}

function closeModal() {
    document.getElementById('editModal').style.display = 'none';
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('editModal');
    if (event.target == modal) {
        closeModal();
    }
}
</script>

<?php require_once '../includes/footer.php'; ?> 