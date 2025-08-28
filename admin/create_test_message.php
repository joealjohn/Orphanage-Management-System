<?php
// Include database connection
include "../includes/db.php";

// Set headers for JSON response
header("Content-Type: application/json");

// Check if it's a POST request
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["create_test"])) {
    // Get form data
    $name = isset($_POST["name"]) ? mysqli_real_escape_string($conn, $_POST["name"]) : "";
    $email = isset($_POST["email"]) ? mysqli_real_escape_string($conn, $_POST["email"]) : "";
    $phone = isset($_POST["phone"]) ? mysqli_real_escape_string($conn, $_POST["phone"]) : "";
    $subject = isset($_POST["subject"]) ? mysqli_real_escape_string($conn, $_POST["subject"]) : "";
    $message = isset($_POST["message"]) ? mysqli_real_escape_string($conn, $_POST["message"]) : "";
    
    // Determine correct datetime column
    $datetime_column = "created_at";
    $columns_check = mysqli_query($conn, "SHOW COLUMNS FROM contact_messages");
    if ($columns_check) {
        while ($column = mysqli_fetch_assoc($columns_check)) {
            if (in_array($column["Field"], ["created_at", "timestamp", "sent_at", "date", "message_date"])) {
                $datetime_column = $column["Field"];
                break;
            }
        }
    }
    
    // Insert the message
    $insert_query = "INSERT INTO contact_messages (name, email, phone, subject, message, $datetime_column) 
                    VALUES ('$name', '$email', '$phone', '$subject', '$message', NOW())";
    
    if (mysqli_query($conn, $insert_query)) {
        echo json_encode(["success" => true]);
    } else {
        echo json_encode(["success" => false, "error" => mysqli_error($conn)]);
    }
} else {
    // Invalid request
    echo json_encode(["success" => false, "error" => "Invalid request method or parameters"]);
}
?>