<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Function to check if user is admin
function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] == 'admin';
}

// Function to ensure user is logged in, redirect if not
function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: ../login.php");
        exit();
    }
}

// Function to ensure user is admin, redirect if not
function requireAdmin() {
    if (!isLoggedIn() || !isAdmin()) {
        header("Location: ../login.php");
        exit();
    }
}

// Function to redirect to dashboard based on role
function redirectToDashboard() {
    if (isAdmin()) {
        header("Location: admin/dashboard.php");
    } else {
        header("Location: users/dashboard.php");
    }
    exit();
}
?>