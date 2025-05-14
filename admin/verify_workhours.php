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

<div class="container">
    <h2>Verify Work Hours</h2>
    
    <div class="filter-form">
        <h3>Filter</h3>
        <form method="post" action="">
            <div class="form-row">
                <div class="form-group">
                    <label for="employee_id">Employee:</label>
                    <select name="employee_id" id="employee_id">
                        <option value="">All Employees</option>
                        <?php foreach($employees as $e): ?>
                        <option value="<?php echo $e['id']; ?>" <?php 
                            echo (isset($_SESSION['wh_filter']['employee_id']) && $_SESSION['wh_filter']['employee_id'] == $e['id']) ? 'selected' : ''; 
                        ?>><?php echo $e['name']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="status">Status:</label>
                    <select name="status" id="status">
                        <option value="">All Statuses</option>
                        <option value="pending" <?php echo (isset($_SESSION['wh_filter']['status']) && $_SESSION['wh_filter']['status'] == 'pending') ? 'selected' : ''; ?>>Pending</option>
                        <option value="approved" <?php echo (isset($_SESSION['wh_filter']['status']) && $_SESSION['wh_filter']['status'] == 'approved') ? 'selected' : ''; ?>>Approved</option>
                        <option value="corrected" <?php echo (isset($_SESSION['wh_filter']['status']) && $_SESSION['wh_filter']['status'] == 'corrected') ? 'selected' : ''; ?>>Corrected</option>
                        <option value="rejected" <?php echo (isset($_SESSION['wh_filter']['status']) && $_SESSION['wh_filter']['status'] == 'rejected') ? 'selected' : ''; ?>>Rejected</option>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="date_from">Date From:</label>
                    <input type="date" name="date_from" id="date_from" value="<?php echo isset($_SESSION['wh_filter']['date_from']) ? $_SESSION['wh_filter']['date_from'] : ''; ?>">
                </div>
                <div class="form-group">
                    <label for="date_to">Date To:</label>
                    <input type="date" name="date_to" id="date_to" value="<?php echo isset($_SESSION['wh_filter']['date_to']) ? $_SESSION['wh_filter']['date_to'] : ''; ?>">
                </div>
            </div>
            <div class="form-buttons">
                <button type="submit" name="filter_workhours">Apply Filter</button>
                <button type="submit" name="clear_filter">Clear Filter</button>
            </div>
        </form>
    </div>
    
    <div class="work-hours-list">
        <h3>Work Hours (<?php echo $total_records; ?> entries)</h3>
        <?php if(count($work_hours) > 0): ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Employee</th>
                        <th>Date</th>
                        <th>Time In</th>
                        <th>Time Out</th>
                        <th>Hours</th>
                        <th>Status</th>
                        <th>Manager</th>
                        <th>Notes</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($work_hours as $wh): ?>
                    <tr>
                        <td><?php echo $wh['id']; ?></td>
                        <td><?php echo $wh['employee_name']; ?></td>
                        <td><?php echo format_date($wh['date']); ?></td>
                        <td><?php echo date('h:i A', strtotime($wh['time_in'])); ?></td>
                        <td><?php echo date('h:i A', strtotime($wh['time_out'])); ?></td>
                        <td><?php echo calculate_hours($wh['time_in'], $wh['time_out']); ?> hrs</td>
                        <td>
                            <?php 
                                switch($wh['status']) {
                                    case 'approved': echo '<span class="text-success">Approved</span>'; break;
                                    case 'rejected': echo '<span class="text-danger">Rejected</span>'; break;
                                    case 'corrected': echo '<span class="text-warning">Corrected</span>'; break;
                                    default: echo '<span class="text-muted">Pending</span>';
                                }
                            ?>
                        </td>
                        <td><?php echo $wh['manager_name']; ?></td>
                        <td><?php echo $wh['notes']; ?></td>
                        <td>
                            <button type="button" onclick="showStatusForm(<?php echo $wh['id']; ?>, '<?php echo $wh['status']; ?>')">Change Status</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <!-- Pagination -->
            <?php if($total_pages > 1): ?>
            <div class="pagination">
                <?php if($page > 1): ?>
                    <a href="?page=<?php echo $page - 1; ?>" class="page-link">&laquo; Previous</a>
                <?php endif; ?>
                
                <?php for($i = 1; $i <= $total_pages; $i++): ?>
                    <?php if($i == $page): ?>
                        <span class="page-link active"><?php echo $i; ?></span>
                    <?php else: ?>
                        <a href="?page=<?php echo $i; ?>" class="page-link"><?php echo $i; ?></a>
                    <?php endif; ?>
                <?php endfor; ?>
                
                <?php if($page < $total_pages): ?>
                    <a href="?page=<?php echo $page + 1; ?>" class="page-link">Next &raquo;</a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <!-- Status Change Form (Hidden by default) -->
            <div id="status-form" style="display: none;" class="modal-form">
                <h3>Change Status</h3>
                <form method="post" action="">
                    <input type="hidden" name="id" id="work_hour_id">
                    <div class="form-group">
                        <label for="status_change">Status:</label>
                        <select name="status" id="status_change" required>
                            <option value="pending">Pending</option>
                            <option value="approved">Approved</option>
                            <option value="corrected">Corrected</option>
                            <option value="rejected">Rejected</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="status_notes">Notes:</label>
                        <textarea name="notes" id="status_notes" rows="3" required></textarea>
                    </div>
                    <button type="submit" name="change_status">Save Changes</button>
                    <button type="button" onclick="hideStatusForm()">Cancel</button>
                </form>
            </div>
        <?php else: ?>
            <p>No work hours records found.</p>
        <?php endif; ?>
    </div>
</div>

<style>
.form-row {
    display: flex;
    gap: 15px;
    margin-bottom: 15px;
}

.form-row .form-group {
    flex: 1;
}

.form-buttons {
    margin-top: 15px;
}

.pagination {
    margin-top: 20px;
    text-align: center;
}

.page-link {
    display: inline-block;
    padding: 5px 10px;
    margin: 0 3px;
    border: 1px solid #ddd;
    border-radius: 3px;
    text-decoration: none;
    color: #333;
}

.page-link.active {
    background-color: #3498db;
    color: white;
    border-color: #3498db;
}

.modal-form {
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background-color: white;
    padding: 20px;
    border-radius: 5px;
    box-shadow: 0 0 10px rgba(0,0,0,0.3);
    z-index: 1000;
    width: 80%;
    max-width: 500px;
}

.text-success { color: #4CAF50; }
.text-danger { color: #e74c3c; }
.text-warning { color: #f39c12; }
.text-muted { color: #7f8c8d; }
</style>

<script>
function showStatusForm(id, currentStatus) {
    document.getElementById('work_hour_id').value = id;
    document.getElementById('status_change').value = currentStatus;
    document.getElementById('status-form').style.display = 'block';
}

function hideStatusForm() {
    document.getElementById('status-form').style.display = 'none';
}
</script>

<?php require_once '../includes/footer.php'; ?> 