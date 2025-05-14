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
                // Get all employees
                $employees = get_users_by_role('employee');
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
                    
                    // Calculate base pay and overtime
                    $base_salary = floatval($employee['base_salary']);
                    $allowance = floatval($employee['allowance']);
                    $hourly_rate = $base_salary / $standard_hours;
                    
                    // Deductions (for now, just a simple tax calculation as example)
                    $deductions = ($base_salary + $allowance) * 0.1; // 10% tax
                    
                    // Calculate net salary
                    $net_salary = $base_salary + $allowance - $deductions;
                    
                    // Insert into payroll table
                    $stmt = $conn->prepare("
                        INSERT INTO payroll (
                            employee_id, month, year, base_salary, allowance, 
                            deductions, net_salary, status
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, 'processing')
                    ");
                    $stmt->execute([
                        $employee['id'], $month, $year, $base_salary, 
                        $allowance, $deductions, $net_salary
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
    SELECT p.*, u.name as employee_name 
    FROM payroll p 
    JOIN users u ON p.employee_id = u.id 
    ORDER BY p.year DESC, p.month DESC, u.name
");
$payrolls = $stmt->fetchAll();
?>

<div class="container">
    <h2>Payroll Processing</h2>
    
    <div class="generate-payroll">
        <h3>Generate Payroll</h3>
        <form method="post" action="">
            <div class="form-group">
                <label for="month">Month:</label>
                <select name="month" id="month" required>
                    <?php for($i = 1; $i <= 12; $i++): ?>
                    <option value="<?php echo $i; ?>" <?php echo ($i == date('n')) ? 'selected' : ''; ?>>
                        <?php echo date('F', mktime(0, 0, 0, $i, 1)); ?>
                    </option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="year">Year:</label>
                <select name="year" id="year" required>
                    <?php for($i = date('Y') - 2; $i <= date('Y') + 1; $i++): ?>
                    <option value="<?php echo $i; ?>" <?php echo ($i == date('Y')) ? 'selected' : ''; ?>>
                        <?php echo $i; ?>
                    </option>
                    <?php endfor; ?>
                </select>
            </div>
            <button type="submit" name="generate_payroll">Generate Payroll</button>
        </form>
    </div>
    
    <div class="payroll-list">
        <h3>Payroll List</h3>
        <?php if(count($payrolls) > 0): ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Employee</th>
                        <th>Period</th>
                        <th>Base Salary</th>
                        <th>Allowance</th>
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
                        <td><?php echo $payroll['employee_name']; ?></td>
                        <td><?php echo get_month_name($payroll['month']) . ' ' . $payroll['year']; ?></td>
                        <td><?php echo format_money($payroll['base_salary']); ?></td>
                        <td><?php echo format_money($payroll['allowance']); ?></td>
                        <td><?php echo format_money($payroll['deductions']); ?></td>
                        <td><?php echo format_money($payroll['net_salary']); ?></td>
                        <td><?php echo ucfirst($payroll['status']); ?></td>
                        <td>
                            <?php if($payroll['status'] == 'processing'): ?>
                                <form method="post" action="" style="display: inline;">
                                    <input type="hidden" name="id" value="<?php echo $payroll['id']; ?>">
                                    <button type="submit" name="finalize_payroll">Finalize</button>
                                </form>
                            <?php elseif($payroll['status'] == 'finalized'): ?>
                                <form method="post" action="" style="display: inline;">
                                    <input type="hidden" name="id" value="<?php echo $payroll['id']; ?>">
                                    <button type="submit" name="mark_as_sent">Mark as Sent</button>
                                </form>
                            <?php else: ?>
                                <span class="text-muted">No actions</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No payroll records found.</p>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?> 