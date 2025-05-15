<?php
require_once '../auth/check_session.php';
require_once '../includes/header.php';

// Process payroll generation
if($_SERVER['REQUEST_METHOD'] === 'POST') {
    if(isset($_POST['generate_payroll'])) {
        $month = intval($_POST['month']);
        $year = intval($_POST['year']);
        
        if($month < 1 || $month > 12 || $year < 2000) {
            set_alert('danger', 'Invalid month or year');
        } else {
            try {
                // Get all employees with their allowances
                $stmt = $conn->prepare("
                    SELECT u.*, 
                           a.transport_allowance, a.meal_allowance, a.health_allowance,
                           a.position_allowance, a.attendance_allowance,
                           a.family_allowance, a.communication_allowance, a.education_allowance
                    FROM users u 
                    LEFT JOIN allowances a ON u.id = a.user_id
                    WHERE u.role = 'employee'
                ");
                $stmt->execute();
                $employees = $stmt->fetchAll();
                $processed_count = 0;
                
                // Start transaction
                $conn->beginTransaction();
                
                foreach($employees as $employee) {
                    // Check if payroll already exists for this employee, month, and year
                    $stmt = $conn->prepare("
                        SELECT COUNT(*) FROM payroll 
                        WHERE employee_id = ? AND month = ? AND year = ?
                    ");
                    $stmt->execute([$employee['id'], $month, $year]);
                    $exists = $stmt->fetchColumn();
                    
                    if($exists) {
                        continue; // Skip if already processed
                    }
                    
                    // Calculate total hours worked in the month with status 'approved' or 'corrected'
                    $start_date = sprintf('%04d-%02d-01', $year, $month);
                    $end_date = date('Y-m-t', strtotime($start_date)); // Last day of month
                    
                    $stmt = $conn->prepare("
                        SELECT SUM(
                            TIME_TO_SEC(TIMEDIFF(time_out, time_in)) / 3600
                        ) as total_hours
                        FROM work_hours 
                        WHERE employee_id = ? 
                        AND date BETWEEN ? AND ? 
                        AND (status = 'approved' OR status = 'corrected')
                    ");
                    $stmt->execute([$employee['id'], $start_date, $end_date]);
                    $result = $stmt->fetch();
                    $total_hours = floatval($result['total_hours'] ?? 0);
                    
                    // Standard hours in a month (based on 8 hours/day, 22 days)
                    $standard_hours = 176;
                    
                    // Calculate base pay and total allowance
                    $base_salary = floatval($employee['base_salary']);
                    $total_allowance = floatval($employee['transport_allowance']) +
                                     floatval($employee['meal_allowance']) +
                                     floatval($employee['health_allowance']) +
                                     floatval($employee['position_allowance']) +
                                     floatval($employee['attendance_allowance']) +
                                     floatval($employee['family_allowance']) +
                                     floatval($employee['communication_allowance']) +
                                     floatval($employee['education_allowance']);
                    
                    $hourly_rate = $base_salary / $standard_hours;
                    
                    // Deductions (for now, just a simple tax calculation as example)
                    $deductions = ($base_salary + $total_allowance) * 0.1; // 10% tax
                    
                    // Calculate net salary
                    $net_salary = $base_salary + $total_allowance - $deductions;
                    
                    // Insert into payroll table
                    $stmt = $conn->prepare("
                        INSERT INTO payroll (
                            employee_id, month, year, base_salary, 
                            transport_allowance, meal_allowance, health_allowance,
                            position_allowance, attendance_allowance,
                            family_allowance, communication_allowance, education_allowance,
                            deductions, net_salary, status
                        ) VALUES (
                            ?, ?, ?, ?, 
                            ?, ?, ?, ?, ?, 
                            ?, ?, ?, ?, 'processing'
                        )
                    ");
                    $stmt->execute([
                        $employee['id'], $month, $year, $base_salary, 
                        floatval($employee['transport_allowance']),
                        floatval($employee['meal_allowance']),
                        floatval($employee['health_allowance']),
                        floatval($employee['position_allowance']),
                        floatval($employee['attendance_allowance']),
                        floatval($employee['family_allowance']),
                        floatval($employee['communication_allowance']),
                        floatval($employee['education_allowance']),
                        $deductions, $net_salary
                    ]);
                    
                    $processed_count++;
                }
                
                // Commit transaction
                $conn->commit();
                
                if($processed_count > 0) {
                    set_alert('success', 'Generated payroll for ' . $processed_count . ' employee(s)');
                } else {
                    set_alert('info', 'No new payrolls to generate for this period');
                }
            } catch (PDOException $e) {
                // Rollback transaction on error
                $conn->rollBack();
                set_alert('danger', 'Error: ' . $e->getMessage());
            }
        }
    } elseif(isset($_POST['finalize_payroll'])) {
        $id = intval($_POST['id']);
        
        try {
            $stmt = $conn->prepare("
                UPDATE payroll SET status = 'finalized' WHERE id = ?
            ");
            $result = $stmt->execute([$id]);
            
            if($result) {
                set_alert('success', 'Payroll finalized');
            } else {
                set_alert('danger', 'Failed to finalize payroll');
            }
        } catch (PDOException $e) {
            set_alert('danger', 'Error: ' . $e->getMessage());
        }
    } elseif(isset($_POST['mark_as_sent'])) {
        $id = intval($_POST['id']);
        
        try {
            $stmt = $conn->prepare("
                UPDATE payroll SET status = 'sent' WHERE id = ?
            ");
            $result = $stmt->execute([$id]);
            
            if($result) {
                set_alert('success', 'Payroll marked as sent');
            } else {
                set_alert('danger', 'Failed to mark payroll as sent');
            }
        } catch (PDOException $e) {
            set_alert('danger', 'Error: ' . $e->getMessage());
        }
    }
}

// Get all payrolls
$stmt = $conn->query("
    SELECT p.*, u.name as employee_name,
           (p.transport_allowance + p.meal_allowance + p.health_allowance +
            p.position_allowance + p.attendance_allowance + p.family_allowance +
            p.communication_allowance + p.education_allowance) as total_allowance
    FROM payroll p 
    JOIN users u ON p.employee_id = u.id 
    ORDER BY p.year DESC, p.month DESC, u.name
");
$payrolls = $stmt->fetchAll();
?>

<!-- Include admin CSS -->
<link rel="stylesheet" href="../assets/css/admin.css">
<!-- Include admin utilities -->
<script src="../assets/js/admin-utils.js"></script>

<div class="admin-container">
    <div class="page-header">
        <h2><i class="fas fa-money-bill-wave"></i> Payroll Processing</h2>
    </div>
    
    <?php echo display_alert(); ?>

    <?php 
    // Get payroll statistics
    $stmt = $conn->query("
        SELECT 
            COUNT(*) as total_payroll,
            COUNT(CASE WHEN status = 'processing' THEN 1 END) as processing_count,
            COUNT(CASE WHEN status = 'finalized' THEN 1 END) as finalized_count,
            COUNT(CASE WHEN status = 'sent' THEN 1 END) as sent_count,
            SUM(net_salary) as total_salary
        FROM payroll
    ");
    $stats = $stmt->fetch();
    ?>

    <div class="stats-cards">
        <div class="stat-card payroll-card">
            <div class="stat-icon">
                <i class="fas fa-file-invoice-dollar"></i>
            </div>
            <div class="stat-details">
                <h3>Total Payroll</h3>
                <p class="stat-number"><?php echo $stats['total_payroll']; ?></p>
            </div>
        </div>
        
        <div class="stat-card pending-card">
            <div class="stat-icon">
                <i class="fas fa-hourglass-half"></i>
            </div>
            <div class="stat-details">
                <h3>Processing</h3>
                <p class="stat-number"><?php echo $stats['processing_count']; ?></p>
            </div>
        </div>
        
        <div class="stat-card employee-card">
            <div class="stat-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="stat-details">
                <h3>Finalized</h3>
                <p class="stat-number"><?php echo $stats['finalized_count']; ?></p>
            </div>
        </div>
        
        <div class="stat-card" style="border-left-color: var(--success-color);">
            <div class="stat-icon" style="background-color: rgba(40, 167, 69, 0.08); color: var(--success-color);">
                <i class="fas fa-money-bill-wave"></i>
            </div>
            <div class="stat-details">
                <h3>Total Salary</h3>
                <p class="stat-number format-currency"><?php echo $stats['total_salary']; ?></p>
            </div>
        </div>
    </div>

    <div class="admin-card">
        <div class="card-header">
            <h3><i class="fas fa-calculator"></i> Generate Payroll</h3>
        </div>
        <div class="card-body">
            <form method="post" action="" class="admin-form">
                <div class="d-flex" style="gap: 20px; flex-wrap: wrap;">
                    <div class="form-group">
                        <label for="month" class="form-label">Month:</label>
                        <select name="month" id="month" class="form-input" required>
                            <?php for($i = 1; $i <= 12; $i++): ?>
                            <option value="<?php echo $i; ?>" <?php echo ($i == date('n')) ? 'selected' : ''; ?>>
                                <?php echo date('F', mktime(0, 0, 0, $i, 1)); ?>
                            </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="year" class="form-label">Year:</label>
                        <select name="year" id="year" class="form-input" required>
                            <?php for($y = date('Y'); $y >= date('Y')-2; $y--): ?>
                            <option value="<?php echo $y; ?>"><?php echo $y; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="form-group" style="align-self: flex-end;">
                        <button type="submit" name="generate_payroll" class="admin-btn admin-btn-primary">
                            <i class="fas fa-cog"></i> Generate Payroll
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <div class="admin-card mt-4">
        <div class="card-header">
            <h3><i class="fas fa-list"></i> Payroll Records</h3>
        </div>
        <div class="card-body">
            <?php if(count($payrolls) > 0): ?>
                <div class="table-responsive">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Employee</th>
                                <th>Period</th>
                                <th>Base Salary</th>
                                <th>Allowances</th>
                                <th>Deductions</th>
                                <th>Net Salary</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($payrolls as $payroll): ?>
                                <tr>
                                    <td><?php echo $payroll['id']; ?></td>
                                    <td><?php echo htmlspecialchars($payroll['employee_name']); ?></td>
                                    <td><?php echo date('F Y', mktime(0, 0, 0, $payroll['month'], 1, $payroll['year'])); ?></td>
                                    <td><?php echo format_money($payroll['base_salary']); ?></td>
                                    <td><?php echo format_money($payroll['total_allowance']); ?></td>
                                    <td><?php echo format_money($payroll['deductions']); ?></td>
                                    <td><?php echo format_money($payroll['net_salary']); ?></td>
                                    <td>
                                        <span class="status-badge <?php echo strtolower($payroll['status']); ?>">
                                            <?php echo ucfirst($payroll['status']); ?>
                                        </span>
                                    </td>
                                    <td class="actions">
                                        <a href="javascript:void(0)" onclick="showPayrollDetails(<?php echo $payroll['id']; ?>)" class="admin-btn admin-btn-sm admin-btn-primary">
                                            <i class="fas fa-eye"></i> Details
                                        </a>
                                        
                                        <?php if($payroll['status'] === 'processing'): ?>
                                            <form method="post" style="display: inline-block;" onsubmit="return confirm('Are you sure you want to finalize this payroll?')">
                                                <input type="hidden" name="id" value="<?php echo $payroll['id']; ?>">
                                                <button type="submit" name="finalize_payroll" class="admin-btn admin-btn-sm admin-btn-success">
                                                    <i class="fas fa-check"></i> Finalize
                                                </button>
                                            </form>
                                        <?php elseif($payroll['status'] === 'finalized'): ?>
                                            <form method="post" style="display: inline-block;" onsubmit="return confirm('Mark this payroll as sent to accounting?')">
                                                <input type="hidden" name="id" value="<?php echo $payroll['id']; ?>">
                                                <button type="submit" name="mark_as_sent" class="admin-btn admin-btn-sm">
                                                    <i class="fas fa-paper-plane"></i> Mark Sent
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="no-data">
                    <i class="fas fa-inbox"></i>
                    <p>No payroll records found</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Payroll Details Modal -->
<div id="payrollDetailsModal" class="modal">
    <div class="modal-content">
        <span class="modal-close" onclick="closeDetailsModal()">&times;</span>
        <h2>Payroll Details</h2>
        <div id="payrollDetailsContent" class="mt-3">
            <div class="loading"><i class="fas fa-spinner fa-spin"></i> Loading details...</div>
        </div>
    </div>
</div>

<style>
/* Update status badge styles to use CSS variables */
.status-badge.processing {
    background-color: var(--info-color);
    color: white;
}

.status-badge.finalized {
    background-color: var(--primary-color);
    color: white;
}

.status-badge.sent {
    background-color: var(--success-color);
    color: white;
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
    max-width: 700px;
    position: relative;
    animation: fadeInUp 0.3s ease;
}

.modal-close {
    position: absolute;
    top: 15px;
    right: 20px;
    font-size: 24px;
    cursor: pointer;
    color: #888;
    transition: color 0.2s;
}

.modal-close:hover {
    color: #000;
}
</style>

<script>
// Function to show payroll details in a modal
function showPayrollDetails(payrollId) {
    const modal = document.getElementById('payrollDetailsModal');
    const contentDiv = document.getElementById('payrollDetailsContent');
    
    // Show loading indicator
    modal.style.display = 'block';
    
    // Fetch payroll details using AJAX
    fetch(`get_payroll_details.php?id=${payrollId}`)
        .then(response => response.text())
        .then(data => {
            contentDiv.innerHTML = data;
        })
        .catch(error => {
            contentDiv.innerHTML = `<div class="admin-alert admin-alert-danger">
                <i class="fas fa-exclamation-circle"></i> Error loading payroll details: ${error.message}
            </div>`;
        });
}

// Function to close the details modal
function closeDetailsModal() {
    document.getElementById('payrollDetailsModal').style.display = 'none';
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('payrollDetailsModal');
    if (event.target == modal) {
        closeDetailsModal();
    }
}
</script>

<?php require_once '../includes/footer.php'; ?> 