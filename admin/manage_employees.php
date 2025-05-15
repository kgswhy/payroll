<?php
require_once '../auth/check_session.php';
require_once '../includes/header.php';

// Process form submission for adding/editing employee
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_employee'])) {
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

            if ($result) {
                set_alert('success', 'Employee added successfully');
            } else {
                set_alert('danger', 'Failed to add employee');
            }
        } catch (PDOException $e) {
            set_alert('danger', 'Error: ' . $e->getMessage());
        }
    } elseif (isset($_POST['edit_employee'])) {
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
            if (!empty($_POST['password'])) {
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

            if ($result) {
                set_alert('success', 'Employee updated successfully');
            } else {
                set_alert('danger', 'Failed to update employee');
            }
        } catch (PDOException $e) {
            set_alert('danger', 'Error: ' . $e->getMessage());
        }
    } elseif (isset($_POST['delete_employee'])) {
        $id = intval($_POST['id']);

        try {
            $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
            $result = $stmt->execute([$id]);

            if ($result) {
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
    ORDER BY u.name
");
$employees = $stmt->fetchAll();

// Get all managers for dropdown
$stmt = $conn->query("SELECT id, name FROM users WHERE role = 'manager' ORDER BY name");
$managers = $stmt->fetchAll();
?>

<div class="main-container">
    <div class="page-header">
        <div class="header-content">
            <h2><i class="fas fa-users"></i> Kelola Karyawan</h2>
            <p>Tambah, edit, dan hapus data karyawan</p>
        </div>
        <button class="add-button" onclick="showAddForm()">
            <i class="fas fa-user-plus"></i> Tambah Karyawan
        </button>
    </div>

    <!-- Modal Overlay -->
    <div id="modal-overlay" class="modal-overlay" style="display: none;" onclick="closeAllModals()"></div>

    <!-- Add Employee Form Modal -->
    <div id="add-employee-form" class="modal-form" style="display: none;">
        <div class="modal-header">
            <h3><i class="fas fa-user-plus"></i> Tambah Karyawan Baru</h3>
            <button type="button" class="close-button" onclick="hideAddForm()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form method="post" action="" class="modal-content">
            <div class="form-grid">
                <div class="form-field">
                    <label for="name">
                        <i class="fas fa-user"></i> Nama
                    </label>
                    <div class="input-wrapper">
                        <input type="text" name="name" id="name" class="form-control" required
                            placeholder="Masukkan nama lengkap">
                        <span class="focus-border"></span>
                    </div>
                </div>
                <div class="form-field">
                    <label for="email">
                        <i class="fas fa-envelope"></i> Email
                    </label>
                    <div class="input-wrapper">
                        <input type="email" name="email" id="email" class="form-control" required
                            placeholder="contoh@perusahaan.com">
                        <span class="focus-border"></span>
                    </div>
                </div>
                <div class="form-field">
                    <label for="password">
                        <i class="fas fa-lock"></i> Password
                    </label>
                    <div class="input-wrapper password-wrapper">
                        <input type="password" name="password" id="password" class="form-control" required
                            placeholder="Minimum 8 karakter">
                        <span class="focus-border"></span>
                        <button type="button" class="toggle-password" tabindex="-1">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                <div class="form-field">
                    <label>
                        <i class="fas fa-user-tag"></i> Role
                    </label>
                    <div class="role-options">
                        <label class="role-option">
                            <input type="radio" name="role" value="admin" class="role-radio"
                                onchange="handleRoleChange()">
                            <span class="role-card">
                                <i class="fas fa-user-shield"></i>
                                <span>Admin</span>
                            </span>
                        </label>
                        <label class="role-option">
                            <input type="radio" name="role" value="manager" class="role-radio"
                                onchange="handleRoleChange()">
                            <span class="role-card">
                                <i class="fas fa-user-tie"></i>
                                <span>Manager</span>
                            </span>
                        </label>
                        <label class="role-option">
                            <input type="radio" name="role" value="employee" class="role-radio" checked
                                onchange="handleRoleChange()">
                            <span class="role-card">
                                <i class="fas fa-user"></i>
                                <span>Employee</span>
                            </span>
                        </label>
                    </div>
                </div>
                <div class="form-field">
                    <label for="position">
                        <i class="fas fa-id-badge"></i> Jabatan
                    </label>
                    <div class="input-wrapper">
                        <input type="text" name="position" id="position" class="form-control"
                            placeholder="Masukkan jabatan">
                        <span class="focus-border"></span>
                    </div>
                </div>
                <div class="form-field">
                    <label for="base_salary">
                        <i class="fas fa-money-bill-wave"></i> Gaji Pokok
                    </label>
                    <div class="input-wrapper currency-wrapper">
                        <span class="currency-symbol">Rp</span>
                        <input type="number" name="base_salary" id="base_salary" step="1000" min="0"
                            class="form-control currency-input" placeholder="0">
                        <span class="focus-border"></span>
                    </div>
                </div>
                <div class="form-field">
                    <label for="allowance">
                        <i class="fas fa-donate"></i> Tunjangan
                    </label>
                    <div class="input-wrapper currency-wrapper">
                        <span class="currency-symbol">Rp</span>
                        <input type="number" name="allowance" id="allowance" step="1000" min="0"
                            class="form-control currency-input" placeholder="0">
                        <span class="focus-border"></span>
                    </div>
                </div>
                <div class="form-field" id="manager-field">
                    <label for="manager_id">
                        <i class="fas fa-user-tie"></i> Manager
                    </label>
                    <div class="input-wrapper select-wrapper">
                        <select name="manager_id" id="manager_id" class="form-control">
                            <option value="">-- Pilih Manager --</option>
                            <?php foreach ($managers as $manager): ?>
                                <option value="<?php echo $manager['id']; ?>"><?php echo $manager['name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                        <span class="focus-border"></span>
                        <i class="fas fa-chevron-down select-arrow"></i>
                    </div>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" name="add_employee" class="btn-primary">
                    <i class="fas fa-save"></i> Simpan
                </button>
                <button type="button" class="btn-secondary" onclick="hideAddForm()">
                    <i class="fas fa-times"></i> Batal
                </button>
            </div>
        </form>
    </div>

    <!-- Edit Employee Form Modal -->
    <div id="edit-employee-form" class="modal-form" style="display: none;">
        <div class="modal-header">
            <h3><i class="fas fa-user-edit"></i> Edit Karyawan</h3>
            <button type="button" class="close-button" onclick="hideEditForm()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form method="post" action="" class="modal-content">
            <input type="hidden" name="id" id="edit_id">
            <div class="form-grid">
                <div class="form-field">
                    <label for="edit_name">
                        <i class="fas fa-user"></i> Nama
                    </label>
                    <div class="input-wrapper">
                        <input type="text" name="name" id="edit_name" class="form-control" required>
                        <span class="focus-border"></span>
                    </div>
                </div>
                <div class="form-field">
                    <label for="edit_email">
                        <i class="fas fa-envelope"></i> Email
                    </label>
                    <div class="input-wrapper">
                        <input type="email" name="email" id="edit_email" class="form-control" required>
                        <span class="focus-border"></span>
                    </div>
                </div>
                <div class="form-field">
                    <label for="edit_password">
                        <i class="fas fa-lock"></i> Password <small>(kosongkan jika tidak ingin mengubah)</small>
                    </label>
                    <div class="input-wrapper password-wrapper">
                        <input type="password" name="password" id="edit_password" class="form-control">
                        <span class="focus-border"></span>
                        <button type="button" class="toggle-password" tabindex="-1">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                <div class="form-field">
                    <label for="edit_role">
                        <i class="fas fa-user-tag"></i> Role
                    </label>
                    <div class="input-wrapper select-wrapper">
                        <select name="role" id="edit_role" class="form-control" required
                            onchange="handleEditRoleChange()">
                            <option value="admin">Admin</option>
                            <option value="employee">Employee</option>
                            <option value="manager">Manager</option>
                        </select>
                        <span class="focus-border"></span>
                        <i class="fas fa-chevron-down select-arrow"></i>
                    </div>
                </div>
                <div class="form-field">
                    <label for="edit_position">
                        <i class="fas fa-id-badge"></i> Jabatan
                    </label>
                    <div class="input-wrapper">
                        <input type="text" name="position" id="edit_position" class="form-control">
                        <span class="focus-border"></span>
                    </div>
                </div>
                <div class="form-field">
                    <label for="edit_base_salary">
                        <i class="fas fa-money-bill-wave"></i> Gaji Pokok
                    </label>
                    <div class="input-wrapper currency-wrapper">
                        <span class="currency-symbol">Rp</span>
                        <input type="number" name="base_salary" id="edit_base_salary" step="1000" min="0"
                            class="form-control currency-input">
                        <span class="focus-border"></span>
                    </div>
                </div>
                <div class="form-field">
                    <label for="edit_allowance">
                        <i class="fas fa-donate"></i> Tunjangan
                    </label>
                    <div class="input-wrapper currency-wrapper">
                        <span class="currency-symbol">Rp</span>
                        <input type="number" name="allowance" id="edit_allowance" step="1000" min="0"
                            class="form-control currency-input">
                        <span class="focus-border"></span>
                    </div>
                </div>
                <div class="form-field" id="edit-manager-field">
                    <label for="edit_manager_id">
                        <i class="fas fa-user-tie"></i> Manager
                    </label>
                    <div class="input-wrapper select-wrapper">
                        <select name="manager_id" id="edit_manager_id" class="form-control">
                            <option value="">-- Pilih Manager --</option>
                            <?php foreach ($managers as $manager): ?>
                                <option value="<?php echo $manager['id']; ?>"><?php echo $manager['name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                        <span class="focus-border"></span>
                        <i class="fas fa-chevron-down select-arrow"></i>
                    </div>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" name="edit_employee" class="btn-primary">
                    <i class="fas fa-save"></i> Update
                </button>
                <button type="button" class="btn-secondary" onclick="hideEditForm()">
                    <i class="fas fa-times"></i> Batal
                </button>
            </div>
        </form>
    </div>

    <!-- Employee List -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-list"></i> Daftar Karyawan</h3>
            <div class="filter-container">
                <input type="text" id="searchInput" class="search-input" placeholder="Cari karyawan..."
                    onkeyup="filterTable()">
                <select id="roleFilter" class="filter-select" onchange="filterTable()">
                    <option value="">Semua Role</option>
                    <option value="admin">Admin</option>
                    <option value="manager">Manager</option>
                    <option value="employee">Employee</option>
                </select>
            </div>
        </div>

        <div class="card-body">
            <?php if (count($employees) > 0): ?>
                <div class="table-responsive">
                    <table class="data-table" id="employeeTable">
                        <thead>
                            <tr>
                                <th>Nama</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Jabatan</th>
                                <th>Gaji Pokok</th>
                                <th>Tunjangan</th>
                                <th>Manager</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($employees as $employee): ?>
                                <tr data-role="<?php echo $employee['role']; ?>">
                                    <td>
                                        <div class="employee-name">
                                            <?php if ($employee['role'] === 'admin'): ?>
                                                <i class="fas fa-user-shield role-icon admin"></i>
                                            <?php elseif ($employee['role'] === 'manager'): ?>
                                                <i class="fas fa-user-tie role-icon manager"></i>
                                            <?php else: ?>
                                                <i class="fas fa-user role-icon employee"></i>
                                            <?php endif; ?>
                                            <?php echo $employee['name']; ?>
                                        </div>
                                    </td>
                                    <td><?php echo $employee['email']; ?></td>
                                    <td>
                                        <span class="role-badge <?php echo $employee['role']; ?>">
                                            <?php echo ucfirst($employee['role']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo $employee['position'] ?: '-'; ?></td>
                                    <td class="money"><?php echo format_money($employee['base_salary']); ?></td>
                                    <td class="money"><?php echo format_money($employee['allowance']); ?></td>
                                    <td><?php echo $employee['manager_name'] ?: '-'; ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <button type="button" class="btn-icon btn-edit" onclick="showEditForm(<?php
                                            echo htmlspecialchars(json_encode($employee), ENT_QUOTES, 'UTF-8');
                                            ?>)" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </button>

                                            <form method="post" action="" style="display: inline;">
                                                <input type="hidden" name="id" value="<?php echo $employee['id']; ?>">
                                                <button type="submit" name="delete_employee" class="btn-icon btn-delete"
                                                    onclick="return confirm('Apakah Anda yakin ingin menghapus karyawan ini?')"
                                                    title="Delete">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-users empty-icon"></i>
                    <p>Belum ada data karyawan.</p>
                    <button class="btn-primary" onclick="showAddForm()">Tambah Karyawan Pertama</button>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
    .main-container {
        max-width: 1280px;
        margin: 0 auto;
        padding: 24px;
    }

    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 24px;
    }

    .header-content h2 {
        margin: 0;
        font-weight: 500;
        color: #333;
        display: flex;
        align-items: center;
        font-size: 24px;
    }

    .header-content h2 i {
        margin-right: 12px;
        color: #1976d2;
    }

    .header-content p {
        margin: 6px 0 0 0;
        color: #666;
        font-size: 14px;
    }

    .add-button {
        background-color: #1976d2;
        color: white;
        border: none;
        border-radius: 6px;
        padding: 10px 16px;
        font-size: 14px;
        font-weight: 500;
        cursor: pointer;
        display: flex;
        align-items: center;
        transition: background-color 0.3s;
    }

    .add-button i {
        margin-right: 8px;
    }

    .add-button:hover {
        background-color: #1565c0;
    }

    .card {
        background-color: white;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        overflow: hidden;
        margin-bottom: 30px;
    }

    .card-header {
        padding: 16px 20px;
        border-bottom: 1px solid #f0f0f0;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .card-header h3 {
        margin: 0;
        font-weight: 500;
        font-size: 18px;
        color: #333;
        display: flex;
        align-items: center;
    }

    .card-header h3 i {
        margin-right: 10px;
        color: #666;
    }

    .card-body {
        padding: 0;
    }

    .table-responsive {
        overflow-x: auto;
    }

    .data-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
    }

    .data-table th {
        background-color: #f9fafb;
        color: #666;
        font-weight: 500;
        text-align: left;
        padding: 14px 20px;
        border-bottom: 1px solid #eee;
        white-space: nowrap;
    }

    .data-table td {
        padding: 14px 20px;
        border-bottom: 1px solid #eee;
        color: #333;
        vertical-align: middle;
    }

    .data-table tr:last-child td {
        border-bottom: none;
    }

    .data-table tr:hover td {
        background-color: #f5f9ff;
    }

    .employee-name {
        display: flex;
        align-items: center;
    }

    .role-icon {
        margin-right: 10px;
        width: 30px;
        height: 30px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 14px;
    }

    .role-icon.admin {
        background-color: rgba(94, 53, 177, 0.1);
        color: #5e35b1;
    }

    .role-icon.manager {
        background-color: rgba(33, 150, 243, 0.1);
        color: #2196f3;
    }

    .role-icon.employee {
        background-color: rgba(67, 160, 71, 0.1);
        color: #43a047;
    }

    .role-badge {
        display: inline-block;
        padding: 5px 10px;
        border-radius: 30px;
        font-size: 12px;
        font-weight: 500;
    }

    .role-badge.admin {
        background-color: rgba(94, 53, 177, 0.1);
        color: #5e35b1;
    }

    .role-badge.manager {
        background-color: rgba(33, 150, 243, 0.1);
        color: #2196f3;
    }

    .role-badge.employee {
        background-color: rgba(67, 160, 71, 0.1);
        color: #43a047;
    }

    .money {
        font-family: 'Roboto Mono', monospace;
        text-align: right;
    }

    .action-buttons {
        display: flex;
        gap: 8px;
    }

    .btn-icon {
        width: 32px;
        height: 32px;
        border-radius: 6px;
        border: none;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.2s;
    }

    .btn-edit {
        background-color: rgba(33, 150, 243, 0.1);
        color: #2196f3;
    }

    .btn-edit:hover {
        background-color: rgba(33, 150, 243, 0.2);
    }

    .btn-delete {
        background-color: rgba(244, 67, 54, 0.1);
        color: #f44336;
    }

    .btn-delete:hover {
        background-color: rgba(244, 67, 54, 0.2);
    }

    .filter-container {
        display: flex;
        gap: 10px;
    }

    .search-input {
        padding: 8px 12px;
        border: 1px solid #e0e0e0;
        border-radius: 6px;
        font-size: 14px;
        width: 200px;
    }

    .filter-select {
        padding: 8px 12px;
        border: 1px solid #e0e0e0;
        border-radius: 6px;
        font-size: 14px;
    }

    .empty-state {
        padding: 60px 20px;
        text-align: center;
    }

    .empty-icon {
        font-size: 48px;
        color: #ddd;
        margin-bottom: 16px;
    }

    .empty-state p {
        color: #888;
        margin-bottom: 20px;
    }

    /* Modal Styles */
    .modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: rgba(0, 0, 0, 0.5);
        z-index: 1000;
        backdrop-filter: blur(3px);
        transition: opacity 0.2s ease;
    }

    .modal-form {
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        background-color: white;
        border-radius: 12px;
        max-width: 800px;
        width: 90%;
        z-index: 1001;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        max-height: 90vh;
        display: flex;
        flex-direction: column;
        animation: modalFadeIn 0.3s ease;
        overflow: hidden;
    }

    @keyframes modalFadeIn {
        from {
            opacity: 0;
            transform: translate(-50%, -48%);
        }

        to {
            opacity: 1;
            transform: translate(-50%, -50%);
        }
    }

    .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 20px 28px;
        border-bottom: 1px solid #f0f0f0;
        background-color: #f9fafb;
    }

    .modal-header h3 {
        margin: 0;
        font-weight: 500;
        font-size: 18px;
        display: flex;
        align-items: center;
        color: #333;
    }

    .modal-header h3 i {
        margin-right: 12px;
        color: #1976d2;
        font-size: 20px;
    }

    .close-button {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        background: #f1f3f5;
        border: none;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #666;
        cursor: pointer;
        transition: all 0.2s;
    }

    .close-button:hover {
        background-color: #e2e6ea;
        color: #333;
    }

    .modal-content {
        padding: 28px;
        overflow-y: auto;
    }

    .form-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 24px;
    }

    .form-field {
        margin-bottom: 4px;
    }

    .form-field label {
        display: block;
        margin-bottom: 10px;
        font-weight: 500;
        color: #444;
        font-size: 14px;
        display: flex;
        align-items: center;
    }

    .form-field label i {
        margin-right: 8px;
        color: #666;
        width: 16px;
    }

    .input-wrapper {
        position: relative;
        margin-bottom: 16px;
    }

    .form-control {
        width: 100%;
        padding: 14px 16px;
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        font-size: 14px;
        transition: all 0.3s;
        background-color: #f9fafb;
    }

    .form-control:focus {
        outline: none;
        border-color: #1976d2;
        background-color: #fff;
    }

    .focus-border {
        position: absolute;
        bottom: 0;
        left: 50%;
        width: 0;
        height: 2px;
        background-color: #1976d2;
        transition: all 0.3s;
    }

    .form-control:focus~.focus-border {
        width: 100%;
        left: 0;
    }

    /* Custom select */
    .select-wrapper {
        position: relative;
    }

    .select-wrapper select {
        appearance: none;
        -webkit-appearance: none;
        padding-right: 30px;
    }

    .select-arrow {
        position: absolute;
        right: 12px;
        top: 50%;
        transform: translateY(-50%);
        color: #666;
        pointer-events: none;
    }

    /* Password toggle */
    .password-wrapper {
        position: relative;
    }

    .toggle-password {
        position: absolute;
        right: 10px;
        top: 50%;
        transform: translateY(-50%);
        background: none;
        border: none;
        color: #666;
        cursor: pointer;
        padding: 5px;
    }

    /* Currency input */
    .currency-wrapper {
        position: relative;
    }

    .currency-symbol {
        position: absolute;
        left: 16px;
        top: 50%;
        transform: translateY(-50%);
        color: #666;
    }

    .currency-input {
        padding-left: 40px;
        text-align: right;
        font-family: 'Roboto Mono', monospace;
    }

    /* Role selection cards */
    .role-options {
        display: flex;
        gap: 12px;
        margin-bottom: 16px;
    }

    .role-option {
        flex: 1;
        cursor: pointer;
    }

    .role-radio {
        position: absolute;
        opacity: 0;
        width: 0;
        height: 0;
    }

    .role-card {
        display: flex;
        flex-direction: column;
        align-items: center;
        padding: 16px 10px;
        background-color: #f9fafb;
        border: 2px solid #e0e0e0;
        border-radius: 8px;
        transition: all 0.2s;
    }

    .role-card i {
        font-size: 20px;
        margin-bottom: 8px;
        color: #666;
    }

    .role-radio:checked+.role-card {
        border-color: #1976d2;
        background-color: rgba(25, 118, 210, 0.05);
    }

    .role-radio:checked+.role-card i {
        color: #1976d2;
    }

    .role-radio:focus+.role-card {
        box-shadow: 0 0 0 2px rgba(25, 118, 210, 0.3);
    }

    .form-actions {
        display: flex;
        justify-content: flex-end;
        gap: 16px;
        margin-top: 28px;
        padding-top: 24px;
        border-top: 1px solid #f0f0f0;
    }

    .btn-primary,
    .btn-secondary {
        padding: 14px 24px;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 500;
        cursor: pointer;
        display: flex;
        align-items: center;
        transition: all 0.2s;
        border: none;
    }

    .btn-primary {
        background-color: #1976d2;
        color: white;
    }

    .btn-primary:hover {
        background-color: #1565c0;
        transform: translateY(-1px);
    }

    .btn-secondary {
        background-color: #f5f5f5;
        color: #333;
    }

    .btn-secondary:hover {
        background-color: #e0e0e0;
    }

    .btn-primary:active,
    .btn-secondary:active {
        transform: translateY(1px);
    }

    .btn-primary i,
    .btn-secondary i {
        margin-right: 10px;
    }

    @media (max-width: 768px) {
        .form-grid {
            grid-template-columns: 1fr;
            gap: 16px;
        }

        .role-options {
            flex-direction: column;
            gap: 8px;
        }
    }
</style>

<script>
    function showAddForm() {
        document.getElementById('add-employee-form').style.display = 'flex';
        document.getElementById('modal-overlay').style.display = 'block';
        document.body.style.overflow = 'hidden';

        // Initialize manager field visibility
        handleRoleChange();
    }

    function hideAddForm() {
        document.getElementById('add-employee-form').style.display = 'none';
        document.getElementById('modal-overlay').style.display = 'none';
        document.body.style.overflow = 'auto';
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

        document.getElementById('edit-employee-form').style.display = 'flex';
        document.getElementById('modal-overlay').style.display = 'block';
        document.body.style.overflow = 'hidden';

        // Initialize manager field visibility for edit form
        handleEditRoleChange();
    }

    function hideEditForm() {
        document.getElementById('edit-employee-form').style.display = 'none';
        document.getElementById('modal-overlay').style.display = 'none';
        document.body.style.overflow = 'auto';
    }

    function closeAllModals() {
        hideAddForm();
        hideEditForm();
    }

    // Function to handle role change in Add form
    function handleRoleChange() {
        const roleRadios = document.querySelectorAll('input[name="role"].role-radio');
        const managerField = document.getElementById('manager-field');

        let selectedRole = '';
        roleRadios.forEach(radio => {
            if (radio.checked) {
                selectedRole = radio.value;
            }
        });

        // Hide manager field if role is admin or manager
        if (selectedRole === 'admin' || selectedRole === 'manager') {
            managerField.style.display = 'none';
            document.getElementById('manager_id').value = ''; // Clear selection
        } else {
            managerField.style.display = 'block';
        }
    }

    // Function to handle role change in Edit form
    function handleEditRoleChange() {
        const roleSelect = document.getElementById('edit_role');
        const managerField = document.getElementById('edit-manager-field');

        // Hide manager field if role is admin or manager
        if (roleSelect.value === 'admin' || roleSelect.value === 'manager') {
            managerField.style.display = 'none';
            document.getElementById('edit_manager_id').value = ''; // Clear selection
        } else {
            managerField.style.display = 'block';
        }
    }

    // Don't close modal when clicking inside it
    document.querySelectorAll('.modal-form').forEach(form => {
        form.addEventListener('click', function (e) {
            e.stopPropagation();
        });
    });

    function filterTable() {
        const searchInput = document.getElementById('searchInput').value.toLowerCase();
        const roleFilter = document.getElementById('roleFilter').value.toLowerCase();
        const table = document.getElementById('employeeTable');
        const rows = table.getElementsByTagName('tr');

        for (let i = 1; i < rows.length; i++) {
            const row = rows[i];
            const name = row.cells[0].textContent.toLowerCase();
            const email = row.cells[1].textContent.toLowerCase();
            const role = row.getAttribute('data-role').toLowerCase();

            const nameMatch = name.includes(searchInput);
            const emailMatch = email.includes(searchInput);
            const roleMatch = !roleFilter || role === roleFilter;

            if ((nameMatch || emailMatch) && roleMatch) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        // Password visibility toggle
        const togglePasswordButtons = document.querySelectorAll('.toggle-password');
        togglePasswordButtons.forEach(button => {
            button.addEventListener('click', function () {
                const input = this.previousElementSibling.previousElementSibling;
                const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
                input.setAttribute('type', type);
                this.innerHTML = type === 'password' ? '<i class="fas fa-eye"></i>' : '<i class="fas fa-eye-slash"></i>';
            });
        });

        // Format currency inputs
        const currencyInputs = document.querySelectorAll('.currency-input');
        currencyInputs.forEach(input => {
            // Add a hidden input to store the actual numeric value
            const hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = input.name;
            input.name = input.name + '_formatted';
            input.parentNode.appendChild(hiddenInput);

            // Initial setup - just copy the raw value to hidden input
            const initialValue = input.value.trim();
            hiddenInput.value = initialValue ? parseInt(initialValue.replace(/\D/g, ''), 10) : '';

            // Function to update the hidden input value
            const updateHiddenValue = (inputField) => {
                // Get value without any formatting
                const cleanValue = inputField.value.replace(/\D/g, '');
                const numericValue = cleanValue === '' ? 0 : parseInt(cleanValue, 10);

                // Update the hidden field with the numeric value
                hiddenInput.value = numericValue;

                // Format the display value
                if (numericValue > 0) {
                    inputField.value = numericValue.toLocaleString('id-ID');
                } else if (numericValue === 0 && inputField.value !== '') {
                    inputField.value = '0';
                }
            };

            // Update on change
            input.addEventListener('input', function () {
                // Allow only digits and separators during typing
                this.value = this.value.replace(/[^\d.,]/g, '');
            });

            // Format on blur
            input.addEventListener('blur', function () {
                updateHiddenValue(this);
            });

            // Clean on focus
            input.addEventListener('focus', function () {
                // Remove formatting for editing
                const numericValue = parseInt(hiddenInput.value, 10);
                this.value = numericValue > 0 ? numericValue.toString() : '';
            });
        });

        // Prevent form submission when pressing Enter in the modal
        const modalForms = document.querySelectorAll('.modal-form form');
        modalForms.forEach(form => {
            form.addEventListener('keypress', function (e) {
                if (e.key === 'Enter' && e.target.tagName !== 'TEXTAREA') {
                    e.preventDefault();
                    // Find the submit button and click it
                    const submitButton = this.querySelector('button[type="submit"]');
                    if (submitButton) {
                        submitButton.click();
                    }
                }
            });
        });
    });
</script>

<?php require_once '../includes/footer.php'; ?>