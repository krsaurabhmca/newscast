<?php
$page_title = "Change Password";
include 'includes/header.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error = "All fields are required.";
    } elseif ($new_password !== $confirm_password) {
        $error = "New passwords do not match.";
    } elseif (strlen($new_password) < 6) {
        $error = "New password must be at least 6 characters long.";
    } else {
        // Verify current password
        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();

        if ($user && password_verify($current_password, $user['password'])) {
            // Update password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $update->execute([$hashed_password, $_SESSION['user_id']]);
            $success = "Password updated successfully!";
        } else {
            $error = "Current password is incorrect.";
        }
    }
}
?>

<div class="stat-card" style="max-width: 500px; margin: 0 auto;">
    <div style="margin-bottom: 25px; text-align: center;">
        <div style="background: #f1f5f9; width: 60px; height: 60px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 15px;">
            <i data-feather="lock" style="width: 28px; color: var(--primary);"></i>
        </div>
        <h3 style="font-size: 20px; font-weight: 800; color: #1e293b; margin: 0;">Update Password</h3>
        <p style="color: #64748b; font-size: 14px; margin-top: 5px;">Keep your account secure by using a strong password.</p>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>

    <form method="POST" style="margin-top: 20px;">
        <div class="form-group">
            <label>Current Password</label>
            <input type="password" name="current_password" class="form-control" required placeholder="Type your current password">
        </div>

        <div class="form-group">
            <label>New Password</label>
            <input type="password" name="new_password" class="form-control" required placeholder="Create a new strong password">
        </div>

        <div class="form-group">
            <label>Confirm New Password</label>
            <input type="password" name="confirm_password" class="form-control" required placeholder="Repeat the new password">
        </div>

        <div style="display: flex; gap: 15px; margin-top: 30px;">
            <button type="submit" class="btn btn-primary" style="flex: 1; justify-content: center;">Update Password</button>
            <a href="dashboard.php" class="btn" style="background: #f1f5f9; border-color: #e2e8f0; color: #475569;">Cancel</a>
        </div>
    </form>
</div>

<?php include 'includes/footer.php'; ?>
