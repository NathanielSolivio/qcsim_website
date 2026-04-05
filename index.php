<?php
session_start();
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
requireLogin();
$user = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Home — QCSim</title>
  <link rel="stylesheet" href="/qcsim/Assets/MainWebsite/style.css">
  <style>
    .hero {
      position: relative; overflow: hidden;
      height: calc(100vh - var(--nav-height));
      min-height: 500px;
    }
    .hero-bg {
      position: absolute; inset: 0;
      background: url('/qcsim/Assets/MainWebsite/bg_home.png') center/cover no-repeat;
    }
    .hero-bg::after {
      content:''; position:absolute; inset:0;
      background: linear-gradient(135deg,rgba(232,243,252,.70) 0%,rgba(168,216,255,.30) 100%);
    }
    .hero-content {
      position: relative; z-index:1;
      height: 100%;
      display: flex; flex-direction: column;
      align-items: flex-start; justify-content: center;
      padding: 0 8%;
      max-width: 680px;
    }
    .hero-content h1 {
      font-family:'Raleway',sans-serif; font-size:clamp(28px,4vw,50px);
      font-weight:900; color:var(--text); line-height:1.15;
      margin-bottom:16px;
    }
    .hero-content h1 span { color:var(--primary); }
    .hero-content p {
      font-size:clamp(14px,1.5vw,17px); color:var(--text-muted);
      line-height:1.7; margin-bottom:28px; max-width:520px;
    }
    .hero-actions { display:flex; gap:12px; flex-wrap:wrap; }

    /* Quick nav cards */
    .quick-nav { padding:48px 0; }
    .quick-nav h2 { font-family:'Raleway',sans-serif; font-size:22px; font-weight:800; margin-bottom:24px; }
    .cards-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:20px; }
    .nav-card {
      background:#fff; border-radius:14px; border:1.5px solid var(--border);
      padding:28px 24px; text-decoration:none; color:var(--text);
      transition:.2s; box-shadow:var(--shadow);
      display:flex; flex-direction:column; gap:10px;
    }
    .nav-card:hover { transform:translateY(-4px); box-shadow:var(--shadow-lg); text-decoration:none; }
    .nav-card-icon {
      width:48px; height:48px; border-radius:12px;
      background:var(--primary-light); display:flex; align-items:center; justify-content:center;
      color:var(--primary);
    }
    .nav-card h3 { font-size:16px; font-weight:700; }
    .nav-card p  { font-size:13px; color:var(--text-muted); line-height:1.5; }

    @media(max-width:600px){
      .hero-content{ padding:0 20px; }
    }
  </style>
</head>
<body>
  <?php require_once __DIR__ . '/includes/navbar.php'; ?>

  <!-- HERO -->
  <section class="hero">
    <div class="hero-bg"></div>
    <div class="hero-content">
      <h1>Your Virtual <span>Pharmaceutical</span> Laboratory</h1>
      <p>Practice real-world quality control procedures, access learning materials, and sharpen your analytical skills — all from your browser.</p>
      <div class="hero-actions">
        <a href="/qcsim/pages/virtual_lab.php" class="btn btn-primary">Enter Virtual Lab</a>
        <a href="/qcsim/pages/learning_materials.php" class="btn btn-outline">Browse Materials</a>
      </div>
    </div>
  </section>

  <!-- QUICK NAV -->
  <section class="quick-nav">
    <div class="container">
      <h2>Quick Access</h2>
      <div class="cards-grid">

        <a href="/qcsim/pages/learning_materials.php" class="nav-card">
          <div class="nav-card-icon">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
          </div>
          <h3>Learning Materials</h3>
          <p>Access uploaded study guides, modules, and references shared by your instructors.</p>
        </a>

        <a href="/qcsim/pages/virtual_lab.php" class="nav-card">
          <div class="nav-card-icon">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 3H5a2 2 0 0 0-2 2v4m6-6h10a2 2 0 0 1 2 2v4M9 3v11m0 0H5a2 2 0 0 0-2 2v4a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-4a2 2 0 0 0-2-2h-4m-4 0v-11"/></svg>
          </div>
          <h3>Virtual Labs</h3>
          <p>Simulate pharmaceutical QC procedures in an interactive virtual environment.</p>
        </a>

        <a href="/qcsim/pages/profile.php" class="nav-card">
          <div class="nav-card-icon">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
          </div>
          <h3>My Profile</h3>
          <p>View and update your personal information and account settings.</p>
        </a>

        <?php if (in_array($user['role'], ['instructor','admin'])): ?>
        <a href="/qcsim/pages/manage_materials.php" class="nav-card">
          <div class="nav-card-icon" style="background:#e9d8fd;color:#6b46c1;">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
          </div>
          <h3>Manage Materials</h3>
          <p>Upload, edit, or remove learning materials for your students.</p>
        </a>
        <?php endif; ?>

        <?php if ($user['role'] === 'admin'): ?>
        <a href="/qcsim/pages/manage_users.php" class="nav-card">
          <div class="nav-card-icon" style="background:#fed7d7;color:#c53030;">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
          </div>
          <h3>Manage Users</h3>
          <p>Add, edit, or remove user accounts and manage roles.</p>
        </a>
        <?php endif; ?>

      </div>
    </div>
  </section>

</body>
</html>
