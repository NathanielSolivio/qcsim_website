<?php
session_start();
session_destroy();
header('Location: /qcsim/login.php');
exit;
