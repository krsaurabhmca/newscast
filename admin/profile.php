<?php
$page_title = "Edit Profile";
include 'includes/header.php';

$errors = [];
$user_id = $_SESSION['user_id'];

// Fetch current user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = clean($_POST['username']);
    $email = clean($_POST['email']);
    $profile_image = $user['profile_image'];

    // Validation
    if (empty($username)) $errors['username'] = "Username is required.";
    if (empty($email)) $errors['email'] = "Email is required.";
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['email'] = "Invalid email format.";

    // Check if username/email already exists for OTHER users
    $check = $pdo->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?");
    $check->execute([$username, $email, $user_id]);
    if ($check->fetch()) {
        $errors['general'] = "Username or email already taken by another member.";
    }

    // Handle Profile Image Upload
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === 0) {
        $img_name = $_FILES['profile_image']['name'];
        $tmp_name = $_FILES['profile_image']['tmp_name'];
        $img_ext = strtolower(pathinfo($img_name, PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp', 'svg'];

        if (in_array($img_ext, $allowed)) {
            $new_name = "user_" . $user_id . "_" . time() . "." . $img_ext;
            $upload_path = "../assets/images/" . $new_name;

            if (move_uploaded_file($tmp_name, $upload_path)) {
                // Delete old image if not default
                if ($profile_image && file_exists("../assets/images/" . $profile_image)) {
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
            $update = $pdo->prepare("UPDATE users SET username = ?, email = ?, profile_image = ? WHERE id = ?");
            $update->execute([$username, $email, $profile_image, $user_id]);
            
            // Update session
            $_SESSION['username'] = $username;
            $_SESSION['profile_image'] = $profile_image;
            
            redirect('admin/profile.php', 'Profile updated successfully!', 'success');
        } catch (PDOException $e) {
            $errors['general'] = "Database error: " . $e->getMessage();
        }
    }
}
?>

<div style="max-width: 800px; margin: 0 auto;">
    <div class="stat-card" style="padding: 40px;">
        <div style="display: flex; align-items: center; gap: 25px; margin-bottom: 40px; border-bottom: 1px solid #f1f5f9; padding-bottom: 30px;">
            <div style="position: relative;">
                <img src="<?php echo get_profile_image($user['profile_image']); ?>" 
                     style="width: 100px; height: 100px; border-radius: 20px; object-fit: cover; border: 4px solid #f1f5f9; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1);">
                <div style="position: absolute; bottom: -5px; right: -5px; background: var(--primary); color: white; width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; border: 3px solid #fff;">
                    <i data-feather="camera" style="width: 14px;"></i>
                </div>
            </div>
            <div>
                <h2 style="font-size: 24px; font-weight: 800; color: #0f172a; margin: 0;"><?php echo htmlspecialchars($user['username']); ?></h2>
                <p style="color: #64748b; margin: 5px 0 0; font-weight: 600; text-transform: uppercase; font-size: 12px; letter-spacing: 1px;"><?php echo ucfirst($user['role']); ?> Settings</p>
            </div>
        </div>

        <?php if (isset($errors['general'])): ?>
            <div class="alert alert-danger"><?php echo $errors['general']; ?></div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 25px;">
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" class="form-control <?php echo isset($errors['username']) ? 'is-error' : ''; ?>" 
                           value="<?php echo htmlspecialchars($user['username']); ?>" required>
                    <?php if(isset($errors['username'])): ?><small style="color: #ef4444;"><?php echo $errors['username']; ?></small><?php endif; ?>
                </div>

                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" class="form-control <?php echo isset($errors['email']) ? 'is-error' : ''; ?>" 
                           value="<?php echo htmlspecialchars($user['email']); ?>" required>
                    <?php if(isset($errors['email'])): ?><small style="color: #ef4444;"><?php echo $errors['email']; ?></small><?php endif; ?>
                </div>
            </div>

            <div class="form-group">
                <label>Update Profile Photo</label>
                <div style="background: #f8fafc; border: 2px dashed #e2e8f0; border-radius: 12px; padding: 20px; text-align: center;">
                    <input type="file" name="profile_image" id="profile_img_input" style="display: none;" accept="image/*">
                    <label for="profile_img_input" style="cursor: pointer; display: block;">
                        <i data-feather="upload-cloud" style="width: 30px; color: #94a3b8; margin-bottom: 10px;"></i>
                        <div style="font-size: 14px; font-weight: 600; color: #475569;">Click to upload or drag and drop</div>
                        <div style="font-size: 12px; color: #94a3b8; margin-top: 5px;">PNG, JPG, WEBP (Max 2MB)</div>
                    </label>
                    <div id="preview-name" style="margin-top: 10px; font-size: 12px; font-weight: 700; color: var(--primary);"></div>
                </div>
                <?php if(isset($errors['image'])): ?><small style="color: #ef4444;"><?php echo $errors['image']; ?></small><?php endif; ?>
            </div>

            <div style="display: flex; gap: 15px; margin-top: 20px;">
                <button type="submit" class="btn btn-primary" style="padding: 12px 25px;">Save Changes</button>
                <a href="dashboard.php" class="btn" style="background: #f1f5f9; color: #475569;">Cancel</a>
            </div>
        </form>
    </div>
</div>

<script>
    document.getElementById('profile_img_input').addEventListener('change', function(e) {
        if (e.target.files.length > 0) {
            document.getElementById('preview-name').innerText = "Selected: " + e.target.files[0].name;
        }
    });
</script>

<?php include 'includes/footer.php'; ?>
