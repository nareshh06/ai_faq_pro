<?php
/*
================================================
 ONE-TIME ADMIN SETUP
 Visit this file once in your browser
 (php/create_admin.php) to create your first
 admin account. Once at least one admin exists,
 this page disables itself for security.
================================================
*/
require_once 'config.php';

$message = '';
$messageType = '';

// Check if an admin already exists
$existing = $conn->query("SELECT COUNT(*) AS cnt FROM admins")->fetch_assoc();
$adminExists = $existing['cnt'] > 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$adminExists) {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($username) || empty($password)) {
        $message = 'Username and password are required.';
        $messageType = 'error';
    } elseif (strlen($password) < 6) {
        $message = 'Password must be at least 6 characters.';
        $messageType = 'error';
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO admins (username, password_hash) VALUES (?, ?)");
        $stmt->bind_param('ss', $username, $hash);

        if ($stmt->execute()) {
            $message = 'Admin account created successfully! You can now log in from the Knowledge Base or Analytics tab.';
            $messageType = 'success';
            $adminExists = true;
        } else {
            $message = 'Error creating admin: ' . $conn->error;
            $messageType = 'error';
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Setup — FAQ.ai</title>
<style>
  body { font-family: 'Segoe UI', Arial, sans-serif; background: #f4f6fb; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; }
  .box { background: #fff; padding: 36px; border-radius: 14px; box-shadow: 0 4px 24px rgba(0,0,0,0.08); width: 100%; max-width: 380px; }
  h2 { color: #1a1b2e; margin-bottom: 6px; }
  p.sub { color: #6b7080; font-size: 0.88rem; margin-bottom: 20px; }
  label { font-size: 0.85rem; font-weight: 600; color: #374151; display: block; margin-top: 12px; margin-bottom: 4px; }
  input { width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 0.92rem; box-sizing: border-box; }
  button { width: 100%; margin-top: 18px; background: #6d5bf6; color: #fff; border: none; padding: 12px; border-radius: 9px; font-weight: 600; cursor: pointer; font-size: 0.92rem; }
  button:hover { background: #5a48e0; }
  .msg { margin-top: 14px; font-size: 0.87rem; font-weight: 600; }
  .msg.success { color: #16a34a; }
  .msg.error { color: #dc2626; }
  .done { text-align: center; color: #374151; font-size: 0.92rem; }
  a { color: #6d5bf6; text-decoration: none; font-weight: 600; }
</style>
</head>
<body>
  <div class="box">
    <h2>Admin Setup</h2>
    <?php if ($adminExists && $messageType !== 'success'): ?>
      <p class="done">✅ An admin account already exists.<br>This setup page is now disabled.<br><br><a href="../index.html">← Back to FAQ.ai</a></p>
    <?php else: ?>
      <p class="sub">Create your first admin account to manage the Knowledge Base.</p>
      <form method="POST">
        <label>Username</label>
        <input type="text" name="username" required>
        <label>Password</label>
        <input type="password" name="password" minlength="6" required>
        <button type="submit">Create Admin Account</button>
      </form>
    <?php endif; ?>
    <?php if ($message): ?>
      <div class="msg <?php echo $messageType; ?>"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>
  </div>
</body>
</html>
