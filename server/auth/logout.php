<?php
require_once __DIR__ . '/../db.php';
if (session_status() === PHP_SESSION_NONE) session_start();
session_destroy();
header('Location: ' . ROOT_URL . '/auth/login.php');
exit;
