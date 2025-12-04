<?php
session_start();

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isTeacher() {
    return isset($_SESSION['role_id']) && $_SESSION['role_id'] == 1;
}

function isStudent() {
    return isset($_SESSION['role_id']) && $_SESSION['role_id'] == 2;
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: /lms/pages/login.php');
        exit();
    }
}

function requireTeacher() {
    requireLogin();
    if (!isTeacher()) {
        header('Location: /lms/pages/dashboard.php');
        exit();
    }
}

function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}
?>