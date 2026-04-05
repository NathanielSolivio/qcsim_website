<?php
// includes/navbar.php — call after session_start() and auth check
$user = getCurrentUser();
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<nav class="navbar">
  <a href="/qcsim/index.php" class="navbar-brand">
    <img src="/qcsim/Assets/MainWebsite/logo.png" alt="QCSim">
    QCSim
  </a>
  <button class="nav-toggle" id="navToggle" aria-label="Toggle navigation">
    <span></span><span></span><span></span>
  </button>
  <ul class="navbar-links" id="navLinks">
    <li><a href="/qcsim/index.php" <?= $currentPage==='index.php'?'class="active"':'' ?>>Home</a></li>
    <li><a href="/qcsim/pages/learning_materials.php" <?= $currentPage==='learning_materials.php'?'class="active"':'' ?>>Learning Materials</a></li>
    <li><a href="/qcsim/pages/virtual_lab.php" <?= $currentPage==='virtual_lab.php'?'class="active"':'' ?>>Virtual Labs</a></li>
    <?php if (in_array($user['role'], ['instructor','admin'])): ?>
    <li><a href="/qcsim/pages/manage_materials.php" <?= $currentPage==='manage_materials.php'?'class="active"':'' ?>>Manage Materials</a></li>
    <?php endif; ?>
    <?php if ($user['role'] === 'admin'): ?>
    <li><a href="/qcsim/pages/manage_users.php" <?= $currentPage==='manage_users.php'?'class="active"':'' ?>>Manage Users</a></li>
    <?php endif; ?>
    <li><a href="/qcsim/pages/profile.php" <?= $currentPage==='profile.php'?'class="active"':'' ?>>Profile</a></li>
    <li><a href="/qcsim/logout.php" class="nav-signout">Sign Out</a></li>
  </ul>
</nav>
<script>
  document.getElementById('navToggle').addEventListener('click', () => {
    document.getElementById('navLinks').classList.toggle('open');
  });
</script>
