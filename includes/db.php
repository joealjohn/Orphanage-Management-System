<?php
// Database Connection
$host = "localhost";
$username = "root"; // default XAMPP username
$password = "";     // default XAMPP password
$database = "orphanage_management";

// Create connection
$conn = mysqli_connect($host, $username, $password, $database);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
?>