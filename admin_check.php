<?php
// Put "require 'admin_check.php';" at the very top of every admin page.
require_once 'config.php';

if (empty($_SESSION['is_admin'])) {
    header('Location: home.php');
    exit;
}