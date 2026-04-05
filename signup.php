<?php
session_start();
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/mailer.php';

if (isLoggedIn()) { header('Location: /qcsim/index.php'); exit; }

$error = $success = '';
$fields = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fields = [
        'last_name'    => trim($_POST['last_name']    ?? ''),
        'first_name'   => trim($_POST['first_name']   ?? ''),
        'email'        => trim($_POST['email']        ?? ''),
        'phone_number' => trim($_POST['phone_number'] ?? ''),
        'school'       => trim($_POST['school']       ?? ''),
        'password'     => $_POST['password']          ?? '',
        'confirm_pw'   => $_POST['confirm_password']  ?? '',
    ];

    // Validation
    if (!$fields['last_name'] || !$fields['first_name'] || !$fields['email'] || !$fields['password']) {
        $error = 'Please fill in all required fields.';
    } elseif (!filter_var($fields['email'], FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($fields['password']) < 8 || !preg_match('/[A-Z]/', $fields['password']) || !preg_match('/[a-z]/', $fields['password']) || !preg_match('/[0-9]/', $fields['password'])) {
        $error = 'Password must be at least 8 characters with uppercase, lowercase, and a number.';
    } elseif ($fields['password'] !== $fields['confirm_pw']) {
        $error = 'Passwords do not match.';
    } else {
        // Check duplicate email
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $stmt->bind_param("s", $fields['email']);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $error = 'An account with this email already exists.';
        } else {
            $hash  = hashPassword($fields['password']);
            $token = generateToken();
            $expires = date('Y-m-d H:i:s', strtotime('+24 hours'));

            $ins = $conn->prepare("INSERT INTO users (last_name, first_name, email, phone_number, school, password, role, verify_token, token_expires) VALUES (?,?,?,?,?,?,'student',?,?)");
            $ins->bind_param("ssssssss", $fields['last_name'], $fields['first_name'], $fields['email'], $fields['phone_number'], $fields['school'], $hash, $token, $expires);

            if ($ins->execute()) {
                $fullName = $fields['first_name'] . ' ' . $fields['last_name'];
                $sent = sendConfirmationEmail($fields['email'], $fullName, $token);
                $success = $sent
                    ? 'Account created! Check your email for a confirmation link.'
                    : 'Account created, but we could not send the confirmation email. Please contact support.';
                $fields = [];
            } else {
                $error = 'Registration failed. Please try again.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sign Up — QCSim</title>
  <link rel="stylesheet" href="/qcsim/Assets/MainWebsite/style.css">
  <style>
    body { display:flex; min-height:100vh; background:#e8f3fc; }
    .auth-left {
      width:460px; min-width:320px; flex-shrink:0;
      background:#fff; display:flex; flex-direction:column; justify-content:center;
      padding:44px 48px; position:relative; z-index:2;
      box-shadow:4px 0 32px rgba(26,107,181,.10); overflow-y:auto;
    }
    .auth-logo { display:flex; align-items:center; gap:10px; margin-bottom:28px; }
    .auth-logo img { height:34px; }
    .auth-logo span { font-family:'Raleway',sans-serif; font-weight:800; font-size:21px; color:var(--primary); }
    .auth-left h1 { font-family:'Raleway',sans-serif; font-size:26px; font-weight:900; margin-bottom:5px; }
    .auth-left p  { color:var(--text-muted); font-size:14px; margin-bottom:24px; }
    .form-row { display:grid; grid-template-columns:1fr 1fr; gap:14px; }
    .auth-right { flex:1; background:url('/qcsim/Assets/MainWebsite/bg_lab.png') center/cover no-repeat; position:relative; }
    .auth-right::after { content:''; position:absolute; inset:0; background:linear-gradient(135deg,rgba(232,243,252,.55),rgba(168,216,255,.25)); }
    .signin-row { text-align:center; margin-top:18px; font-size:14px; color:var(--text-muted); }
    .signin-row a { font-weight:700; }
    @media(max-width:700px){
      body{flex-direction:column;}
      .auth-left{width:100%;padding:32px 18px;box-shadow:none;}
      .auth-right{display:none;}
      .form-row{grid-template-columns:1fr;}
    }
  </style>
</head>
<body>
  <div class="auth-left">
    <div class="auth-logo">
      <img src="/qcsim/Assets/MainWebsite/logo.png" alt="QCSim">
      <span>QCSim</span>
    </div>

    <h1>Create new account</h1>
    <p>Your virtual laboratory for learning pharmaceutical quality control.</p>

    <?php if ($error): ?>
    <div class="alert alert-error"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
    <div class="alert alert-success"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <form method="POST" novalidate>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Last Name <span style="color:var(--danger)">*</span></label>
          <input class="form-control" type="text" name="last_name" placeholder="Dela Cruz" value="<?= htmlspecialchars($fields['last_name'] ?? '') ?>" required>
        </div>
        <div class="form-group">
          <label class="form-label">First Name <span style="color:var(--danger)">*</span></label>
          <input class="form-control" type="text" name="first_name" placeholder="Juan" value="<?= htmlspecialchars($fields['first_name'] ?? '') ?>" required>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Email Address <span style="color:var(--danger)">*</span></label>
          <input class="form-control" type="email" name="email" placeholder="juandelacruz@gmail.com" value="<?= htmlspecialchars($fields['email'] ?? '') ?>" required>
        </div>
        <div class="form-group">
          <label class="form-label">Phone Number</label>
          <input class="form-control" type="tel" name="phone_number" placeholder="+63 912 345 6789" value="<?= htmlspecialchars($fields['phone_number'] ?? '') ?>">
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">School / Institution</label>
        <input class="form-control" type="text" name="school" placeholder="University / College name" value="<?= htmlspecialchars($fields['school'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label class="form-label">Create a password <span style="color:var(--danger)">*</span></label>
        <div class="input-icon-wrap">
          <svg class="icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
          <input class="form-control" type="password" id="pw1" name="password" placeholder="Create a strong password" required>
          <button type="button" class="eye-toggle" onclick="togglePw('pw1',this)"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></button>
        </div>
        <div class="form-hint">Minimum of 8 characters, with upper and lowercase letter and a number</div>
      </div>
      <div class="form-group">
        <label class="form-label">Confirm password <span style="color:var(--danger)">*</span></label>
        <div class="input-icon-wrap">
          <svg class="icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
          <input class="form-control" type="password" id="pw2" name="confirm_password" placeholder="Re-type password" required>
          <button type="button" class="eye-toggle" onclick="togglePw('pw2',this)"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></button>
        </div>
        <div class="form-hint">Minimum of 8 characters, with upper and lowercase letter and a number</div>
      </div>
      <button type="submit" class="btn btn-primary btn-full">Sign Up</button>
    </form>

    <div class="signin-row">Already have an account? <a href="/qcsim/login.php">Sign In</a></div>
  </div>
  <div class="auth-right"></div>
  <script>function togglePw(id){const f=document.getElementById(id);f.type=f.type==='password'?'text':'password';}</script>
</body>
</html>
