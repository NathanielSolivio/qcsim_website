<?php
session_start();
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

// Already logged in → go home
if (isLoggedIn()) { header('Location: /qcsim/index.php'); exit; }

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$email || !$password) {
        $error = 'Please fill in all fields.';
    } else {
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        if (!$user || !verifyPassword($password, $user['password'])) {
            $error = 'Invalid email or password.';
        } elseif (!$user['is_verified']) {
            $error = 'Please verify your email before signing in. Check your inbox.';
        } else {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role']    = $user['role'];
            $_SESSION['name']    = $user['first_name'] . ' ' . $user['last_name'];
            header('Location: /qcsim/index.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sign In — QCSim</title>
  <link rel="stylesheet" href="/qcsim/Assets/MainWebsite/style.css">
  <style>
    body {
      display: flex; min-height: 100vh;
      background: #e8f3fc;
    }
    .auth-left {
      width: 420px; min-width: 320px; flex-shrink: 0;
      background: #fff;
      display: flex; flex-direction: column; justify-content: center;
      padding: 56px 48px;
      position: relative; z-index: 2;
      box-shadow: 4px 0 32px rgba(26,107,181,.10);
    }
    .auth-logo { display: flex; align-items: center; gap: 10px; margin-bottom: 36px; }
    .auth-logo img { height: 36px; }
    .auth-logo span { font-family:'Raleway',sans-serif; font-weight:800; font-size:22px; color:var(--primary); }
    .auth-left h1 { font-family:'Raleway',sans-serif; font-size:28px; font-weight:900; margin-bottom:6px; }
    .auth-left p  { color:var(--text-muted); font-size:14px; margin-bottom:28px; line-height:1.5; }
    .auth-right {
      flex: 1;
      background: url('/qcsim/Assets/MainWebsite/bg_lab.png') center/cover no-repeat;
      position: relative;
    }
    .auth-right::after {
      content:''; position:absolute; inset:0;
      background: linear-gradient(135deg, rgba(232,243,252,.55) 0%, rgba(168,216,255,.25) 100%);
    }
    .forgot-link { font-size:13px; color:var(--primary); float:right; margin-top:-2px; }
    .signup-row { text-align:center; margin-top:20px; font-size:14px; color:var(--text-muted); }
    .signup-row a { font-weight:700; }

    @media (max-width: 700px) {
      body { flex-direction: column; }
      .auth-left { width: 100%; padding: 40px 24px; box-shadow: none; }
      .auth-right { display: none; }
    }
  </style>
</head>
<body>
  <div class="auth-left">
    <div class="auth-logo">
      <img src="/qcsim/Assets/MainWebsite/logo.png" alt="QCSim">
      <span>QCSim</span>
    </div>

    <h1>Welcome Back!</h1>
    <p>Your virtual laboratory for learning pharmaceutical quality control.</p>

    <?php if ($error): ?>
    <div class="alert alert-error">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
      <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>
    <?php if (isset($_GET['verified'])): ?>
    <div class="alert alert-success">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
      Email verified! You can now sign in.
    </div>
    <?php endif; ?>

    <form method="POST" novalidate>
      <div class="form-group">
        <label class="form-label" for="email">Email</label>
        <div class="input-icon-wrap">
          <svg class="icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-10 7L2 7"/></svg>
          <input class="form-control" type="email" id="email" name="email" placeholder="Enter your email address" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required autocomplete="email">
        </div>
      </div>
      <div class="form-group">
        <label class="form-label" for="password">
          Password
          <a href="/qcsim/forgot_password.php" class="forgot-link">Forgot password?</a>
        </label>
        <div class="input-icon-wrap">
          <svg class="icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
          <input class="form-control" type="password" id="password" name="password" placeholder="Enter your password" required autocomplete="current-password">
          <button type="button" class="eye-toggle" onclick="togglePw('password',this)">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
          </button>
        </div>
      </div>
      <button type="submit" class="btn btn-primary btn-full" style="margin-top:8px;">Sign In</button>
    </form>

    <div class="signup-row">
      Don't have an account? <a href="/qcsim/signup.php">Sign Up</a>
    </div>
  </div>
  <div class="auth-right"></div>

  <script>
    function togglePw(id, btn) {
      const f = document.getElementById(id);
      f.type = f.type === 'password' ? 'text' : 'password';
    }
  </script>
</body>
</html>
