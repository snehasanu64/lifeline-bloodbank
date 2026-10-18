<?php
session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: admin_login.php");
    exit();
}

include('db_connect.php');

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$msg = "";
$err = "";
$adminId = 'admin_' . strtolower($_SESSION['admin_username'] ?? 'admin');

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['change'])) {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $err = "Invalid request. Please refresh and try again.";
    } else {
        $current = $_POST['current_password'] ?? '';
        $new = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        try {
            $admin = $db->getById('admins', $adminId);
            if (!$admin || !password_verify($current, $admin['password'] ?? '')) {
                $err = "Current password is incorrect.";
            } elseif (strlen($new) < 8) {
                $err = "New password must be at least 8 characters.";
            } elseif ($new !== $confirm) {
                $err = "New password and confirmation do not match.";
            } elseif ($new === $current) {
                $err = "New password must be different from the current one.";
            } else {
                $db->update('admins', $adminId, ['password' => password_hash($new, PASSWORD_DEFAULT)]);
                $msg = "Password changed successfully.";
            }
        } catch (Exception $e) {
            error_log("Admin password change failed: " . $e->getMessage());
            $err = "Could not change the password. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Admin Password</title>
    <style>
        * { box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { margin: 0; background: #f4f4f4; display: flex; justify-content: center; padding: 40px 16px; }
        .box { background: #fff; width: 100%; max-width: 440px; border-radius: 14px; padding: 28px; border-left: 8px solid #7a1538; box-shadow: 0 6px 20px rgba(0,0,0,.08); }
        h2 { margin: 0 0 18px; color: #7a1538; }
        label { display: block; margin: 14px 0 6px; font-weight: 600; color: #333; }
        input { width: 100%; padding: 11px; border: 1px solid #ccc; border-radius: 8px; }
        button { margin-top: 22px; width: 100%; padding: 12px; background: #7a1538; color: #fff; border: 0; border-radius: 24px; font-weight: bold; cursor: pointer; }
        .ok { background: #e6f4ea; color: #1b5e20; padding: 10px 12px; border-radius: 8px; margin-bottom: 10px; }
        .bad { background: #fdecea; color: #b71c1c; padding: 10px 12px; border-radius: 8px; margin-bottom: 10px; }
        a { color: #7a1538; display: inline-block; margin-top: 16px; }
    </style>
</head>
<body>
<div class="box">
    <h2>Change Admin Password</h2>
    <?php if ($msg) : ?><div class="ok"><?php echo htmlspecialchars($msg, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
    <?php if ($err) : ?><div class="bad"><?php echo htmlspecialchars($err, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
    <form method="post">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
        <label>Current password</label>
        <input type="password" name="current_password" required autocomplete="current-password">
        <label>New password (at least 8 characters)</label>
        <input type="password" name="new_password" required minlength="8" autocomplete="new-password">
        <label>Confirm new password</label>
        <input type="password" name="confirm_password" required minlength="8" autocomplete="new-password">
        <button type="submit" name="change">Change Password</button>
    </form>
    <a href="admin_dashboard.php">&larr; Back to Dashboard</a>
</div>
</body>
</html>
