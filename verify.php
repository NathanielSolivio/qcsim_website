<?php
session_start();
require_once __DIR__ . '/includes/db.php';

$token = trim($_GET['token'] ?? '');
$msg   = '';
$type  = 'error';

if (!$token) {
    $msg = 'Invalid verification link.';
} else {
    $stmt = $conn->prepare("SELECT id, is_verified, token_expires FROM users WHERE verify_token = ? LIMIT 1");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    if (!$user) {
        $msg = 'Invalid or already used verification link.';
    } elseif ($user['is_verified']) {
        $msg  = 'Your account is already verified. You can sign in.';
        $type = 'success';
    } elseif (strtotime($user['token_expires']) < time()) {
        $msg = 'This verification link has expired. Please sign up again.';
    } else {
        $upd = $conn->prepare("UPDATE users SET is_verified=1, verify_token=NULL, token_expires=NULL WHERE id=?");
        $upd->bind_param("i", $user['id']);
        $upd->execute();
        header('Location: /qcsim/login.php?verified=1');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Verify Email — QCSim</title>
  <link rel="stylesheet" href="/qcsim/Assets/MainWebsite/style.css">
  <style>
    body{display:flex;align-items:center;justify-content:center;min-height:100vh;background:var(--bg);}
    .verify-box{max-width:440px;width:90%;text-align:center;padding:48px 36px;background:#fff;border-radius:18px;box-shadow:var(--shadow-lg);}
    .verify-box h2{font-family:'Raleway',sans-serif;font-weight:800;font-size:22px;margin:16px 0 8px;}
    .verify-box p{color:var(--text-muted);font-size:15px;margin-bottom:24px;}
  </style>
</head>
<body>
  <div class="verify-box">
    <?php if ($type === 'success'): ?>
      <svg width="56" height="56" viewBox="0 0 24 24" fill="none" stroke="#38a169" stroke-width="2" style="margin:auto"><circle cx="12" cy="12" r="10"/><polyline points="9 12 11 14 15 10"/></svg>
      <h2>Email Verified!</h2>
    <?php else: ?>
      <svg width="56" height="56" viewBox="0 0 24 24" fill="none" stroke="#e53e3e" stroke-width="2" style="margin:auto"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
      <h2>Verification Failed</h2>
    <?php endif; ?>
    <p><?= htmlspecialchars($msg) ?></p>
    <a href="/qcsim/login.php" class="btn btn-primary">Go to Sign In</a>
  </div>
</body>
</html>
