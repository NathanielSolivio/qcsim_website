<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole('instructor', 'admin');
$user = getCurrentUser();

$success = $error = '';
$uploadDir = __DIR__ . '/../uploads/materials/';
if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

// ── DELETE ──────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    // Admin can delete any, instructor only their own
    $where = $user['role'] === 'admin' ? "id=?" : "id=? AND uploaded_by={$user['id']}";
    $sel = $conn->prepare("SELECT file_path FROM learningMaterialsTable WHERE $where LIMIT 1");
    $sel->bind_param("i", $id);
    $sel->execute();
    $row = $sel->get_result()->fetch_assoc();
    if ($row) {
        $fullPath = __DIR__ . '/../' . $row['file_path'];
        if (file_exists($fullPath)) unlink($fullPath);
        $del = $conn->prepare("DELETE FROM learningMaterialsTable WHERE id=?");
        $del->bind_param("i", $id);
        $del->execute();
        $success = 'Material deleted.';
    } else {
        $error = 'Item not found or insufficient permissions.';
    }
}

// ── ADD ──────────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add') {
    $title    = trim($_POST['title']       ?? '');
    $desc     = trim($_POST['description'] ?? '');
    $category = trim($_POST['category']    ?? '');

    if (!$title || empty($_FILES['material_file']['name'])) {
        $error = 'Title and file are required.';
    } else {
        $origName = $_FILES['material_file']['name'];
        $ext      = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
        $mime     = $_FILES['material_file']['type'];
        $size     = $_FILES['material_file']['size'];
        $filename = 'mat_' . time() . '_' . uniqid() . '.' . $ext;
        $dest     = $uploadDir . $filename;

        if ($_FILES['material_file']['error'] !== UPLOAD_ERR_OK) {
            $error = 'File upload failed.';
        } elseif ($size > 50 * 1024 * 1024) {
            $error = 'File must be under 50MB.';
        } else {
            move_uploaded_file($_FILES['material_file']['tmp_name'], $dest);
            $relPath = 'uploads/materials/' . $filename;
            $ins = $conn->prepare("INSERT INTO learningMaterialsTable (title, description, file_path, file_type, file_size, category, uploaded_by) VALUES (?,?,?,?,?,?,?)");
            $ins->bind_param("ssssiis", $title, $desc, $relPath, $mime, $size, $category, $user['id']);
            $ins->execute();
            $success = 'Material uploaded successfully.';
        }
    }
}

// ── EDIT ─────────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'edit') {
    $id       = (int)($_POST['id'] ?? 0);
    $title    = trim($_POST['title']       ?? '');
    $desc     = trim($_POST['description'] ?? '');
    $category = trim($_POST['category']    ?? '');
    $where    = $user['role'] === 'admin' ? "id=?" : "id=? AND uploaded_by={$user['id']}";

    if (!$title) {
        $error = 'Title is required.';
    } else {
        $upd = $conn->prepare("UPDATE learningMaterialsTable SET title=?,description=?,category=? WHERE $where");
        $upd->bind_param("sssi", $title, $desc, $category, $id);
        $upd->execute();
        $success = $upd->affected_rows > 0 ? 'Material updated.' : 'Nothing changed or insufficient permissions.';
    }
}

// ── FETCH LIST ───────────────────────────────────────────────────────────────
$where = $user['role'] === 'admin' ? '' : "WHERE m.uploaded_by = {$user['id']}";
$materials = $conn->query(
    "SELECT m.*, CONCAT(u.first_name,' ',u.last_name) AS uploader_name
     FROM learningMaterialsTable m JOIN users u ON u.id=m.uploaded_by
     $where ORDER BY m.created_at DESC"
)->fetch_all(MYSQLI_ASSOC);

function fmtBytes($b){ if(!$b)return'—'; $u=['B','KB','MB','GB']; $i=0; while($b>=1024&&$i<3){$b/=1024;$i++;} return round($b,1).' '.$u[$i]; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manage Materials — QCSim</title>
  <link rel="stylesheet" href="/qcsim/Assets/MainWebsite/style.css">
  <style>
    body{min-height:100vh;}
    .topbar{display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;margin-bottom:24px;}
    @media(max-width:500px){.topbar{flex-direction:column;align-items:flex-start;}}
  </style>
</head>
<body>
  <?php require_once __DIR__ . '/../includes/navbar.php'; ?>
  <div class="container" style="padding-top:32px;padding-bottom:56px;">

    <div class="page-header">
      <h1>Manage Learning Materials</h1>
      <p>Upload and manage materials for students.</p>
    </div>

    <?php if ($success): ?>
    <div class="alert alert-success"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
    <div class="alert alert-error"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- ADD BUTTON -->
    <div class="topbar">
      <span style="font-size:14px;color:var(--text-muted);"><?= count($materials) ?> material(s)</span>
      <button class="btn btn-primary" onclick="openModal('addModal')">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Upload Material
      </button>
    </div>

    <!-- TABLE -->
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>#</th><th>Title</th><th>Category</th><th>Size</th><th>Uploaded By</th><th>Date</th><th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($materials)): ?>
          <tr><td colspan="7" style="text-align:center;color:var(--text-muted);padding:32px;">No materials yet.</td></tr>
          <?php else: foreach ($materials as $i => $m): ?>
          <tr>
            <td><?= $i+1 ?></td>
            <td><strong><?= htmlspecialchars($m['title']) ?></strong><?php if($m['description']): ?><br><span style="font-size:12px;color:var(--text-muted);"><?= htmlspecialchars(mb_strimwidth($m['description'],0,60,'…')) ?></span><?php endif; ?></td>
            <td><?= $m['category'] ? '<span class="badge badge-student">'.htmlspecialchars($m['category']).'</span>' : '—' ?></td>
            <td><?= fmtBytes($m['file_size']) ?></td>
            <td><?= htmlspecialchars($m['uploader_name']) ?></td>
            <td><?= date('M j, Y', strtotime($m['created_at'])) ?></td>
            <td>
              <div class="flex gap-2">
                <button class="btn btn-outline btn-sm" onclick='openEdit(<?= json_encode($m) ?>)'>Edit</button>
                <form method="POST" onsubmit="return confirm('Delete this material?');" style="display:inline;">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id" value="<?= $m['id'] ?>">
                  <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                </form>
              </div>
            </td>
          </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- ADD MODAL -->
  <div class="modal-overlay" id="addModal">
    <div class="modal">
      <div class="modal-header">
        <h3>Upload Material</h3>
        <button class="modal-close" onclick="closeModal('addModal')">✕</button>
      </div>
      <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" value="add">
        <div class="form-group">
          <label class="form-label">Title <span style="color:var(--danger)">*</span></label>
          <input class="form-control" type="text" name="title" required placeholder="e.g. Dissolution Testing Guide">
        </div>
        <div class="form-group">
          <label class="form-label">Description</label>
          <textarea class="form-control" name="description" rows="3" placeholder="Optional description…" style="resize:vertical;"></textarea>
        </div>
        <div class="form-group">
          <label class="form-label">Category</label>
          <input class="form-control" type="text" name="category" placeholder="e.g. Module 1, References…">
        </div>
        <div class="form-group">
          <label class="form-label">File <span style="color:var(--danger)">*</span></label>
          <input class="form-control" type="file" name="material_file" required>
          <div class="form-hint">Max 50MB. Any file type accepted.</div>
        </div>
        <div class="flex gap-2" style="justify-content:flex-end;">
          <button type="button" class="btn btn-outline" onclick="closeModal('addModal')">Cancel</button>
          <button type="submit" class="btn btn-primary">Upload</button>
        </div>
      </form>
    </div>
  </div>

  <!-- EDIT MODAL -->
  <div class="modal-overlay" id="editModal">
    <div class="modal">
      <div class="modal-header">
        <h3>Edit Material</h3>
        <button class="modal-close" onclick="closeModal('editModal')">✕</button>
      </div>
      <form method="POST">
        <input type="hidden" name="action" value="edit">
        <input type="hidden" name="id" id="editId">
        <div class="form-group">
          <label class="form-label">Title <span style="color:var(--danger)">*</span></label>
          <input class="form-control" type="text" name="title" id="editTitle" required>
        </div>
        <div class="form-group">
          <label class="form-label">Description</label>
          <textarea class="form-control" name="description" id="editDesc" rows="3" style="resize:vertical;"></textarea>
        </div>
        <div class="form-group">
          <label class="form-label">Category</label>
          <input class="form-control" type="text" name="category" id="editCategory">
        </div>
        <div class="flex gap-2" style="justify-content:flex-end;">
          <button type="button" class="btn btn-outline" onclick="closeModal('editModal')">Cancel</button>
          <button type="submit" class="btn btn-primary">Save</button>
        </div>
      </form>
    </div>
  </div>

  <script>
    function openModal(id)  { document.getElementById(id).classList.add('open'); }
    function closeModal(id) { document.getElementById(id).classList.remove('open'); }
    function openEdit(m) {
      document.getElementById('editId').value       = m.id;
      document.getElementById('editTitle').value    = m.title;
      document.getElementById('editDesc').value     = m.description || '';
      document.getElementById('editCategory').value = m.category || '';
      openModal('editModal');
    }
    // Close on backdrop click
    document.querySelectorAll('.modal-overlay').forEach(o => {
      o.addEventListener('click', e => { if(e.target===o) o.classList.remove('open'); });
    });
    // Auto-open add modal on upload error
    <?php if ($error && ($_POST['action']??'')==='add'): ?>openModal('addModal');<?php endif; ?>
    <?php if ($error && ($_POST['action']??'')==='edit'): ?>openModal('editModal');<?php endif; ?>
  </script>
</body>
</html>
