<?php
/**
 * Register API Endpoint
 * Handles user registration
 */

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With');

include_once '../../config/database.php';

$database = new Database();
$db = $database->getConnection();

$data = json_decode(file_get_contents("php://input"));

if (!empty($data->username) && !empty($data->email) && !empty($data->password) && 
    !empty($data->firstName) && !empty($data->lastName)) {
    
    // Check if email already exists
    $check_query = "SELECT user_id FROM users WHERE email = :email LIMIT 1";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->bindParam(':email', $data->email);
    $check_stmt->execute();

    if ($check_stmt->rowCount() > 0) {
        http_response_code(409);
        echo json_encode([
            'success' => false,
            'message' => 'Email already exists'
        ]);
        exit;
    }

    // Hash password
    $password_hash = password_hash($data->password, PASSWORD_DEFAULT);

    // Get default user role (role_id = 2 for 'user')
    $role_query = "SELECT role_id FROM roles WHERE role_name = 'user' LIMIT 1";
    $role_stmt = $db->prepare($role_query);
    $role_stmt->execute();
    $role = $role_stmt->fetch(PDO::FETCH_ASSOC);
    $role_id = $role ? $role['role_id'] : 2;

    // Insert new user
    $query = "INSERT INTO users (role_id, first_name, last_name, email, phone, password_hash) 
              VALUES (:role_id, :first_name, :last_name, :email, :phone, :password_hash)";

    $stmt = $db->prepare($query);

    // Clean data
    $first_name = htmlspecialchars(strip_tags($data->firstName));
    $last_name = htmlspecialchars(strip_tags($data->lastName));
    $email = htmlspecialchars(strip_tags($data->email));
    $phone = !empty($data->phone) ? htmlspecialchars(strip_tags($data->phone)) : null;

    // Bind parameters
    $stmt->bindParam(':role_id', $role_id);
    $stmt->bindParam(':first_name', $first_name);
    $stmt->bindParam(':last_name', $last_name);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':phone', $phone);
    $stmt->bindParam(':password_hash', $password_hash);

    if ($stmt->execute()) {
        http_response_code(201);
        echo json_encode([
            'success' => true,
            'message' => 'User registered successfully'
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Unable to register user'
        ]);
    }
} else {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Incomplete data'
    ]);
}
?>