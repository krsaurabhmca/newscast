<?php
$page_title = "Edit Team Member";
include 'includes/header.php';

if (!is_admin()) {
    redirect('admin/dashboard.php', 'Access denied.', 'danger');
}

// Get ID from URL or POST
$id = (int)($_REQUEST['id'] ?? 0);

if (!$id) {
    redirect('admin/users.php', 'Invalid user ID.', 'danger');
}

$errors = [];
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$id]);
$user = $stmt->fetch();

if (!$user) {
    redirect('admin/users.php', 'User not found.', 'danger');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = clean($_POST['username'] ?? '');
    $email = clean($_POST['email'] ?? '');
    $role = clean($_POST['role'] ?? '');
    $profile_image = $user['profile_image'];

    if (empty($username)) $errors['username'] = "Username is required.";
    if (empty($email)) $errors['email'] = "Email is required.";
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['email'] = "Invalid email format.";

    $check = $pdo->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?");
    $check->execute([$username, $email, $id]);
    if ($check->fetch()) {
        $errors['general'] = "Username or email already taken.";
    }

    // Handle Image Upload
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === 0) {
        $img_name = $_FILES['profile_image']['name'];
        $tmp_name = $_FILES['profile_image']['tmp_name'];
        $img_ext = strtolower(pathinfo($img_name, PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp', 'svg'];

        if (in_array($img_ext, $allowed)) {
            $new_name = "user_" . $id . "_" . time() . "." . $img_ext;
            $upload_path = "../assets/images/" . $new_name;

            if (move_uploaded_file($tmp_name, $upload_path)) {
                if ($profile_image && $profile_image != 'default-avatar.svg' && file_exists("../assets/images/" . $profile_image)) {
                    unlink("../assets/images/" . $profile_image);
                }
                $profile_image = $new_name;
            } else {
                $errors['image'] = "Failed to upload image.";
            }
        } else {
            $errors['image'] = "Invalid file type. Allowed: jpg, png, webp, svg.";
        }
    }

    if (empty($errors)) {
        try {
            $update = $pdo->prepare("UPDATE users SET username = ?, email = ?, role = ?, profile_image = ? WHERE id = ?");
            $update->execute([$username, $email, $role, $profile_image, $id]);
            redirect('admin/users.php', 'User updated successfully!', 'success');
        } catch (PDOException $e) {
            $errors['general'] = "Database error: " . $e->getMessage();
        }
    }
}
?>

<div style="max-width: 800px; margin: 0 auto;">
    <div class="panel" style="background:#fff; border-radius:16px; padding:30px; box-shadow:0 1px 4px rgba(0,0,0,.05);">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:25px;">
            <h2 style="font-size:20px; font-weight:700; color:#0f172a; margin:0;">Edit User: <?= htmlspecialchars($user['username']) ?></h2>
            <a href="users.php" class="btn" style="background:#f1f5f9; color:#475569; padding:8px 15px; font-size:13px; font-weight:700; border-radius:8px; text-decoration:none;"><i data-feather="arrow-left" style="width:14px;"></i> Back</a>
        </div>

        <?php if (isset($errors['general'])): ?>
            <div style="background:#fef2f2; border:1px solid #fecaca; padding:12px; border-radius:8px; color:#991b1b; margin-bottom:20px; font-size:14px;"><?= $errors['general'] ?></div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="id" value="<?= $id ?>">
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:20px; margin-bottom:20px;">
                <div class="form-group">
                    <label style="font-size:13px; font-weight:700; color:#475569; display:block; margin-bottom:8px;">Username</label>
                    <input type="text" name="username" class="form-control" value="<?= htmlspecialchars($user['username']) ?>" required>
                    <?php if(isset($errors['username'])): ?><small style="color:#ef4444;"><?= $errors['username'] ?></small><?php endif; ?>
                </div>
                <div class="form-group">
                    <label style="font-size:13px; font-weight:700; color:#475569; display:block; margin-bottom:8px;">Email Address</label>
                    <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" required>
                    <?php if(isset($errors['email'])): ?><small style="color:#ef4444;"><?= $errors['email'] ?></small><?php endif; ?>
                </div>
            </div>

            <div class="form-group" style="margin-bottom:20px;">
                <label style="font-size:13px; font-weight:700; color:#475569; display:block; margin-bottom:8px;">User Role</label>
                <select name="role" class="form-control">
                    <option value="editor" <?= $user['role']=='editor'?'selected':'' ?>>Editor</option>
                    <option value="admin" <?= $user['role']=='admin'?'selected':'' ?>>Administrator</option>
                </select>
            </div>

            <div class="form-group" style="margin-bottom:25px;">
                <label style="font-size:13px; font-weight:700; color:#475569; display:block; margin-bottom:8px;">Profile Photo</label>
                <div style="display:flex; align-items:center; gap:20px; background:#f8fafc; padding:15px; border-radius:12px; border:1px solid #e2e8f0;">
                    <img src="<?= get_profile_image($user['profile_image']) ?>" style="width:70px; height:70px; border-radius:12px; object-fit:cover; border:2px solid #fff; box-shadow:0 4px 6px -1px rgba(0,0,0,0.1);">
                    <div style="flex:1;">
                        <input type="file" name="profile_image" accept="image/*" style="font-size:13px;">
                        <div style="font-size:11px; color:#94a3b8; margin-top:5px;">Upload PNG, JPG or WEBP (Standard square recommended)</div>
                    </div>
                </div>
                <?php if(isset($errors['image'])): ?><small style="color:#ef4444;"><?= $errors['image'] ?></small><?php endif; ?>
            </div>

            <div style="border-top:1px solid #f1f5f9; padding-top:20px;">
                <button type="submit" class="btn btn-primary" style="padding:12px 25px; font-weight:700;">Update Team Member</button>
            </div>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
