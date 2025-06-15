<?php
header('Content-Type: application/json');
session_start();

// Database configuration
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'quiz_db';

// Create database connection
try {
    $conn = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die(json_encode(['success' => false, 'message' => 'Database connection failed']));
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    die(json_encode(['success' => false, 'message' => 'Invalid request data']));
}

$action = $data['action'] ?? '';

switch ($action) {
    case 'register':
        handleRegistration($conn, $data);
        break;
    case 'login':
        handleLogin($conn, $data);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

function handleRegistration($conn, $data) {
    $name = $data['name'] ?? '';
    $email = $data['email'] ?? '';
    $password = $data['password'] ?? '';
    $contactNumber = $data['contactNumber'] ?? '';

    // Validate input
    if (empty($name) || empty($email) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'All fields are required']);
        return;
    }

    // Check if email already exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => false, 'message' => 'Email already registered']);
        return;
    }

    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    try {
        $stmt = $conn->prepare("INSERT INTO users (name, email, password, contact_number) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $email, $hashedPassword, $contactNumber]);
        echo json_encode(['success' => true, 'message' => 'User registered successfully']);
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Registration failed']);
    }
}

function handleLogin($conn, $data) {
    $email = $data['email'] ?? '';
    $password = $data['password'] ?? '';

    // Validate input
    if (empty($email) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'All fields are required']);
        return;
    }

    try {
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            echo json_encode(['success' => true, 'message' => 'Login successful']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid email or password']);
        }
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Login failed']);
    }
}
?> 