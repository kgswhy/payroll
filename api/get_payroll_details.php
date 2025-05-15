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
        SELECT p.*, u.name, u.position, u.id as employee_id
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
        'employee' => [
            'id' => $payroll['employee_id'],
            'name' => $payroll['name'],
            'position' => $payroll['position']
        ],
        'period' => get_month_name($payroll['month']) . ' ' . $payroll['year'],
        'allowances' => [
            [
                'label' => 'Tunjangan Transportasi',
                'value' => format_money($payroll['transport_allowance'])
            ],
            [
                'label' => 'Tunjangan Makan',
                'value' => format_money($payroll['meal_allowance'])
            ],
            [
                'label' => 'Tunjangan Kesehatan',
                'value' => format_money($payroll['health_allowance'])
            ],
            [
                'label' => 'Tunjangan Jabatan',
                'value' => format_money($payroll['position_allowance'])
            ],
            [
                'label' => 'Tunjangan Kehadiran',
                'value' => format_money($payroll['attendance_allowance'])
            ],
            [
                'label' => 'Tunjangan Keluarga',
                'value' => format_money($payroll['family_allowance'])
            ],
            [
                'label' => 'Tunjangan Komunikasi',
                'value' => format_money($payroll['communication_allowance'])
            ],
            [
                'label' => 'Tunjangan Pendidikan',
                'value' => format_money($payroll['education_allowance'])
            ]
        ],
        'total_allowance' => format_money(
            $payroll['transport_allowance'] +
            $payroll['meal_allowance'] +
            $payroll['health_allowance'] +
            $payroll['position_allowance'] +
            $payroll['attendance_allowance'] +
            $payroll['family_allowance'] +
            $payroll['communication_allowance'] +
            $payroll['education_allowance']
        )
    ];
    
    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode($response);
    
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    exit;
} 