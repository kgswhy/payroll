<?php
require_once '../auth/check_session.php';
require_once '../includes/header.php';

// Get employee details
$employee_id = $_SESSION['user_id'];
$employee = get_user_by_id($employee_id);

// Get employee's payroll history
$payroll_history = get_employee_payroll($employee_id);
?>

<div class="container">
    <h2>My Salary Information</h2>
    
    <div class="salary-details">
        <h3>Salary Details</h3>
        <div class="salary-info">
            <p><strong>Base Salary:</strong> <?php echo format_money($employee['base_salary']); ?></p>
            <p><strong>Allowance:</strong> <?php echo format_money($employee['allowance']); ?></p>
            <p><strong>Position:</strong> <?php echo $employee['position']; ?></p>
        </div>
    </div>
    
    <div class="payroll-history">
        <h3>Payroll History</h3>
        <?php if(count($payroll_history) > 0): ?>
            <table class="data-table">
                <thead>
                    <tr>
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
                    <?php foreach($payroll_history as $payroll): ?>
                    <tr>
                        <td><?php echo get_month_name($payroll['month']) . ' ' . $payroll['year']; ?></td>
                        <td><?php echo format_money($payroll['base_salary']); ?></td>
                        <td><?php echo format_money($payroll['allowance']); ?></td>
                        <td><?php echo format_money($payroll['deductions']); ?></td>
                        <td><?php echo format_money($payroll['net_salary']); ?></td>
                        <td><?php echo ucfirst($payroll['status']); ?></td>
                        <td>
                            <?php if($payroll['status'] == 'sent'): ?>
                            <button type="button" onclick="printPayslip(<?php echo $payroll['id']; ?>)">
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
        <?php else: ?>
            <p>No payroll records found.</p>
        <?php endif; ?>
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
                        <span class="label">Allowance:</span>
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
</div>

<script>
function printPayslip(payrollId) {
    // Find the payroll data in the table
    const rows = document.querySelectorAll('.data-table tbody tr');
    let payrollData = null;
    
    rows.forEach((row, index) => {
        if (index + 1 === payrollId) {
            const cells = row.querySelectorAll('td');
            payrollData = {
                period: cells[0].innerText,
                base: cells[1].innerText,
                allowance: cells[2].innerText,
                deductions: cells[3].innerText,
                net: cells[4].innerText
            };
        }
    });
    
    if (!payrollData) return;
    
    // Fill the template
    document.getElementById('print-period').innerText = payrollData.period;
    document.getElementById('print-base').innerText = payrollData.base;
    document.getElementById('print-allowance').innerText = payrollData.allowance;
    document.getElementById('print-deductions').innerText = payrollData.deductions;
    document.getElementById('print-net').innerText = payrollData.net;
    
    // Clone the template for printing
    const template = document.getElementById('payslip-template').innerHTML;
    
    // Open print window
    const printWindow = window.open('', '', 'height=600,width=800');
    printWindow.document.write('<html><head><title>Payslip</title>');
    printWindow.document.write('<style>');
    printWindow.document.write(`
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; }
        .payslip-container { max-width: 800px; margin: 0 auto; border: 1px solid #ccc; padding: 20px; }
        .payslip-header { border-bottom: 2px solid #2c3e50; padding-bottom: 10px; margin-bottom: 20px; }
        .payslip-body { margin-bottom: 20px; }
        .detail { display: flex; justify-content: space-between; margin-bottom: 10px; padding: 5px 0; border-bottom: 1px solid #eee; }
        .detail.total { font-weight: bold; border-top: 2px solid #2c3e50; border-bottom: none; margin-top: 15px; padding-top: 10px; }
        .payslip-footer { border-top: 1px solid #ccc; padding-top: 10px; font-size: 12px; color: #777; }
    `);
    printWindow.document.write('</style></head><body>');
    printWindow.document.write(template);
    printWindow.document.write('</body></html>');
    
    printWindow.document.close();
    printWindow.focus();
    
    // Print after a short delay to ensure content is loaded
    setTimeout(() => {
        printWindow.print();
        printWindow.close();
    }, 500);
}
</script>

<?php require_once '../includes/footer.php'; ?> 