<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/email_helper.php';

if (is_logged_in()) {
    header("Location: admin/dashboard.php");
    exit();
}

$step = 'request';
$error = '';
$token = trim($_GET['token'] ?? '');

// STEP 1: Request reset
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_reset'])) {
    $email = clean($_POST['email'] ?? '');
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    }
    else {
        $step = 'sent';
        $stmt = $pdo->prepare("SELECT id,username FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        if ($user) {
            try {
                $pdo->prepare("DELETE FROM password_resets WHERE email = ?")->execute([$email]);
                $token_val = bin2hex(random_bytes(32));
                $expiry = date('Y-m-d H:i:s', time() + 3600);
                $pdo->prepare("INSERT INTO password_resets (email,token,expires_at) VALUES(?,?,?)")->execute([$email, $token_val, $expiry]);
                $reset_link = BASE_URL . 'forgot_password.php?token=' . $token_val;
                send_forgot_password_email($email, $reset_link, $user['username']);
            }
            catch (Exception $e) {
            }
        }
    }
}

// STEP 2: Token in URL
if ($token && $step === 'request') {
    try {
        $stmt = $pdo->prepare("SELECT * FROM password_resets WHERE token = ? AND expires_at > NOW()");
        $stmt->execute([$token]);
        $reset_row = $stmt->fetch();
        if ($reset_row) {
            $step = 'reset';
        }
        else {
            $error = 'This reset link is invalid or has expired. Please request a new one.';
        }
    }
    catch (Exception $e) {
        $error = 'Database error. Please try again.';
    }
}

// STEP 3: New password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['do_reset']) && $token) {
    $new_pass = $_POST['new_password'] ?? '';
    $conf_pass = $_POST['confirm_password'] ?? '';
    if (strlen($new_pass) < 6) {
        $error = 'Password must be at least 6 characters.';
        $step = 'reset';
    }
    elseif ($new_pass !== $conf_pass) {
        $error = 'Passwords do not match.';
        $step = 'reset';
    }
    else {
        try {
            $stmt = $pdo->prepare("SELECT * FROM password_resets WHERE token = ? AND expires_at > NOW()");
            $stmt->execute([$token]);
            $reset_row = $stmt->fetch();
            if ($reset_row) {
                $pdo->prepare("UPDATE users SET password = ? WHERE email = ?")->execute([password_hash($new_pass, PASSWORD_DEFAULT), $reset_row['email']]);
                $pdo->prepare("DELETE FROM password_resets WHERE email = ?")->execute([$reset_row['email']]);
                $step = 'done';
            }
            else {
                $error = 'Link expired. Please request a new one.';
                $step = 'request';
            }
        }
        catch (Exception $e) {
            $error = 'Error: ' . $e->getMessage();
            $step = 'reset';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Forgot Password | <?php echo SITE_NAME_DYNAMIC; ?></title>
<link rel="stylesheet" href="assets/css/admin.css">
<style>
:root{--primary-clr:<?php echo get_setting('theme_color', '#6366f1'); ?>;}
body{min-height:100vh;margin:0;display:flex;align-items:center;justify-content:center;background:#f1f5f9;background-image:radial-gradient(#6366f120 1.5px,transparent 1.5px);background-size:30px 30px;font-family:'Inter','Segoe UI',sans-serif;}
.card{background:#fff;border-radius:20px;padding:44px 42px;width:100%;max-width:440px;box-shadow:0 20px 50px rgba(0,0,0,.08);border:1px solid #f1f5f9;position:relative;}
.card::before{content:'';position:absolute;top:0;left:50%;transform:translateX(-50%);width:100px;height:4px;background:var(--primary-clr);border-bottom-left-radius:4px;border-bottom-right-radius:4px;}
.icon-wrap{width:64px;height:64px;border-radius:18px;display:flex;align-items:center;justify-content:center;margin:0 auto 20px;font-size:28px;}
h2{font-size:22px;font-weight:800;color:#0f172a;margin:0 0 8px;text-align:center;}
.sub{color:#64748b;font-size:14px;text-align:center;margin:0 0 28px;}
label{display:block;font-size:12px;font-weight:700;color:#475569;margin-bottom:6px;text-transform:uppercase;letter-spacing:.5px;}
input[type=email],input[type=password]{width:100%;padding:13px 16px;border:1.5px solid #e2e8f0;border-radius:10px;font-size:14px;transition:.2s;outline:none;background:#f8fafc;box-sizing:border-box;}
input:focus{border-color:var(--primary-clr);background:#fff;box-shadow:0 0 0 4px rgba(99,102,241,.1);}
.btn-main{width:100%;padding:13px;background:var(--primary-clr);color:#fff;border:none;border-radius:10px;font-size:15px;font-weight:700;cursor:pointer;margin-top:18px;transition:.2s;box-shadow:0 6px 16px rgba(99,102,241,.25);}
.btn-main:hover{filter:brightness(1.08);transform:translateY(-1px);}
.alert-err{border-radius:10px;padding:12px 16px;font-size:13px;font-weight:600;margin-bottom:20px;background:#fef2f2;color:#991b1b;border-left:4px solid #ef4444;}
.alert-ok{border-radius:10px;padding:12px 16px;font-size:13px;font-weight:600;margin-bottom:20px;background:#f0fdf4;color:#166534;border-left:4px solid #22c55e;}
.back-link{display:block;text-align:center;margin-top:22px;color:var(--primary-clr);font-size:13px;font-weight:700;text-decoration:none;}
.form-group{margin-bottom:18px;}
.pw-wrap{position:relative;}
.pw-toggle{position:absolute;right:14px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#94a3b8;font-size:16px;}
</style>
</head>
<body>
<div class="card">
<?php if ($step === 'request'): ?>
  <div class="icon-wrap" style="background:#eef2ff;color:var(--primary-clr);">&#128272;</div>
  <h2>Forgot Password?</h2>
  <p class="sub">Enter your email and we'll send you a secure reset link.</p>
  <?php if ($error): ?><div class="alert-err"><?php echo htmlspecialchars($error); ?></div><?php
    endif; ?>
  <form method="POST">
    <div class="form-group">
      <label>Email Address</label>
      <input type="email" name="email" required placeholder="yourname@newscast.com" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
    </div>
    <button type="submit" name="request_reset" class="btn-main">Send Reset Link</button>
  </form>
  <a href="login.php" class="back-link">Back to Login</a>

<?php
elseif ($step === 'sent'): ?>
  <div class="icon-wrap" style="background:#f0fdf4;color:#16a34a;">&#10003;</div>
  <h2>Check Your Email</h2>
  <p class="sub">If an account with that email exists, we've sent a password reset link. Please check your inbox and spam folder.</p>
  <div class="alert-ok">The link will expire in <strong>1 hour</strong>.</div>
  <a href="login.php" class="back-link">Back to Login</a>

<?php
elseif ($step === 'reset'): ?>
  <div class="icon-wrap" style="background:#fdf4ff;color:#9333ea;">&#128273;</div>
  <h2>Set New Password</h2>
  <p class="sub">Enter your new password below.</p>
  <?php if ($error): ?><div class="alert-err"><?php echo htmlspecialchars($error); ?></div><?php
    endif; ?>
  <form method="POST">
    <div class="form-group">
      <label>New Password</label>
      <div class="pw-wrap">
        <input type="password" name="new_password" id="np" required placeholder="Min 6 characters" style="padding-right:42px;">
        <button type="button" class="pw-toggle" onclick="var f=document.getElementById('np');f.type=f.type=='password'?'text':'password';">&#128065;</button>
      </div>
    </div>
    <div class="form-group">
      <label>Confirm Password</label>
      <input type="password" name="confirm_password" required placeholder="Repeat password">
    </div>
    <button type="submit" name="do_reset" class="btn-main">Reset Password</button>
  </form>
  <a href="forgot_password.php" class="back-link">Request new link</a>

<?php
elseif ($step === 'done'): ?>
  <div class="icon-wrap" style="background:#f0fdf4;color:#16a34a;">&#127881;</div>
  <h2>Password Reset!</h2>
  <p class="sub">Your password has been changed successfully. You can now log in with your new password.</p>
  <a href="login.php" class="btn-main" style="display:block;text-align:center;text-decoration:none;line-height:normal;padding:13px;">Go to Login</a>
<?php
endif; ?>
</div>
</body>
</html>
