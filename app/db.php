<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "orl_db";

// Establish a connection to the MySQL database
$conn = new mysqli($servername, $username, $password, $dbname);

// Check the connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Whitelist of allowed table names
// $allowed_tables = ['ann', 'table2', 'table3']; // Add your actual table names here

// Sanitize table name
$table = $_GET['table'];


// Function to sanitize input data
function sanitizeInput($data) {
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}


// Handle POST request
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    $action = $_POST['action'];
    switch ($action) {
        case 'create':
            $text = sanitizeInput($_POST['text']);
            $stmt = $conn->prepare("INSERT INTO `$table` (text) VALUES (?)");
            $stmt->bind_param("s", $text);
            $stmt->execute();
            $stmt->close();
            break;

        case 'delete':
            $id = intval($_POST['id']);
            $stmt = $conn->prepare("DELETE FROM `$table` WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();
            break;

        case 'update':
            $id = intval($_POST['id']);
            $text = sanitizeInput($_POST['text']);
            $stmt = $conn->prepare("UPDATE `$table` SET text = ? WHERE id = ?");
            $stmt->bind_param("si", $text, $id);
            $stmt->execute();
            $stmt->close();
            break;
    }
} else {
    if($table === 'channels'){
        $stmt = $conn->prepare("SELECT * FROM `$table` ORDER BY `id` ASC");
    }else{
        $stmt = $conn->prepare("SELECT * FROM `$table` ORDER BY `id` DESC");
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $messages = [];
    while ($row = $result->fetch_assoc()) {
        $messages[] = $row;
    }
    header('Content-Type: application/json');
    echo json_encode($messages);
    $stmt->close();
}

$conn->close();
?>
