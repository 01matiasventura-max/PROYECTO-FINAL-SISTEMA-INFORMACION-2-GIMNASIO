<?php
require_once __DIR__ . '/includes/init.php';
session_destroy();
header('Location: ' . BASE_URL . '/login.php');
exit;
