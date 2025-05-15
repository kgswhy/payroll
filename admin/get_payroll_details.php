<?php
// Prevent any output before headers
error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once '../auth/check_session.php';
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

// Check if user is admin or manager
if ($_SESSION['user_role'] !== 'admin' && $_SESSION['user_role'] !== 'manager') {
    http_response_code(403);
    echo '<div class="admin-alert admin-alert-danger">
            <i class="fas fa-exclamation-circle"></i> Unauthorized access
          </div>';
    exit;
}

// Check if ID is provided
if (!isset($_GET['id'])) {
    http_response_code(400);
    echo '<div class="admin-alert admin-alert-danger">
            <i class="fas fa-exclamation-circle"></i> Payroll ID is required
          </div>';
    exit;
}

$id = intval($_GET['id']);

try {
    // Get detailed payroll information
    $stmt = $conn->prepare("
        SELECT p.*, u.name as employee_name, u.role, u.position
        FROM payroll p
        JOIN users u ON p.employee_id = u.id
        WHERE p.id = ?
    ");
    
    $stmt->execute([$id]);
    $payroll = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$payroll) {
        http_response_code(404);
        echo '<div class="admin-alert admin-alert-danger">
                <i class="fas fa-exclamation-circle"></i> Payroll not found
              </div>';
        exit;
    }
    
    // Calculate total allowance
    $total_allowance = 
        floatval($payroll['transport_allowance']) +
        floatval($payroll['meal_allowance']) +
        floatval($payroll['health_allowance']) +
        floatval($payroll['position_allowance']) +
        floatval($payroll['attendance_allowance']) +
        floatval($payroll['family_allowance']) +
        floatval($payroll['communication_allowance']) +
        floatval($payroll['education_allowance']);
    
    // Format period
    $period = date('F Y', mktime(0, 0, 0, $payroll['month'], 1, $payroll['year']));
    
    // Output HTML content
    ?>
    <div class="payroll-details">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3><?php echo htmlspecialchars($payroll['employee_name']); ?></h3>
            <div>
                <span class="status-badge <?php echo strtolower($payroll['status']); ?>">
                    <?php echo ucfirst($payroll['status']); ?>
                </span>
                <span class="ml-2"><?php echo $period; ?></span>
            </div>
        </div>
        
        <div class="admin-card">
            <div class="card-header">
                <h3><i class="fas fa-info-circle"></i> Basic Information</h3>
            </div>
            <div class="card-body">
                <div class="detail-row">
                    <div class="detail-label">Position:</div>
                    <div class="detail-value"><?php echo htmlspecialchars($payroll['position'] ?? $payroll['role']); ?></div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Base Salary:</div>
                    <div class="detail-value"><?php echo format_money($payroll['base_salary']); ?></div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Total Allowances:</div>
                    <div class="detail-value"><?php echo format_money($total_allowance); ?></div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Deductions:</div>
                    <div class="detail-value"><?php echo format_money($payroll['deductions']); ?></div>
                </div>
                <div class="detail-row total">
                    <div class="detail-label">Net Salary:</div>
                    <div class="detail-value"><?php echo format_money($payroll['net_salary']); ?></div>
                </div>
            </div>
        </div>
        
        <div class="admin-card mt-3">
            <div class="card-header">
                <h3><i class="fas fa-list"></i> Allowance Breakdown</h3>
            </div>
            <div class="card-body">
                <div class="allowance-grid">
                    <?php if ($payroll['transport_allowance'] > 0): ?>
                    <div class="allowance-item">
                        <div class="allowance-label">Transport Allowance</div>
                        <div class="allowance-value"><?php echo format_money($payroll['transport_allowance']); ?></div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($payroll['meal_allowance'] > 0): ?>
                    <div class="allowance-item">
                        <div class="allowance-label">Meal Allowance</div>
                        <div class="allowance-value"><?php echo format_money($payroll['meal_allowance']); ?></div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($payroll['health_allowance'] > 0): ?>
                    <div class="allowance-item">
                        <div class="allowance-label">Health Allowance</div>
                        <div class="allowance-value"><?php echo format_money($payroll['health_allowance']); ?></div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($payroll['position_allowance'] > 0): ?>
                    <div class="allowance-item">
                        <div class="allowance-label">Position Allowance</div>
                        <div class="allowance-value"><?php echo format_money($payroll['position_allowance']); ?></div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($payroll['attendance_allowance'] > 0): ?>
                    <div class="allowance-item">
                        <div class="allowance-label">Attendance Allowance</div>
                        <div class="allowance-value"><?php echo format_money($payroll['attendance_allowance']); ?></div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($payroll['family_allowance'] > 0): ?>
                    <div class="allowance-item">
                        <div class="allowance-label">Family Allowance</div>
                        <div class="allowance-value"><?php echo format_money($payroll['family_allowance']); ?></div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($payroll['communication_allowance'] > 0): ?>
                    <div class="allowance-item">
                        <div class="allowance-label">Communication Allowance</div>
                        <div class="allowance-value"><?php echo format_money($payroll['communication_allowance']); ?></div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($payroll['education_allowance'] > 0): ?>
                    <div class="allowance-item">
                        <div class="allowance-label">Education Allowance</div>
                        <div class="allowance-value"><?php echo format_money($payroll['education_allowance']); ?></div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <style>
    .payroll-details {
        padding: 10px 0;
    }
    
    .detail-row {
        display: flex;
        justify-content: space-between;
        padding: 10px 0;
        border-bottom: 1px solid var(--border-color);
    }
    
    .detail-row:last-child {
        border-bottom: none;
    }
    
    .detail-row.total {
        font-weight: bold;
        font-size: 1.1em;
        margin-top: 10px;
        padding-top: 10px;
        border-top: 2px solid var(--border-color);
    }
    
    .detail-label {
        color: #666;
    }
    
    .allowance-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 15px;
    }
    
    .allowance-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 12px 15px;
        background-color: var(--bg-light);
        border-radius: 8px;
        transition: transform 0.2s;
    }
    
    .allowance-item:hover {
        transform: translateY(-2px);
        box-shadow: 0 3px 10px rgba(0, 0, 0, 0.05);
    }
    
    .allowance-label {
        color: #666;
    }
    
    /* Status badge colors */
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
    
    @media (max-width: 768px) {
        .allowance-grid {
            grid-template-columns: 1fr;
        }
    }
    </style>
    <?php
    
} catch (PDOException $e) {
    http_response_code(500);
    echo '<div class="admin-alert admin-alert-danger">
            <i class="fas fa-exclamation-circle"></i> Database error: ' . htmlspecialchars($e->getMessage()) . '
          </div>';
} catch (Exception $e) {
    http_response_code(500);
    echo '<div class="admin-alert admin-alert-danger">
            <i class="fas fa-exclamation-circle"></i> Server error: ' . htmlspecialchars($e->getMessage()) . '
          </div>';
} 