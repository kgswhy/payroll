<?php
require_once '../auth/check_session.php';
header('Content-Type: application/json');

// Check if user is a manager
if($_SESSION['user_role'] !== 'manager') {
    echo json_encode(['error' => 'Unauthorized access']);
    exit;
}

// Get manager ID
$manager_id = $_SESSION['user_id'];

// Get filter parameters
$status = isset($_GET['status']) ? sanitize_input($_GET['status']) : 'all';

try {
    // Base query
    $query = "
        SELECT o.*, u.name as employee_name, u.position,
               m.name as manager_name
        FROM overtime o
        JOIN users u ON o.employee_id = u.id
        LEFT JOIN users m ON o.reviewed_by = m.id
        WHERE u.manager_id = ?
    ";
    
    $params = [$manager_id];
    
    // Add status filter if not 'all'
    if($status !== 'all') {
        $query .= " AND o.status = ?";
        $params[] = $status;
    }
    
    // Order by date and status
    $query .= " ORDER BY o.date DESC, o.created_at DESC LIMIT 100";
    
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $overtime_records = $stmt->fetchAll();
    
    // Format the data for response
    $formatted_records = [];
    foreach($overtime_records as $record) {
        $formatted_records[] = [
            'id' => $record['id'],
            'employee' => [
                'id' => $record['employee_id'],
                'name' => $record['employee_name'],
                'position' => $record['position']
            ],
            'date' => format_date($record['date']),
            'time' => date('h:i A', strtotime($record['start_time'])) . ' - ' . date('h:i A', strtotime($record['end_time'])),
            'hours' => $record['hours'],
            'reason' => $record['reason'],
            'status' => $record['status'],
            'reviewed_by' => $record['manager_name'] ? $record['manager_name'] : null,
            'reviewed_at' => $record['reviewed_at'] ? date('M j, Y', strtotime($record['reviewed_at'])) : null,
            'created_at' => date('M j, Y', strtotime($record['created_at']))
        ];
    }
    
    echo json_encode([
        'status' => 'success',
        'data' => $formatted_records,
        'count' => count($formatted_records)
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error: ' . $e->getMessage()
    ]);
} 