<?php
/**
 * Users API Endpoint
 * Handles user management operations
 */

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With');

include_once '../../config/database.php';

$database = new Database();
$db = $database->getConnection();

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // Get all users or specific user
        if (isset($_GET['id'])) {
            $query = "SELECT u.user_id as id, u.first_name, u.last_name, u.email, u.phone, 
                     r.role_name as role, u.created_at
                     FROM users u
                     JOIN roles r ON u.role_id = r.role_id
                     WHERE u.user_id = :id LIMIT 1";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $_GET['id']);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($row) {
                $user = formatUser($row);
                echo json_encode(['success' => true, 'user' => $user]);
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'User not found']);
            }
        } else {
            $query = "SELECT u.user_id as id, u.first_name, u.last_name, u.email, u.phone, 
                     r.role_name as role, u.created_at
                     FROM users u
                     JOIN roles r ON u.role_id = r.role_id
                     ORDER BY u.created_at DESC";
            $stmt = $db->prepare($query);
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $users = array_map('formatUser', $rows);
            echo json_encode(['success' => true, 'users' => $users]);
        }
        break;

    case 'PUT':
        // Update user
        $data = json_decode(file_get_contents("php://input"));

        if (!empty($data->id)) {
            // Check if user exists
            $check_query = "SELECT user_id, role_id FROM users WHERE user_id = :id LIMIT 1";
            $check_stmt = $db->prepare($check_query);
            $check_stmt->bindParam(':id', $data->id);
            $check_stmt->execute();
            $existing_user = $check_stmt->fetch(PDO::FETCH_ASSOC);

            if (!$existing_user) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'User not found']);
                exit;
            }

            // Check for email conflicts
            if (isset($data->email)) {
                $conflict_query = "SELECT user_id FROM users WHERE email = :email AND user_id != :id LIMIT 1";
                $conflict_stmt = $db->prepare($conflict_query);
                $conflict_stmt->bindParam(':email', $data->email);
                $conflict_stmt->bindParam(':id', $data->id);
                $conflict_stmt->execute();

                if ($conflict_stmt->rowCount() > 0) {
                    http_response_code(409);
                    echo json_encode(['success' => false, 'message' => 'Email already exists']);
                    exit;
                }
            }

            // Build update query dynamically
            $update_fields = [];
            $params = [];

            if (isset($data->firstName)) {
                $update_fields[] = "first_name = :first_name";
                $params[':first_name'] = htmlspecialchars(strip_tags($data->firstName));
            }
            if (isset($data->lastName)) {
                $update_fields[] = "last_name = :last_name";
                $params[':last_name'] = htmlspecialchars(strip_tags($data->lastName));
            }
            if (isset($data->email)) {
                $update_fields[] = "email = :email";
                $params[':email'] = htmlspecialchars(strip_tags($data->email));
            }
            if (isset($data->phone)) {
                $update_fields[] = "phone = :phone";
                $params[':phone'] = htmlspecialchars(strip_tags($data->phone));
            }
            if (isset($data->password)) {
                $update_fields[] = "password_hash = :password_hash";
                $params[':password_hash'] = password_hash($data->password, PASSWORD_DEFAULT);
            }
            if (isset($data->role)) {
                // Get role_id from role_name
                $role_query = "SELECT role_id FROM roles WHERE role_name = :role LIMIT 1";
                $role_stmt = $db->prepare($role_query);
                $role_stmt->bindParam(':role', $data->role);
                $role_stmt->execute();
                $role = $role_stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($role) {
                    $update_fields[] = "role_id = :role_id";
                    $params[':role_id'] = $role['role_id'];
                }
            }

            if (empty($update_fields)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'No fields to update']);
                exit;
            }

            $query = "UPDATE users SET " . implode(', ', $update_fields) . " WHERE user_id = :id";
            $params[':id'] = $data->id;

            $stmt = $db->prepare($query);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }

            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'User updated successfully']);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Unable to update user']);
            }
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Incomplete data']);
        }
        break;

    case 'DELETE':
        // Delete user
        if (isset($_GET['id'])) {
            $query = "DELETE FROM users WHERE user_id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $_GET['id']);

            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'User deleted successfully']);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Unable to delete user']);
            }
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'User ID required']);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        break;
}

function formatUser($row) {
    return [
        'id' => $row['id'],
        'username' => $row['email'], // Using email as username for compatibility
        'email' => $row['email'],
        'role' => $row['role'],
        'profile' => [
            'firstName' => $row['first_name'],
            'lastName' => $row['last_name'],
            'phone' => $row['phone']
        ],
        'createdAt' => $row['created_at'],
        'isActive' => true
    ];
}
?>