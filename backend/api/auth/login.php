<?php
/**
 * Login API Endpoint
 * Handles user and admin authentication
 */

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With');

include_once '../../config/database.php';

$database = new Database();
$db = $database->getConnection();

$data = json_decode(file_get_contents("php://input"));

if (!empty($data->username) && !empty($data->password)) {
    // Try admin login first
    $admin_query = "SELECT admin_id as id, full_name as name, email, password_hash, 'admin' as role, created_at 
                    FROM admins 
                    WHERE email = :email LIMIT 1";
    
    $admin_stmt = $db->prepare($admin_query);
    $admin_stmt->bindParam(':email', $data->username);
    $admin_stmt->execute();
    $admin = $admin_stmt->fetch(PDO::FETCH_ASSOC);

    if ($admin && password_verify($data->password, $admin['password_hash'])) {
        // Create admin session
        $token = bin2hex(random_bytes(32));
        $expires_at = date('Y-m-d H:i:s', strtotime('+24 hours'));
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;

        $session_query = "INSERT INTO admin_sessions (admin_id, token, expires_at, ip_address) 
                         VALUES (:admin_id, :token, :expires_at, :ip_address)";
        $session_stmt = $db->prepare($session_query);
        $session_stmt->bindParam(':admin_id', $admin['id']);
        $session_stmt->bindParam(':token', $token);
        $session_stmt->bindParam(':expires_at', $expires_at);
        $session_stmt->bindParam(':ip_address', $ip_address);
        $session_stmt->execute();

        unset($admin['password_hash']);
        $admin['token'] = $token;
        $admin['userType'] = 'admin';

        http_response_code(200);
        echo json_encode([
            'success' => true,
            'user' => $admin
        ]);
        exit;
    }

    // Try user login
    $user_query = "SELECT u.user_id as id, u.first_name, u.last_name, u.email, u.password_hash, 
                   r.role_name as role, u.created_at, u.phone
                   FROM users u
                   JOIN roles r ON u.role_id = r.role_id
                   WHERE u.email = :email LIMIT 1";
    
    $user_stmt = $db->prepare($user_query);
    $user_stmt->bindParam(':email', $data->username);
    $user_stmt->execute();
    $user = $user_stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($data->password, $user['password_hash'])) {
        // Create user session
        $token = bin2hex(random_bytes(32));
        $expires_at = date('Y-m-d H:i:s', strtotime('+24 hours'));
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;

        $session_query = "INSERT INTO login_sessions (user_id, token, expires_at, ip_address) 
                         VALUES (:user_id, :token, :expires_at, :ip_address)";
        $session_stmt = $db->prepare($session_query);
        $session_stmt->bindParam(':user_id', $user['id']);
        $session_stmt->bindParam(':token', $token);
        $session_stmt->bindParam(':expires_at', $expires_at);
        $session_stmt->bindParam(':ip_address', $ip_address);
        $session_stmt->execute();

        unset($user['password_hash']);
        $user['token'] = $token;
        $user['userType'] = 'user';
        $user['profile'] = [
            'firstName' => $user['first_name'],
            'lastName' => $user['last_name'],
            'phone' => $user['phone']
        ];
        unset($user['first_name'], $user['last_name'], $user['phone']);

        http_response_code(200);
        echo json_encode([
            'success' => true,
            'user' => $user
        ]);
        exit;
    }

    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid credentials'
    ]);
} else {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Incomplete data'
    ]);
}
?>