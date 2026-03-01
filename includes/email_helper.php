<?php
/**
 * NewsCast Email Helper
 * Sends HTML emails using PHP mail() or SMTP via PHPMailer if configured.
 * Falls back gracefully to PHP mail().
 */

function get_email_template($subject, $body_html, $site_name = 'NewsCast', $primary_color = '#6366f1')
{
    return '<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>' . htmlspecialchars($subject) . '</title>
<style>
  body{margin:0;padding:0;background:#f1f5f9;font-family:\'Segoe UI\',Arial,sans-serif;}
  .wrapper{max-width:600px;margin:40px auto;background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,.08);}
  .header{background:' . $primary_color . ';padding:32px 40px;text-align:center;}
  .header h1{margin:0;color:#fff;font-size:24px;font-weight:800;letter-spacing:-0.5px;}
  .header p{margin:6px 0 0;color:rgba(255,255,255,.8);font-size:13px;}
  .body{padding:36px 40px;}
  .body p{color:#334155;font-size:15px;line-height:1.7;margin:0 0 16px;}
  .btn{display:inline-block;padding:14px 32px;background:' . $primary_color . ';color:#fff;text-decoration:none;border-radius:10px;font-weight:700;font-size:15px;margin:16px 0;}
  .divider{border:none;border-top:1px solid #e2e8f0;margin:24px 0;}
  .info-box{background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:18px 22px;margin:16px 0;}
  .info-box p{margin:0;color:#475569;font-size:13px;line-height:1.6;}
  .info-row{display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px solid #e2e8f0;}
  .info-row:last-child{border-bottom:none;}
  .info-label{color:#64748b;font-size:13px;font-weight:600;}
  .info-value{color:#0f172a;font-size:13px;font-weight:700;}
  .footer{background:#f8fafc;padding:20px 40px;text-align:center;border-top:1px solid #e2e8f0;}
  .footer p{color:#94a3b8;font-size:12px;margin:0;line-height:1.6;}
  .badge{display:inline-block;background:rgba(99,102,241,.1);color:' . $primary_color . ';padding:3px 10px;border-radius:20px;font-size:11px;font-weight:700;margin-bottom:12px;}
</style>
</head>
<body>
<div class="wrapper">
  <div class="header">
    <h1>' . htmlspecialchars($site_name) . '</h1>
    <p>Digital News Platform</p>
  </div>
  <div class="body">
    ' . $body_html . '
  </div>
  <div class="footer">
    <p>This email was sent by <strong>' . htmlspecialchars($site_name) . '</strong>.<br>
    If you did not request this, please ignore this email. &copy; ' . date('Y') . ' ' . htmlspecialchars($site_name) . '. All rights reserved.</p>
  </div>
</div>
</body>
</html>';
}

/**
 * Send an email
 */
function send_email($to, $subject, $body_html, $from_name = '', $from_email = '')
{
    global $settings;

    $site_name = $from_name ?: get_setting('site_name', 'NewsCast');
    $contact_mail = $from_email ?: get_setting('contact_email', 'noreply@newscast.com');
    $primary_col = get_setting('theme_color', '#6366f1');

    $full_html = get_email_template($subject, $body_html, $site_name, $primary_col);

    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: =?UTF-8?B?" . base64_encode($site_name) . "?= <" . $contact_mail . ">\r\n";
    $headers .= "Reply-To: " . $contact_mail . "\r\n";
    $headers .= "X-Mailer: NewsCast-PHP\r\n";

    // Check for SMTP settings stored in DB
    $smtp_host = get_setting('smtp_host', '');
    $smtp_user = get_setting('smtp_user', '');
    $smtp_pass = get_setting('smtp_pass', '');
    $smtp_port = get_setting('smtp_port', '587');

    if ($smtp_host && $smtp_user && $smtp_pass) {
        return send_via_smtp($to, $subject, $full_html, $site_name, $contact_mail, $smtp_host, $smtp_user, $smtp_pass, (int)$smtp_port);
    }

    return @mail($to, '=?UTF-8?B?' . base64_encode($subject) . '?=', $full_html, $headers);
}

/**
 * Send via SMTP using socket connection (no PHPMailer dependency)
 */
function send_via_smtp($to, $subject, $body, $from_name, $from_email, $host, $username, $password, $port = 587)
{
    try {
        $socket = fsockopen(($port == 465 ? 'ssl://' : '') . $host, $port, $errno, $errstr, 15);
        if (!$socket)
            return false;

        $read = fgets($socket, 515);
        fputs($socket, "EHLO " . $_SERVER['HTTP_HOST'] . "\r\n");
        $read = fread($socket, 1024);

        if ($port == 587) {
            fputs($socket, "STARTTLS\r\n");
            fgets($socket, 515);
            stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            fputs($socket, "EHLO " . $_SERVER['HTTP_HOST'] . "\r\n");
            $read = fread($socket, 1024);
        }

        fputs($socket, "AUTH LOGIN\r\n");
        fgets($socket, 515);
        fputs($socket, base64_encode($username) . "\r\n");
        fgets($socket, 515);
        fputs($socket, base64_encode($password) . "\r\n");
        $auth = fgets($socket, 515);
        if (strpos($auth, '235') === false) {
            fclose($socket);
            return false;
        }

        fputs($socket, "MAIL FROM:<{$from_email}>\r\n");
        fgets($socket, 515);
        fputs($socket, "RCPT TO:<{$to}>\r\n");
        fgets($socket, 515);
        fputs($socket, "DATA\r\n");
        fgets($socket, 515);

        $msg = "From: =?UTF-8?B?" . base64_encode($from_name) . "?= <{$from_email}>\r\n";
        $msg .= "To: {$to}\r\n";
        $msg .= "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=\r\n";
        $msg .= "MIME-Version: 1.0\r\n";
        $msg .= "Content-Type: text/html; charset=UTF-8\r\n";
        $msg .= "\r\n";
        $msg .= $body . "\r\n.\r\n";
        fputs($socket, $msg);
        $res = fgets($socket, 515);
        fputs($socket, "QUIT\r\n");
        fclose($socket);

        return strpos($res, '250') !== false;
    }
    catch (Exception $e) {
        return false;
    }
}

/**
 * Send forgot password email
 */
function send_forgot_password_email($to_email, $reset_link, $username = '')
{
    $site_name = get_setting('site_name', 'NewsCast');
    $subject = "Reset Your Password — {$site_name}";

    $body = '
    <div class="badge">🔐 Password Reset</div>
    <p>Hello <strong>' . htmlspecialchars($username ?: $to_email) . '</strong>,</p>
    <p>We received a request to reset the password for your <strong>' . htmlspecialchars($site_name) . '</strong> account. Click the button below to create a new password:</p>
    <div style="text-align:center;margin:28px 0;">
        <a href="' . $reset_link . '" class="btn">🔑 Reset My Password</a>
    </div>
    <div class="info-box">
        <p><strong>⚠️ Important:</strong> This link will expire in <strong>1 hour</strong>. If you did not request a password reset, you can safely ignore this email — your password will not be changed.</p>
    </div>
    <hr class="divider">
    <p style="font-size:13px;color:#64748b;">If the button above doesn\'t work, copy and paste this link into your browser:<br>
    <a href="' . $reset_link . '" style="color:#6366f1;word-break:break-all;">' . $reset_link . '</a></p>';

    return send_email($to_email, $subject, $body);
}

/**
 * Send welcome/registration email
 */
function send_welcome_email($to_email, $username, $temp_password = '')
{
    $site_name = get_setting('site_name', 'NewsCast');
    $base_url = BASE_URL;
    $subject = "Welcome to {$site_name} — Your Account is Ready!";

    $creds_html = '';
    if ($temp_password) {
        $creds_html = '
    <div class="info-box">
        <p><strong>Your Login Credentials:</strong></p>
        <div class="info-row"><span class="info-label">Email</span><span class="info-value">' . htmlspecialchars($to_email) . '</span></div>
        <div class="info-row"><span class="info-label">Temporary Password</span><span class="info-value">' . htmlspecialchars($temp_password) . '</span></div>
    </div>
    <p style="font-size:13px;color:#ef4444;"><strong>⚠ Please change your password after first login!</strong></p>';
    }

    $body = '
    <div class="badge">🎉 Welcome Aboard!</div>
    <p>Hello <strong>' . htmlspecialchars($username) . '</strong>,</p>
    <p>Your account on <strong>' . htmlspecialchars($site_name) . '</strong> has been created successfully. You are now part of our journalist team!</p>
    ' . $creds_html . '
    <div style="text-align:center;margin:28px 0;">
        <a href="' . $base_url . 'login.php" class="btn">🚀 Login to Dashboard</a>
    </div>
    <hr class="divider">
    <p style="font-size:13px;color:#64748b;">If you have any questions, please contact the administrator.</p>';

    return send_email($to_email, $subject, $body);
}

/**
 * Send reporter joining letter email
 */
function send_joining_letter_email($to_email, $reporter_name, $letter_html, $designation = 'Reporter')
{
    $site_name = get_setting('site_name', 'NewsCast');
    $subject = "Joining Letter — {$site_name}";

    $body = '
    <div class="badge">📄 Joining Letter</div>
    <p>Dear <strong>' . htmlspecialchars($reporter_name) . '</strong>,</p>
    <p>Congratulations! Please find your official joining letter for the position of <strong>' . htmlspecialchars($designation) . '</strong> at <strong>' . htmlspecialchars($site_name) . '</strong> below.</p>
    <hr class="divider">
    ' . $letter_html . '
    <hr class="divider">
    <p style="font-size:13px;color:#64748b;">This is an auto-generated letter. For queries, contact the editorial team.</p>';

    return send_email($to_email, $subject, $body);
}
?>
