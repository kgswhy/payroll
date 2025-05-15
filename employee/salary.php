<?php
require_once '../auth/check_session.php';
require_once '../includes/header.php';

// Get employee details with allowances
$employee_id = $_SESSION['user_id'];
$stmt = $conn->prepare("
    SELECT u.*, 
           a.transport_allowance, a.meal_allowance, a.health_allowance,
           a.position_allowance, a.attendance_allowance,
           a.family_allowance, a.communication_allowance, a.education_allowance
    FROM users u 
    LEFT JOIN allowances a ON u.id = a.user_id
    WHERE u.id = ?
");
$stmt->execute([$employee_id]);
$employee = $stmt->fetch();

// Calculate total allowance
$total_allowance = calculate_total_allowance($employee);

// Get employee's payroll history
$payroll_history = get_employee_payroll($employee_id);
?>

<!-- Include the employee salary CSS -->
<link rel="stylesheet" href="../assets/css/employee-salary.css">

<div class="container">
    <div class="page-header">
        <h2><i class="fas fa-money-check-alt"></i> My Salary Information</h2>
    </div>
    
    <div class="salary-details card">
        <div class="card-header">
            <h3><i class="fas fa-info-circle"></i> Salary Details</h3>
        </div>
        <div class="card-body">
        <div class="salary-info">
                <div class="info-item">
                    <span class="info-label">Base Salary</span>
                    <span class="info-value"><?php echo format_money($employee['base_salary']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Total Allowance</span>
                    <a href="#" onclick="showAllowanceDetails()" class="info-value allowance-link">
                        <?php echo format_money($total_allowance); ?>
                        <i class="fas fa-external-link-alt"></i>
                    </a>
                </div>
                <div class="info-item">
                    <span class="info-label">Position</span>
                    <span class="info-value"><?php echo $employee['position']; ?></span>
                </div>
            </div>
        </div>
    </div>
    
    <div class="payroll-history card">
        <div class="card-header">
            <h3><i class="fas fa-history"></i> Payroll History</h3>
        </div>
        <div class="card-body">
        <?php if(count($payroll_history) > 0): ?>
                <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Period</th>
                        <th>Base Salary</th>
                                <th>Total Allowance</th>
                        <th>Deductions</th>
                        <th>Net Salary</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($payroll_history as $payroll): ?>
                    <tr>
                        <td><?php echo get_month_name($payroll['month']) . ' ' . $payroll['year']; ?></td>
                        <td><?php echo format_money($payroll['base_salary']); ?></td>
                                <td>
                                    <a href="#" onclick="showPayrollAllowanceDetails(<?php echo $payroll['id']; ?>)" class="allowance-link">
                                        <?php 
                                        // Calculate total allowance
                                        $total_allowance = calculate_total_allowance($payroll);
                                        echo format_money($total_allowance); 
                                        ?>
                                        <i class="fas fa-external-link-alt"></i>
                                    </a>
                                </td>
                        <td><?php echo format_money($payroll['deductions']); ?></td>
                        <td><?php echo format_money($payroll['net_salary']); ?></td>
                                <td>
                                    <span class="status-badge <?php echo $payroll['status']; ?>">
                                        <?php echo ucfirst($payroll['status']); ?>
                                    </span>
                                </td>
                        <td>
                            <?php if($payroll['status'] == 'sent'): ?>
                                    <button type="button" class="btn-print" onclick="printPayslip(<?php echo $payroll['id']; ?>)">
                                <i class="fas fa-print"></i> Print
                            </button>
                            <?php else: ?>
                            <span class="text-muted">Not available</span>
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
            <p>No payroll records found.</p>
                </div>
        <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal Overlay -->
<div id="modal-overlay"></div>

<!-- Allowance Details Modal -->
<div id="allowance-details-modal" class="modal-form">
    <div class="modal-header">
        <h3><i class="fas fa-money-bill-wave"></i> Detail Tunjangan</h3>
        <button type="button" class="close-button" onclick="hideAllowanceDetails()">
            <i class="fas fa-times"></i>
        </button>
    </div>
    <div class="modal-content">
        <div class="allowance-details">
            <div class="employee-info">
                <h4><?php echo $employee['name']; ?></h4>
                <p><?php echo $employee['position']; ?></p>
            </div>
            
            <!-- Basic Allowances -->
            <div class="allowance-category">
                <h4 class="allowance-category-title">Dasar</h4>
                <div class="allowance-grid">
                    <div class="allowance-item">
                        <span class="allowance-label">Tunjangan Transportasi</span>
                        <span class="allowance-value"><?php echo format_money($employee['transport_allowance']); ?></span>
                    </div>
                    <div class="allowance-item">
                        <span class="allowance-label">Tunjangan Makan</span>
                        <span class="allowance-value"><?php echo format_money($employee['meal_allowance']); ?></span>
                    </div>
                </div>
            </div>
            
            <!-- Welfare Allowances -->
            <div class="allowance-category">
                <h4 class="allowance-category-title">Kesejahteraan</h4>
                <div class="allowance-grid">
                    <div class="allowance-item">
                        <span class="allowance-label">Tunjangan Kesehatan</span>
                        <span class="allowance-value"><?php echo format_money($employee['health_allowance']); ?></span>
                    </div>
                    <div class="allowance-item">
                        <span class="allowance-label">Tunjangan Keluarga</span>
                        <span class="allowance-value"><?php echo format_money($employee['family_allowance']); ?></span>
                    </div>
                </div>
            </div>
            
            <!-- Professional Allowances -->
            <div class="allowance-category">
                <h4 class="allowance-category-title">Profesional</h4>
                <div class="allowance-grid">
                    <div class="allowance-item">
                        <span class="allowance-label">Tunjangan Jabatan</span>
                        <span class="allowance-value"><?php echo format_money($employee['position_allowance']); ?></span>
                    </div>
                    <div class="allowance-item">
                        <span class="allowance-label">Tunjangan Kehadiran</span>
                        <span class="allowance-value"><?php echo format_money($employee['attendance_allowance']); ?></span>
                    </div>
                    <div class="allowance-item">
                        <span class="allowance-label">Tunjangan Komunikasi</span>
                        <span class="allowance-value"><?php echo format_money($employee['communication_allowance']); ?></span>
                    </div>
                    <div class="allowance-item">
                        <span class="allowance-label">Tunjangan Pendidikan</span>
                        <span class="allowance-value"><?php echo format_money($employee['education_allowance']); ?></span>
                    </div>
                </div>
            </div>
            
            <!-- Total -->
            <div class="allowance-total">
                <span class="total-label">Total Tunjangan</span>
                <span class="total-value"><?php echo format_money($total_allowance); ?></span>
            </div>
        </div>
    </div>
</div>
    
<!-- Payslip Print Template (hidden) -->
<div id="payslip-template" style="display: none;">
    <div class="payslip-container">
        <div class="payslip-header">
            <h2><?php echo APP_NAME; ?> - Payslip</h2>
            <div class="payslip-employee">
                <p><strong>Employee:</strong> <?php echo $employee['name']; ?></p>
                <p><strong>Position:</strong> <?php echo $employee['position']; ?></p>
                <p><strong>Employee ID:</strong> <?php echo $employee_id; ?></p>
            </div>
        </div>
        <div class="payslip-body">
            <div class="payslip-period">
                <p><strong>Period:</strong> <span id="print-period"></span></p>
            </div>
            <div class="payslip-details">
                <div class="detail">
                    <span class="label">Base Salary:</span>
                    <span class="value" id="print-base"></span>
                </div>
                <div class="detail">
                <span class="label">Allowances:</span>
                <div class="allowance-breakdown">
                    <div class="allowance-item">
                        <span class="label">Transportation</span>
                        <span class="value" id="print-transport"></span>
                    </div>
                    <div class="allowance-item">
                        <span class="label">Meal</span>
                        <span class="value" id="print-meal"></span>
                    </div>
                    <div class="allowance-item">
                        <span class="label">Health</span>
                        <span class="value" id="print-health"></span>
                    </div>
                    <div class="allowance-item">
                        <span class="label">Position</span>
                        <span class="value" id="print-position"></span>
                    </div>
                    <div class="allowance-item">
                        <span class="label">Attendance</span>
                        <span class="value" id="print-attendance"></span>
                    </div>
                    <div class="allowance-item" style="display:none">
                        <span class="label">THR</span>
                        <span class="value" id="print-thr"></span>
                    </div>
                    <div class="allowance-item">
                        <span class="label">Family</span>
                        <span class="value" id="print-family"></span>
                    </div>
                    <div class="allowance-item">
                        <span class="label">Communication</span>
                        <span class="value" id="print-communication"></span>
                    </div>
                    <div class="allowance-item">
                        <span class="label">Education</span>
                        <span class="value" id="print-education"></span>
                    </div>
                </div>
            </div>
            <div class="detail">
                <span class="label">Total Allowance:</span>
                <span class="value" id="print-allowance"></span>
                </div>
                <div class="detail">
                    <span class="label">Deductions:</span>
                    <span class="value" id="print-deductions"></span>
                </div>
                <div class="detail total">
                    <span class="label">Net Salary:</span>
                    <span class="value" id="print-net"></span>
                </div>
            </div>
        </div>
        <div class="payslip-footer">
            <p>This is a computer-generated document. No signature is required.</p>
            <p>Generated on: <?php echo date('F j, Y'); ?></p>
        </div>
    </div>
</div>

<!-- Include the employee salary JavaScript -->
<script src="../assets/js/employee-salary.js"></script>

<?php require_once '../includes/footer.php'; ?> 