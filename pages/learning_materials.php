<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

// Fetch materials
$category = trim($_GET['category'] ?? '');
$search   = trim($_GET['search']   ?? '');

$sql = "SELECT m.*, CONCAT(u.first_name,' ',u.last_name) AS uploader_name
        FROM learningMaterialsTable m
        JOIN users u ON u.id = m.uploaded_by
        WHERE 1=1";
$params = []; $types = '';

if ($search) {
    $sql .= " AND (m.title LIKE ? OR m.description LIKE ?)";
    $like = "%$search%"; $params[] = $like; $params[] = $like; $types .= 'ss';
}
if ($category) {
    $sql .= " AND m.category = ?";
    $params[] = $category; $types .= 's';
}
$sql .= " ORDER BY m.created_at DESC";

$stmt = $conn->prepare($sql);
if ($params) { $stmt->bind_param($types, ...$params); }
$stmt->execute();
$materials = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// All categories for filter
$cats = $conn->query("SELECT DISTINCT category FROM learningMaterialsTable WHERE category IS NOT NULL AND category != '' ORDER BY category")->fetch_all(MYSQLI_ASSOC);

function formatBytes($bytes) {
    if (!$bytes) return '—';
    $u = ['B','KB','MB','GB']; $i = 0;
    while ($bytes >= 1024 && $i < 3) { $bytes /= 1024; $i++; }
    return round($bytes,1).' '.$u[$i];
}
function fileIcon($type) {
    if (!$type) return '📄';
    if (str_contains($type,'pdf'))   return '📕';
    if (str_contains($type,'word') || str_contains($type,'doc')) return '📘';
    if (str_contains($type,'sheet') || str_contains($type,'excel') || str_contains($type,'csv')) return '📗';
    if (str_contains($type,'ppt') || str_contains($type,'presentation')) return '📙';
    if (str_contains($type,'image') || str_contains($type,'png') || str_contains($type,'jpg')) return '🖼️';
    if (str_contains($type,'video')) return '🎥';
    if (str_contains($type,'zip') || str_contains($type,'archive')) return '🗜️';
    return '📄';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Learning Materials — QCSim</title>
  <link rel="stylesheet" href="/qcsim/Assets/MainWebsite/style.css">
  <style>
    body { min-height:100vh; }
    .filters {
      display:flex; gap:10px; flex-wrap:wrap; align-items:center;
      margin-bottom:20px;
    }
    .filters input, .filters select {
      flex:1; min-width:160px; max-width:280px;
      padding:9px 14px; border:1.5px solid var(--border); border-radius:var(--radius);
      font-family:'Nunito',sans-serif; font-size:14px; color:var(--text);
      background:#fff; outline:none;
    }
    .filters input:focus, .filters select:focus { border-color:var(--primary); }
    .mat-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(280px,1fr)); gap:18px; }
    .mat-card {
      background:#fff; border-radius:14px; border:1.5px solid var(--border);
      box-shadow:var(--shadow); padding:22px; display:flex; flex-direction:column; gap:10px;
      transition:.2s;
    }
    .mat-card:hover { box-shadow:var(--shadow-lg); transform:translateY(-2px); }
    .mat-icon { font-size:32px; }
    .mat-title { font-weight:700; font-size:15px; color:var(--text); }
    .mat-desc  { font-size:13px; color:var(--text-muted); line-height:1.5; flex:1; }
    .mat-meta  { font-size:12px; color:var(--text-muted); display:flex; gap:12px; flex-wrap:wrap; }
    .mat-footer{ display:flex; justify-content:space-between; align-items:center; }
    .empty-state { text-align:center; padding:64px 0; color:var(--text-muted); }
    .empty-state svg { opacity:.3; margin-bottom:12px; }
    @media(max-width:500px){ .filters input,.filters select{max-width:100%;} }
  </style>
</head>
<body>
  <?php require_once __DIR__ . '/../includes/navbar.php'; ?>
  <div class="container" style="padding-top:32px; padding-bottom:48px;">

    <div class="page-header">
      <h1>Learning Materials</h1>
      <p>Browse and download study guides, modules, and references.</p>
    </div>

    <!-- FILTERS -->
    <form method="GET" class="filters">
      <input type="text" name="search" placeholder="🔍  Search materials…" value="<?= htmlspecialchars($search) ?>">
      <select name="category">
        <option value="">All Categories</option>
        <?php foreach ($cats as $c): ?>
          <option value="<?= htmlspecialchars($c['category']) ?>" <?= $category===$c['category']?'selected':'' ?>>
            <?= htmlspecialchars($c['category']) ?>
          </option>
        <?php endforeach; ?>
      </select>
      <button type="submit" class="btn btn-primary btn-sm">Filter</button>
      <?php if ($search || $category): ?>
        <a href="/qcsim/pages/learning_materials.php" class="btn btn-outline btn-sm">Clear</a>
      <?php endif; ?>
    </form>

    <!-- GRID -->
    <?php if (empty($materials)): ?>
      <div class="empty-state">
        <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="var(--primary)" stroke-width="1.5"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
        <p>No learning materials found<?= ($search||$category)?' for your filters':'' ?>.</p>
      </div>
    <?php else: ?>
      <div class="mat-grid">
        <?php foreach ($materials as $m): ?>
        <div class="mat-card">
          <div class="mat-icon"><?= fileIcon($m['file_type']) ?></div>
          <div class="mat-title"><?= htmlspecialchars($m['title']) ?></div>
          <?php if ($m['description']): ?>
            <div class="mat-desc"><?= htmlspecialchars(mb_strimwidth($m['description'],0,120,'…')) ?></div>
          <?php endif; ?>
          <div class="mat-meta">
            <?php if ($m['category']): ?><span>📂 <?= htmlspecialchars($m['category']) ?></span><?php endif; ?>
            <span>📦 <?= formatBytes($m['file_size']) ?></span>
            <span>👤 <?= htmlspecialchars($m['uploader_name']) ?></span>
          </div>
          <div class="mat-footer">
            <span style="font-size:12px;color:var(--text-muted);"><?= date('M j, Y', strtotime($m['created_at'])) ?></span>
            <a href="/qcsim/<?= htmlspecialchars($m['file_path']) ?>" class="btn btn-primary btn-sm" download>
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
              Download
            </a>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</body>
</html>
