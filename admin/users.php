<?php
$page_title = "Manage Users";
include 'includes/header.php';

if (!is_admin()) {
    redirect('admin/dashboard.php', 'Access denied.', 'danger');
}

// Handle Add User
if (isset($_POST['add_user'])) {
    $username = clean($_POST['username']);
    $email = clean($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = $_POST['role'];

    if (empty($username) || empty($email) || empty($_POST['password'])) {
        $_SESSION['flash_msg'] = "All fields are required.";
        $_SESSION['flash_type'] = "danger";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
            $stmt->execute([$username, $email, $password, $role]);
            redirect('admin/users.php', 'User added successfully!');
        } catch (PDOException $e) {
            $_SESSION['flash_msg'] = "Error: " . $e->getMessage();
            $_SESSION['flash_type'] = "danger";
        }
    }
}

$users = $pdo->query("SELECT * FROM users ORDER BY created_at DESC")->fetchAll();
?>

<div style="display: grid; grid-template-columns: 350px 1fr; gap: 30px;">
    <div style="background: white; padding: 25px; border-radius: 12px; height: fit-content; box-shadow: var(--shadow);">
        <h3 style="margin-bottom: 20px;">Add New User</h3>
        <form action="" method="POST">
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Role</label>
                <select name="role" class="form-control">
                    <option value="editor">Editor</option>
                    <option value="admin">Admin</option>
                </select>
            </div>
            <button type="submit" name="add_user" class="btn btn-primary" style="width: 100%; justify-content: center;">
                Save User
            </button>
        </form>
    </div>

    <div style="background: white; padding: 25px; border-radius: 12px; box-shadow: var(--shadow);">
        <h3 style="margin-bottom: 20px;">All Users</h3>
        <table class="content-table">
            <thead>
                <tr>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Joined</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                <tr>
                    <td><strong><?php echo $user['username']; ?></strong></td>
                    <td><?php echo $user['email']; ?></td>
                    <td><span class="badge" style="background: <?php echo $user['role'] == 'admin' ? '#fee2e2' : '#e0f2fe'; ?>; color: <?php echo $user['role'] == 'admin' ? '#991b1b' : '#0369a1'; ?>;"><?php echo $user['role']; ?></span></td>
                    <td><?php echo format_date($user['created_at']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
