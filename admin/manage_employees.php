<?php
require_once '../auth/check_session.php';
require_once '../includes/header.php';

// Process form submission for adding/editing employee
if($_SERVER['REQUEST_METHOD'] === 'POST') {
    if(isset($_POST['add_employee'])) {
        $name = sanitize_input($_POST['name']);
        $email = sanitize_input($_POST['email']);
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $role = sanitize_input($_POST['role']);
        $position = sanitize_input($_POST['position']);
        $base_salary = floatval($_POST['base_salary']);
        $allowance = floatval($_POST['allowance']);
        $manager_id = !empty($_POST['manager_id']) ? intval($_POST['manager_id']) : null;
        
        try {
            $stmt = $conn->prepare("
                INSERT INTO users (name, email, password, role, position, base_salary, allowance, manager_id) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $result = $stmt->execute([$name, $email, $password, $role, $position, $base_salary, $allowance, $manager_id]);
            
            if($result) {
                set_alert('success', 'Employee added successfully');
            } else {
                set_alert('danger', 'Failed to add employee');
            }
        } catch (PDOException $e) {
            set_alert('danger', 'Error: ' . $e->getMessage());
        }
    } elseif(isset($_POST['edit_employee'])) {
        $id = intval($_POST['id']);
        $name = sanitize_input($_POST['name']);
        $email = sanitize_input($_POST['email']);
        $role = sanitize_input($_POST['role']);
        $position = sanitize_input($_POST['position']);
        $base_salary = floatval($_POST['base_salary']);
        $allowance = floatval($_POST['allowance']);
        $manager_id = !empty($_POST['manager_id']) ? intval($_POST['manager_id']) : null;
        
        try {
            // Check if password is being updated
            if(!empty($_POST['password'])) {
                $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                $stmt = $conn->prepare("
                    UPDATE users 
                    SET name = ?, email = ?, password = ?, role = ?, position = ?, 
                        base_salary = ?, allowance = ?, manager_id = ? 
                    WHERE id = ?
                ");
                $result = $stmt->execute([$name, $email, $password, $role, $position, $base_salary, $allowance, $manager_id, $id]);
            } else {
                $stmt = $conn->prepare("
                    UPDATE users 
                    SET name = ?, email = ?, role = ?, position = ?, 
                        base_salary = ?, allowance = ?, manager_id = ? 
                    WHERE id = ?
                ");
                $result = $stmt->execute([$name, $email, $role, $position, $base_salary, $allowance, $manager_id, $id]);
            }
            
            if($result) {
                set_alert('success', 'Employee updated successfully');
            } else {
                set_alert('danger', 'Failed to update employee');
            }
        } catch (PDOException $e) {
            set_alert('danger', 'Error: ' . $e->getMessage());
        }
    } elseif(isset($_POST['delete_employee'])) {
        $id = intval($_POST['id']);
        
        try {
            $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
            $result = $stmt->execute([$id]);
            
            if($result) {
                set_alert('success', 'Employee deleted successfully');
            } else {
                set_alert('danger', 'Failed to delete employee');
            }
        } catch (PDOException $e) {
            set_alert('danger', 'Error: ' . $e->getMessage());
        }
    }
}

// Get all employees
$stmt = $conn->query("
    SELECT u.*, m.name as manager_name 
    FROM users u 
    LEFT JOIN users m ON u.manager_id = m.id 
    WHERE u.role != 'admin'
    ORDER BY u.name
");
$employees = $stmt->fetchAll();

// Get all managers for dropdown
$stmt = $conn->query("SELECT id, name FROM users WHERE role = 'manager' ORDER BY name");
$managers = $stmt->fetchAll();
?>

<div class="container">
    <h2>Manage Employees</h2>
    
    <div class="action-buttons">
        <button type="button" onclick="showAddForm()">Add New Employee</button>
    </div>
    
    <!-- Add Employee Form (Hidden by default) -->
    <div id="add-employee-form" style="display: none;">
        <h3>Add New Employee</h3>
        <form method="post" action="">
            <div class="form-group">
                <label for="name">Name:</label>
                <input type="text" name="name" id="name" required>
            </div>
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" name="email" id="email" required>
            </div>
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" name="password" id="password" required>
            </div>
            <div class="form-group">
                <label for="role">Role:</label>
                <select name="role" id="role" required>
                    <option value="employee" selected>Employee</option>
                    <option value="manager">Manager</option>
                </select>
            </div>
            <div class="form-group">
                <label for="position">Position:</label>
                <input type="text" name="position" id="position">
            </div>
            <div class="form-group">
                <label for="base_salary">Base Salary:</label>
                <input type="number" name="base_salary" id="base_salary" step="0.01" min="0">
            </div>
            <div class="form-group">
                <label for="allowance">Allowance:</label>
                <input type="number" name="allowance" id="allowance" step="0.01" min="0">
            </div>
            <div class="form-group">
                <label for="manager_id">Manager:</label>
                <select name="manager_id" id="manager_id">
                    <option value="">-- Select Manager --</option>
                    <?php foreach($managers as $manager): ?>
                    <option value="<?php echo $manager['id']; ?>"><?php echo $manager['name']; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" name="add_employee">Add Employee</button>
            <button type="button" onclick="hideAddForm()">Cancel</button>
        </form>
    </div>
    
    <!-- Edit Employee Form (Hidden by default) -->
    <div id="edit-employee-form" style="display: none;">
        <h3>Edit Employee</h3>
        <form method="post" action="">
            <input type="hidden" name="id" id="edit_id">
            <div class="form-group">
                <label for="edit_name">Name:</label>
                <input type="text" name="name" id="edit_name" required>
            </div>
            <div class="form-group">
                <label for="edit_email">Email:</label>
                <input type="email" name="email" id="edit_email" required>
            </div>
            <div class="form-group">
                <label for="edit_password">Password (leave blank to keep unchanged):</label>
                <input type="password" name="password" id="edit_password">
            </div>
            <div class="form-group">
                <label for="edit_role">Role:</label>
                <select name="role" id="edit_role" required>
                    <option value="employee">Employee</option>
                    <option value="manager">Manager</option>
                </select>
            </div>
            <div class="form-group">
                <label for="edit_position">Position:</label>
                <input type="text" name="position" id="edit_position">
            </div>
            <div class="form-group">
                <label for="edit_base_salary">Base Salary:</label>
                <input type="number" name="base_salary" id="edit_base_salary" step="0.01" min="0">
            </div>
            <div class="form-group">
                <label for="edit_allowance">Allowance:</label>
                <input type="number" name="allowance" id="edit_allowance" step="0.01" min="0">
            </div>
            <div class="form-group">
                <label for="edit_manager_id">Manager:</label>
                <select name="manager_id" id="edit_manager_id">
                    <option value="">-- Select Manager --</option>
                    <?php foreach($managers as $manager): ?>
                    <option value="<?php echo $manager['id']; ?>"><?php echo $manager['name']; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" name="edit_employee">Update Employee</button>
            <button type="button" onclick="hideEditForm()">Cancel</button>
        </form>
    </div>
    
    <!-- Employee List -->
    <div class="employee-list">
        <h3>All Employees</h3>
        <?php if(count($employees) > 0): ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Position</th>
                        <th>Base Salary</th>
                        <th>Allowance</th>
                        <th>Manager</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($employees as $employee): ?>
                    <tr>
                        <td><?php echo $employee['id']; ?></td>
                        <td><?php echo $employee['name']; ?></td>
                        <td><?php echo $employee['email']; ?></td>
                        <td><?php echo ucfirst($employee['role']); ?></td>
                        <td><?php echo $employee['position']; ?></td>
                        <td><?php echo format_money($employee['base_salary']); ?></td>
                        <td><?php echo format_money($employee['allowance']); ?></td>
                        <td><?php echo $employee['manager_name']; ?></td>
                        <td>
                            <button type="button" onclick="showEditForm(<?php 
                                echo htmlspecialchars(json_encode($employee), ENT_QUOTES, 'UTF-8'); 
                            ?>)">Edit</button>
                            
                            <form method="post" action="" style="display: inline;">
                                <input type="hidden" name="id" value="<?php echo $employee['id']; ?>">
                                <button type="submit" name="delete_employee" class="delete-btn" onclick="return confirm('Are you sure you want to delete this employee?')">Delete</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No employees found.</p>
        <?php endif; ?>
    </div>
</div>

<script>
function showAddForm() {
    document.getElementById('add-employee-form').style.display = 'block';
}

function hideAddForm() {
    document.getElementById('add-employee-form').style.display = 'none';
}

function showEditForm(employee) {
    document.getElementById('edit_id').value = employee.id;
    document.getElementById('edit_name').value = employee.name;
    document.getElementById('edit_email').value = employee.email;
    document.getElementById('edit_role').value = employee.role;
    document.getElementById('edit_position').value = employee.position || '';
    document.getElementById('edit_base_salary').value = employee.base_salary || '';
    document.getElementById('edit_allowance').value = employee.allowance || '';
    document.getElementById('edit_manager_id').value = employee.manager_id || '';
    
    document.getElementById('edit-employee-form').style.display = 'block';
}

function hideEditForm() {
    document.getElementById('edit-employee-form').style.display = 'none';
}
</script>

<?php require_once '../includes/footer.php'; ?> 