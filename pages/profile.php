<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
$user = getCurrentUser();

$success = $error = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_info') {
        $first = trim($_POST['first_name'] ?? '');
        $last  = trim($_POST['last_name']  ?? '');
        $phone = trim($_POST['phone_number'] ?? '');
        $school= trim($_POST['school'] ?? '');

        if (!$first || !$last) {
            $error = 'Name fields are required.';
        } else {
            // Handle profile pic upload
            $picPath = $user['profile_pic'];
            if (!empty($_FILES['profile_pic']['name'])) {
                $uploadDir = __DIR__ . '/../uploads/profile_pics/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                $ext  = strtolower(pathinfo($_FILES['profile_pic']['name'], PATHINFO_EXTENSION));
                $allowed = ['jpg','jpeg','png','gif','webp'];
                if (!in_array($ext, $allowed)) {
                    $error = 'Invalid image format. Use JPG, PNG, GIF, or WEBP.';
                } elseif ($_FILES['profile_pic']['size'] > 2*1024*1024) {
                    $error = 'Image must be under 2MB.';
                } else {
                    $filename = 'user_' . $user['id'] . '_' . time() . '.' . $ext;
                    move_uploaded_file($_FILES['profile_pic']['tmp_name'], $uploadDir . $filename);
                    $picPath = 'uploads/profile_pics/' . $filename;
                }
            }

            if (!$error) {
                $stmt = $conn->prepare("UPDATE users SET first_name=?,last_name=?,phone_number=?,school=?,profile_pic=? WHERE id=?");
                $stmt->bind_param("sssssi", $first, $last, $phone, $school, $picPath, $user['id']);
                $stmt->execute();
                $_SESSION['name'] = $first . ' ' . $last;
                $success = 'Profile updated successfully.';
                $user = getCurrentUser();
            }

        }
    } elseif ($action === 'change_password') {
        $current = $_POST['current_password'] ?? '';
        $new     = $_POST['new_password']     ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        if (!verifyPassword($current, $user['password'])) {
            $error = 'Current password is incorrect.';
        } elseif (strlen($new) < 8 || !preg_match('/[A-Z]/', $new) || !preg_match('/[a-z]/', $new) || !preg_match('/[0-9]/', $new)) {
            $error = 'New password must be 8+ characters with uppercase, lowercase, and a number.';
        } elseif ($new !== $confirm) {
            $error = 'Passwords do not match.';
        } else {
            $hash = hashPassword($new);
            $stmt = $conn->prepare("UPDATE users SET password=? WHERE id=?");
            $stmt->bind_param("si", $hash, $user['id']);
            $stmt->execute();
            $success = 'Password changed successfully.';
        }
    }
}

$profilePicUrl = $user['profile_pic']
    ? '/qcsim/' . htmlspecialchars($user['profile_pic'])
    : '/qcsim/Assets/MainWebsite/default_avatar.png';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Profile — QCSim</title>
  <link rel="stylesheet" href="/qcsim/Assets/MainWebsite/style.css">
  <style>
    body { min-height:100vh; }
    .profile-header {
      background: linear-gradient(135deg, var(--primary) 0%, #0d4f8a 100%);
      padding: 0 0 56px; position:relative; overflow:hidden;
    }
    .profile-header::before {
      content:''; position:absolute; top:-60px; right:-80px;
      width:320px; height:320px; background:rgba(255,255,255,.06);
      border-radius:50%;
    }
    .profile-header::after {
      content:''; position:absolute; bottom:-80px; left:10%;
      width:200px; height:200px; background:rgba(255,255,255,.04);
      border-radius:50%;
    }
    .profile-banner {
      height: 80px;
      background: rgba(255,255,255,.08);
    }
    .profile-avatar-wrap {
      display:flex; flex-direction:column; align-items:center;
      margin-top:-50px; position:relative; z-index:2;
    }
    .profile-avatar {
      width:100px; height:100px; border-radius:50%;
      border:4px solid #fff; object-fit:cover;
      box-shadow:0 4px 20px rgba(0,0,0,.2);
      background:#ccc;
    }
    .profile-name  { font-family:'Raleway',sans-serif; font-weight:800; font-size:22px; color:#fff; margin-top:12px; }
    .profile-email { color:rgba(255,255,255,.75); font-size:14px; margin-top:2px; }
    .profile-badge { margin-top:8px; }

    .profile-body { max-width:760px; margin:0 auto; padding:32px 24px 56px; }
    .section-title { font-family:'Raleway',sans-serif; font-weight:800; font-size:16px; color:var(--primary); margin-bottom:16px; }

    .info-table { width:100%; border-collapse:collapse; }
    .info-table td { padding:14px 16px; border-top:1px solid var(--border); font-size:14px; }
    .info-table tr:first-child td { border-top:none; }
    .info-table td:first-child { font-weight:700; width:160px; color:var(--text); }
    .info-table td:last-child  { color:var(--text-muted); }

    .avatar-upload-label {
      display:inline-block; margin-top:10px; cursor:pointer;
      font-size:13px; color:var(--primary); font-weight:600;
    }
    .avatar-upload-label:hover { text-decoration:underline; }
    #avatarInput { display:none; }
    #avatarPreview { width:100px; height:100px; border-radius:50%; object-fit:cover; border:3px solid var(--border); }

    .tab-bar { display:flex; gap:0; border-bottom:2px solid var(--border); margin-bottom:28px; }
    .tab-btn {
      padding:10px 20px; background:none; border:none; cursor:pointer;
      font-family:'Nunito',sans-serif; font-weight:700; font-size:14px;
      color:var(--text-muted); border-bottom:2px solid transparent; margin-bottom:-2px;
      transition:.2s;
    }
    .tab-btn.active { color:var(--primary); border-bottom-color:var(--primary); }
    .tab-pane { display:none; }
    .tab-pane.active { display:block; }
  </style>
</head>
<body>
  <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

  <!-- PROFILE HEADER -->
  <div class="profile-header">
    <div class="profile-banner"></div>
    <div class="profile-avatar-wrap">
      <img src="<?= $profilePicUrl ?>" alt="Avatar" class="profile-avatar" id="headerAvatar">
      <div class="profile-name"><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></div>
      <div class="profile-email"><?= htmlspecialchars($user['email']) ?></div>
      <div class="profile-badge">
        <span class="badge badge-<?= $user['role'] ?>"><?= ucfirst($user['role']) ?></span>
      </div>
    </div>
  </div>

  <!-- PROFILE BODY -->
  <div class="profile-body">

    <?php if ($success): ?>
    <div class="alert alert-success"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
    <div class="alert alert-error"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- TABS -->
    <div class="tab-bar">
      <button class="tab-btn active" onclick="switchTab('info',this)">Basic Information</button>
      <button class="tab-btn" onclick="switchTab('password',this)">Change Password</button>
    </div>

    <!-- TAB: BASIC INFO -->
    <div class="tab-pane active" id="tab-info">
      <div class="card">
        <div class="section-title">Basic Information</div>
        <form method="POST" enctype="multipart/form-data">
          <input type="hidden" name="action" value="update_info">

          <div style="display:flex;align-items:center;gap:20px;margin-bottom:24px;flex-wrap:wrap;">
            <img id="avatarPreview" src="<?= $profilePicUrl ?>" alt="Preview">
            <div>
              <label class="avatar-upload-label" for="avatarInput">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:middle;margin-right:4px;"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                Upload new photo
              </label>
              <input type="file" id="avatarInput" name="profile_pic" accept="image/*" onchange="previewAvatar(this)">
              <div class="form-hint">JPG, PNG, WEBP — max 2MB</div>
            </div>
          </div>

          <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
            <div class="form-group">
              <label class="form-label">Last Name</label>
              <input class="form-control" type="text" name="last_name" value="<?= htmlspecialchars($user['last_name']) ?>" required>
            </div>
            <div class="form-group">
              <label class="form-label">First Name</label>
              <input class="form-control" type="text" name="first_name" value="<?= htmlspecialchars($user['first_name']) ?>" required>
            </div>
          </div>

          <div class="form-group">
            <label class="form-label">Email Address</label>
            <input class="form-control" type="email" value="<?= htmlspecialchars($user['email']) ?>" disabled style="background:#f9fbfe;cursor:not-allowed;">
            <div class="form-hint">Email cannot be changed.</div>
          </div>

          <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
            <div class="form-group">
              <label class="form-label">Phone Number</label>
              <input class="form-control" type="tel" name="phone_number" value="<?= htmlspecialchars($user['phone_number'] ?? '') ?>">
            </div>
            <div class="form-group">
              <label class="form-label">School / Institution</label>
              <input class="form-control" type="text" name="school" value="<?= htmlspecialchars($user['school'] ?? '') ?>">
            </div>
          </div>

          <div class="form-group">
            <label class="form-label">Role</label>
            <input class="form-control" type="text" value="<?= ucfirst($user['role']) ?>" disabled style="background:#f9fbfe;cursor:not-allowed;">
          </div>

          <button type="submit" class="btn btn-primary">Save Changes</button>
        </form>
      </div>
    </div>

    <!-- TAB: CHANGE PASSWORD -->
    <div class="tab-pane" id="tab-password">
      <div class="card">
        <div class="section-title">Change Password</div>
        <form method="POST" style="max-width:440px;">
          <input type="hidden" name="action" value="change_password">
          <div class="form-group">
            <label class="form-label">Current Password</label>
            <input class="form-control" type="password" name="current_password" required>
          </div>
          <div class="form-group">
            <label class="form-label">New Password</label>
            <input class="form-control" type="password" name="new_password" required>
            <div class="form-hint">Minimum 8 characters, with uppercase, lowercase, and a number.</div>
          </div>
          <div class="form-group">
            <label class="form-label">Confirm New Password</label>
            <input class="form-control" type="password" name="confirm_password" required>
          </div>
          <button type="submit" class="btn btn-primary">Change Password</button>
        </form>
      </div>
    </div>

  </div><!-- end profile-body -->

  <script>
  function switchTab(id, btn) {
    document.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.getElementById('tab-' + id).classList.add('active');
    btn.classList.add('active');
  }
  function previewAvatar(input) {
    if (input.files && input.files[0]) {
      const reader = new FileReader();
      reader.onload = e => {
        document.getElementById('avatarPreview').src = e.target.result;
        document.getElementById('headerAvatar').src  = e.target.result;
      };
      reader.readAsDataURL(input.files[0]);
    }
  }
  // Open password tab if there's an error on password action
  <?php if ($error && ($_POST['action'] ?? '') === 'change_password'): ?>
    switchTab('password', document.querySelectorAll('.tab-btn')[1]);
  <?php endif; ?>
  </script>

  @media(max-width:600px){
    .info-table td:first-child{width:120px;}
    div[style*="grid-template-columns:1fr 1fr"]{grid-template-columns:1fr!important;}
  }
</body>
</html>
