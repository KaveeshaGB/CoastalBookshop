<?php
require_once __DIR__.'/config.php';

if (empty($_SESSION['is_admin'])) {
    header('Location: home.php');
    exit;
}