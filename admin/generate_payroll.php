<?php

// Get all employees with their allowances
$stmt = $conn->query("
    SELECT u.*, m.name as manager_name,
           a.transport_allowance, a.meal_allowance, a.health_allowance,
           a.position_allowance, a.attendance_allowance,
           a.family_allowance, a.communication_allowance, a.education_allowance
    FROM users u 
    LEFT JOIN users m ON u.manager_id = m.id 
    LEFT JOIN allowances a ON u.id = a.user_id
    WHERE u.role != 'admin'
    ORDER BY u.name
");
$employees = $stmt->fetchAll();

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_payroll'])) {
    $month = intval($_POST['month']);
    $year = intval($_POST['year']);

    try {
        $conn->beginTransaction();

        // Check if payroll already exists for this month and year
        $stmt = $conn->prepare("SELECT COUNT(*) FROM payroll WHERE month = ? AND year = ?");
        $stmt->execute([$month, $year]);
        if ($stmt->fetchColumn() > 0) {
            set_alert('danger', 'Payroll for this month and year already exists');
        } else {
            // Generate payroll for each employee
            foreach ($employees as $employee) {
                $base_salary = $employee['base_salary'];
                
                // Calculate total allowances
                $total_allowance = $employee['transport_allowance'] + 
                                 $employee['meal_allowance'] + 
                                 $employee['health_allowance'] + 
                                 $employee['position_allowance'] + 
                                 $employee['attendance_allowance'] + 
                                 $employee['family_allowance'] + 
                                 $employee['communication_allowance'] + 
                                 $employee['education_allowance'];

                // Calculate deductions (BPJS, etc.)
                $deductions = calculate_deductions($base_salary);

                // Calculate net salary
                $net_salary = $base_salary + $total_allowance - $deductions;

                // Insert payroll record
                $stmt = $conn->prepare("
                    INSERT INTO payroll (user_id, month, year, base_salary, deductions, net_salary, status)
                    VALUES (?, ?, ?, ?, ?, ?, 'processing')
                ");
                $stmt->execute([
                    $employee['id'],
                    $month,
                    $year,
                    $base_salary,
                    $deductions,
                    $net_salary
                ]);
            }

            $conn->commit();
            set_alert('success', 'Payroll generated successfully');
        }
    } catch (PDOException $e) {
        $conn->rollBack();
        set_alert('danger', 'Error: ' . $e->getMessage());
    }
} 