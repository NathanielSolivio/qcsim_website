<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Virtual Lab — QCSim</title>
  <link rel="stylesheet" href="/qcsim/Assets/MainWebsite/style.css">
  <style>
    body { display:flex; flex-direction:column; min-height:100vh; }
    .lab-container { flex:1; display:flex; align-items:center; justify-content:center; }
    .placeholder { text-align:center; color:var(--text-muted); }
    .placeholder svg { opacity:.25; margin-bottom:16px; }
    .placeholder h2 { font-family:'Raleway',sans-serif; font-size:22px; font-weight:800; color:var(--text); margin-bottom:6px; }
  </style>
</head>
<body>
  <?php require_once __DIR__ . '/../includes/navbar.php'; ?>
  <div class="lab-container">
    <div class="placeholder">
      <svg width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="var(--primary)" stroke-width="1.5">
        <path d="M9 3H5a2 2 0 0 0-2 2v4m6-6h10a2 2 0 0 1 2 2v4M9 3v11m0 0H5a2 2 0 0 0-2 2v4a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-4a2 2 0 0 0-2-2h-4m-4 0v-11"/>
      </svg>
      <h2>Virtual Lab</h2>
      <p>This page is under construction. Check back soon!</p>
    </div>
  </div>
</body>
</html>
