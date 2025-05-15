<?php
require_once '../auth/check_session.php';
require_once '../includes/functions.php';

// Ensure the user is authenticated
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Get payroll ID from request
$payroll_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($payroll_id <= 0) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Invalid payroll ID']);
    exit;
}

try {
    // Get payroll data
    $stmt = $conn->prepare("
        SELECT p.*, u.name, u.position, u.id as employee_id,
               (p.transport_allowance + p.meal_allowance + p.health_allowance +
                p.position_allowance + p.attendance_allowance + p.family_allowance +
                p.communication_allowance + p.education_allowance) as total_allowance
        FROM payroll p 
        JOIN users u ON p.employee_id = u.id 
        WHERE p.id = ? AND (p.employee_id = ? OR ? = 1)
    ");
    $is_admin = isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin' ? 1 : 0;
    $stmt->execute([$payroll_id, $_SESSION['user_id'], $is_admin]);
    $payroll = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$payroll) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Payroll not found or access denied']);
        exit;
    }
    
    // Format data for response
    $response = [
        'id' => $payroll['id'],
        'employee_id' => $payroll['employee_id'],
        'name' => $payroll['name'],
        'position' => $payroll['position'],
        'period' => get_month_name($payroll['month']) . ' ' . $payroll['year'],
        'month' => $payroll['month'],
        'year' => $payroll['year'],
        'base_salary' => format_money($payroll['base_salary']),
        'transport_allowance' => format_money($payroll['transport_allowance']),
        'meal_allowance' => format_money($payroll['meal_allowance']),
        'health_allowance' => format_money($payroll['health_allowance']),
        'position_allowance' => format_money($payroll['position_allowance']),
        'attendance_allowance' => format_money($payroll['attendance_allowance']),
        'family_allowance' => format_money($payroll['family_allowance']),
        'communication_allowance' => format_money($payroll['communication_allowance']),
        'education_allowance' => format_money($payroll['education_allowance']),
        'total_allowance' => format_money($payroll['total_allowance']),
        'deductions' => format_money($payroll['deductions']),
        'net_salary' => format_money($payroll['net_salary']),
        'status' => $payroll['status'],
        'created_at' => $payroll['created_at']
    ];
    
    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode($response);
    
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    exit;
} 