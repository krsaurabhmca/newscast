<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

if (is_logged_in()) {
    header("Location: admin/dashboard.php");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = clean($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $error = "Please fill in all fields.";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['profile_image'] = $user['profile_image'];
            
            redirect('admin/dashboard.php', 'Welcome back, ' . $user['username'] . '!');
        } else {
            $error = "Invalid email or password.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login | <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/admin.css">
    <style>
        :root {
            --primary-clr: <?php echo get_setting('theme_color', '#6366f1'); ?>;
        }
        body {
            height: 100vh;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f8fafc;
            background-image: radial-gradient(#6366f120 1.5px, transparent 1.5px);
            background-size: 30px 30px;
            font-family: 'Inter', sans-serif;
        }
        .login-card {
            background: white;
            padding: 40px;
            border-radius: 24px;
            width: 100%;
            max-width: 420px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.08);
            border: 1px solid #f1f5f9;
            position: relative;
        }
        .login-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 100px;
            height: 4px;
            background: var(--primary-clr);
            border-bottom-left-radius: 4px;
            border-bottom-right-radius: 4px;
        }
        .portal-logo {
            text-align: center;
            margin-bottom: 30px;
        }
        .portal-logo div {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: var(--primary-clr);
            color: white;
            padding: 10px 20px;
            border-radius: 12px;
            font-weight: 800;
            font-size: 20px;
            margin-bottom: 15px;
            box-shadow: 0 10px 15px -3px rgba(99, 102, 241, 0.3);
        }
        .form-title {
            text-align: center;
            margin-bottom: 30px;
        }
        .form-title h2 {
            font-size: 24px;
            color: #1e293b;
            margin: 0 0 8px 0;
            font-weight: 800;
        }
        .form-title p {
            color: #64748b;
            font-size: 14px;
            margin: 0;
            font-weight: 500;
        }
        .input-group {
            margin-bottom: 20px;
        }
        .input-group label {
            display: block;
            font-size: 13px;
            font-weight: 700;
            color: #475569;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .input-group input {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #f1f5f9;
            border-radius: 12px;
            font-size: 15px;
            transition: 0.2s;
            outline: none;
            background: #f8fafc;
        }
        .input-group input:focus {
            border-color: var(--primary-clr);
            background: #fff;
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
        }
        .login-btn {
            width: 100%;
            padding: 14px;
            background: var(--primary-clr);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: 0.3s;
            margin-top: 10px;
            box-shadow: 0 10px 15px -3px rgba(99, 102, 241, 0.2);
        }
        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 20px 25px -5px rgba(99, 102, 241, 0.3);
            filter: brightness(1.1);
        }
        .footer-links {
            margin-top: 30px;
            text-align: center;
            font-size: 14px;
            color: #94a3b8;
        }
        .footer-links a {
            color: var(--primary-clr);
            text-decoration: none;
            font-weight: 700;
        }
        .alert {
            background: #fef2f2;
            color: #991b1b;
            padding: 12px 16px;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 20px;
            border-left: 4px solid #ef4444;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="portal-logo">
            <div><?php echo strtoupper(SITE_NAME_DYNAMIC); ?></div>
        </div>
        
        <div class="form-title">
            <h2>Welcome Back</h2>
            <p>Enter your credentials to access admin panel</p>
        </div>

        <?php if ($error): ?>
            <div class="alert"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="input-group">
                <label>Email Address</label>
                <input type="email" name="email" required placeholder="name@company.com">
            </div>
            
            <div class="input-group">
                <label>Password</label>
                <input type="password" name="password" required placeholder="••••••••">
            </div>

            <button type="submit" class="login-btn">Sign In to Dashboard</button>
        </form>

        <div class="footer-links">
            <a href="<?php echo BASE_URL; ?>">← Back to Website</a>
        </div>
    </div>
</body>
</html>

