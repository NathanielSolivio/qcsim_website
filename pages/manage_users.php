<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole('admin');
$user = getCurrentUser();

$success = $error = '';

// ── DELETE ───────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action']??'') === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id === $user['id']) {
        $error = 'You cannot delete your own account.';
    } else {
        $chk = $conn->prepare("SELECT role FROM users WHERE id=? LIMIT 1");
        $chk->bind_param("i", $id);
        $chk->execute();
        $row = $chk->get_result()->fetch_assoc();
        if ($row && $row['role'] === 'admin') {
            $error = 'Cannot delete the admin account.';
        } else {
            $del = $conn->prepare("DELETE FROM users WHERE id=?");
            $del->bind_param("i", $id);
            $del->execute();
            $success = 'User deleted.';
        }
    }
}

// ── EDIT ROLE ────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action']??'') === 'edit') {
    $id   = (int)($_POST['id'] ?? 0);
    $role = $_POST['role'] ?? '';
    if (!in_array($role, ['student','instructor'])) {
        $error = 'Invalid role selected.';
    } elseif ($id === $user['id']) {
        $error = "You cannot change your own role.";
    } else {
        $upd = $conn->prepare("UPDATE users SET role=? WHERE id=? AND role != 'admin'");
        $upd->bind_param("si", $role, $id);
        $upd->execute();
        $success = $upd->affected_rows > 0 ? 'Role updated.' : 'Could not update role.';
    }
}

// ── FETCH ────────────────────────────────────────────────────────────────────
$search = trim($_GET['search'] ?? '');
$roleFilter = trim($_GET['role'] ?? '');

$sql = "SELECT * FROM users WHERE 1=1";
$params = []; $types = '';
if ($search) {
    $sql .= " AND (first_name LIKE ? OR last_name LIKE ? OR email LIKE ?)";
    $like = "%$search%"; $params[] = $like; $params[] = $like; $params[] = $like; $types .= 'sss';
}
if ($roleFilter) {
    $sql .= " AND role = ?";
    $params[] = $roleFilter; $types .= 's';
}
$sql .= " ORDER BY created_at DESC";
$stmt = $conn->prepare($sql);
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manage Users — QCSim</title>
  <link rel="stylesheet" href="/qcsim/Assets/MainWebsite/style.css">
  <style>
    body{min-height:100vh;}
    .filters{display:flex;gap:10px;flex-wrap:wrap;margin-bottom:20px;}
    .filters input,.filters select{flex:1;min-width:150px;max-width:260px;padding:9px 14px;border:1.5px solid var(--border);border-radius:var(--radius);font-family:'Nunito',sans-serif;font-size:14px;background:#fff;outline:none;}
    .filters input:focus,.filters select:focus{border-color:var(--primary);}
    .user-avatar{width:34px;height:34px;border-radius:50%;object-fit:cover;border:2px solid var(--border);}
  </style>
</head>
<body>
  <?php require_once __DIR__ . '/../includes/navbar.php'; ?>
  <div class="container" style="padding-top:32px;padding-bottom:56px;">

    <div class="page-header">
      <h1>Manage Users</h1>
      <p>View, edit roles, and remove user accounts.</p>
    </div>

    <?php if ($success): ?>
    <div class="alert alert-success"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
    <div class="alert alert-error"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- FILTERS -->
    <form method="GET" class="filters">
      <input type="text" name="search" placeholder="🔍 Search name or email…" value="<?= htmlspecialchars($search) ?>">
      <select name="role">
        <option value="">All Roles</option>
        <option value="student"    <?= $roleFilter==='student'   ?'selected':'' ?>>Student</option>
        <option value="instructor" <?= $roleFilter==='instructor'?'selected':'' ?>>Instructor</option>
        <option value="admin"      <?= $roleFilter==='admin'     ?'selected':'' ?>>Admin</option>
      </select>
      <button type="submit" class="btn btn-primary btn-sm">Filter</button>
      <?php if ($search || $roleFilter): ?>
        <a href="/qcsim/pages/manage_users.php" class="btn btn-outline btn-sm">Clear</a>
      <?php endif; ?>
    </form>

    <div style="font-size:14px;color:var(--text-muted);margin-bottom:16px;"><?= count($users) ?> user(s) found</div>

    <!-- TABLE -->
    <div class="table-wrap">
      <table>
        <thead>
          <tr><th>#</th><th>User</th><th>Email</th><th>Phone</th><th>School</th><th>Role</th><th>Verified</th><th>Joined</th><th>Actions</th></tr>
        </thead>
        <tbody>
          <?php if (empty($users)): ?>
          <tr><td colspan="9" style="text-align:center;color:var(--text-muted);padding:32px;">No users found.</td></tr>
          <?php else: foreach ($users as $i => $u): ?>
          <tr>
            <td><?= $i+1 ?></td>
            <td>
              <div class="flex flex-center gap-2">
                <img class="user-avatar" src="<?= $u['profile_pic'] ? '/qcsim/'.htmlspecialchars($u['profile_pic']) : '/qcsim/Assets/MainWebsite/default_avatar.png' ?>" alt="">
                <span><strong><?= htmlspecialchars($u['first_name'].' '.$u['last_name']) ?></strong></span>
              </div>
            </td>
            <td><?= htmlspecialchars($u['email']) ?></td>
            <td><?= htmlspecialchars($u['phone_number'] ?? '—') ?></td>
            <td><?= htmlspecialchars($u['school'] ?? '—') ?></td>
            <td><span class="badge badge-<?= $u['role'] ?>"><?= ucfirst($u['role']) ?></span></td>
            <td><?= $u['is_verified'] ? '<span class="badge badge-verified">✔ Yes</span>' : '<span class="badge badge-pending">Pending</span>' ?></td>
            <td><?= date('M j, Y', strtotime($u['created_at'])) ?></td>
            <td>
              <?php if ($u['role'] !== 'admin'): ?>
              <div class="flex gap-2">
                <button class="btn btn-outline btn-sm" onclick='openEditUser(<?= json_encode(['id'=>$u['id'],'name'=>$u['first_name']." ".$u['last_name'],'role'=>$u['role']]) ?>)'>Edit Role</button>
                <form method="POST" onsubmit="return confirm('Delete this user?');" style="display:inline;">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id" value="<?= $u['id'] ?>">
                  <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                </form>
              </div>
              <?php else: ?>
              <span style="font-size:12px;color:var(--text-muted);">—</span>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- EDIT ROLE MODAL -->
  <div class="modal-overlay" id="editUserModal">
    <div class="modal">
      <div class="modal-header">
        <h3>Edit User Role</h3>
        <button class="modal-close" onclick="closeModal('editUserModal')">✕</button>
      </div>
      <form method="POST">
        <input type="hidden" name="action" value="edit">
        <input type="hidden" name="id" id="editUserId">
        <div class="form-group">
          <label class="form-label">User</label>
          <input class="form-control" id="editUserName" type="text" disabled style="background:#f9fbfe;">
        </div>
        <div class="form-group">
          <label class="form-label">Role <span style="color:var(--danger)">*</span></label>
          <select class="form-control" name="role" id="editUserRole">
            <option value="student">Student</option>
            <option value="instructor">Instructor</option>
          </select>
        </div>
        <div class="flex gap-2" style="justify-content:flex-end;">
          <button type="button" class="btn btn-outline" onclick="closeModal('editUserModal')">Cancel</button>
          <button type="submit" class="btn btn-primary">Save Role</button>
        </div>
      </form>
    </div>
  </div>

  <script>
    function openModal(id)  { document.getElementById(id).classList.add('open'); }
    function closeModal(id) { document.getElementById(id).classList.remove('open'); }
    function openEditUser(u) {
      document.getElementById('editUserId').value   = u.id;
      document.getElementById('editUserName').value = u.name;
      document.getElementById('editUserRole').value = u.role;
      openModal('editUserModal');
    }
    document.querySelectorAll('.modal-overlay').forEach(o => {
      o.addEventListener('click', e => { if(e.target===o) o.classList.remove('open'); });
    });
  </script>
</body>
</html>
