<?php
/**
 * Concern Reports API Endpoint
 * Handles concern report CRUD operations
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
        // Get all concerns or specific concern
        if (isset($_GET['id'])) {
            $query = "SELECT cr.*, 
                     CONCAT(u.first_name, ' ', u.last_name) as reported_by_name,
                     cc.category_name,
                     l.building_name, l.floor_level, l.room_area,
                     r.role_name as reporter_role
                     FROM concern_reports cr
                     JOIN users u ON cr.user_id = u.user_id
                     JOIN concern_categories cc ON cr.category_id = cc.category_id
                     JOIN locations l ON cr.location_id = l.location_id
                     JOIN roles r ON u.role_id = r.role_id
                     WHERE cr.report_id = :id LIMIT 1";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $_GET['id']);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($row) {
                $concern = formatConcern($row);
                echo json_encode(['success' => true, 'concern' => $concern]);
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Concern not found']);
            }
        } else {
            // Handle filters
            $where = ["1=1"];
            $params = [];

            if (isset($_GET['category'])) {
                $where[] = "cc.category_name = :category";
                $params[':category'] = $_GET['category'];
            }
            if (isset($_GET['status'])) {
                $where[] = "cr.status = :status";
                $params[':status'] = $_GET['status'];
            }
            if (isset($_GET['user_id'])) {
                $where[] = "cr.user_id = :user_id";
                $params[':user_id'] = $_GET['user_id'];
            }
            if (isset($_GET['search'])) {
                $where[] = "(cr.title LIKE :search OR cr.description LIKE :search)";
                $params[':search'] = '%' . $_GET['search'] . '%';
            }

            $query = "SELECT cr.*, 
                     CONCAT(u.first_name, ' ', u.last_name) as reported_by_name,
                     cc.category_name,
                     l.building_name, l.floor_level, l.room_area,
                     r.role_name as reporter_role
                     FROM concern_reports cr
                     JOIN users u ON cr.user_id = u.user_id
                     JOIN concern_categories cc ON cr.category_id = cc.category_id
                     JOIN locations l ON cr.location_id = l.location_id
                     JOIN roles r ON u.role_id = r.role_id
                     WHERE " . implode(' AND ', $where) . "
                     ORDER BY cr.created_at DESC";

            $stmt = $db->prepare($query);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $concerns = array_map('formatConcern', $rows);
            echo json_encode(['success' => true, 'concerns' => $concerns]);
        }
        break;

    case 'POST':
        // Create new concern
        $data = json_decode(file_get_contents("php://input"));

        $required_fields = ['title', 'description', 'category', 'severity', 'location_id', 'user_id', 'concern_date'];
        foreach ($required_fields as $field) {
            if (empty($data->$field)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => "Missing required field: $field"]);
                exit;
            }
        }

        // Get category_id from category_name
        $category_query = "SELECT category_id FROM concern_categories WHERE category_name = :category LIMIT 1";
        $category_stmt = $db->prepare($category_query);
        $category_stmt->bindParam(':category', $data->category);
        $category_stmt->execute();
        $category = $category_stmt->fetch(PDO::FETCH_ASSOC);

        if (!$category) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid category']);
            exit;
        }

        $query = "INSERT INTO concern_reports (user_id, category_id, location_id, title, description, severity, status, concern_date) 
                  VALUES (:user_id, :category_id, :location_id, :title, :description, :severity, 'pending', :concern_date)";

        $stmt = $db->prepare($query);

        // Clean and prepare data
        $title = htmlspecialchars(strip_tags($data->title));
        $description = htmlspecialchars(strip_tags($data->description));
        $severity = $data->severity;
        $location_id = $data->location_id;
        $user_id = $data->user_id;
        $category_id = $category['category_id'];
        $concern_date = $data->concern_date;

        $stmt->bindParam(':title', $title);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':severity', $severity);
        $stmt->bindParam(':location_id', $location_id);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':category_id', $category_id);
        $stmt->bindParam(':concern_date', $concern_date);

        if ($stmt->execute()) {
            $concern_id = $db->lastInsertId();
            
            // Fetch the created concern
            $query = "SELECT cr.*, 
                     CONCAT(u.first_name, ' ', u.last_name) as reported_by_name,
                     cc.category_name,
                     l.building_name, l.floor_level, l.room_area,
                     r.role_name as reporter_role
                     FROM concern_reports cr
                     JOIN users u ON cr.user_id = u.user_id
                     JOIN concern_categories cc ON cr.category_id = cc.category_id
                     JOIN locations l ON cr.location_id = l.location_id
                     JOIN roles r ON u.role_id = r.role_id
                     WHERE cr.report_id = :id LIMIT 1";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $concern_id);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $concern = formatConcern($row);
            
            http_response_code(201);
            echo json_encode(['success' => true, 'concern' => $concern]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Unable to create concern']);
        }
        break;

    case 'PUT':
        // Update concern
        $data = json_decode(file_get_contents("php://input"));

        if (!empty($data->id)) {
            // Check if concern exists
            $check_query = "SELECT report_id FROM concern_reports WHERE report_id = :id LIMIT 1";
            $check_stmt = $db->prepare($check_query);
            $check_stmt->bindParam(':id', $data->id);
            $check_stmt->execute();

            if ($check_stmt->rowCount() === 0) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Concern not found']);
                exit;
            }

            // Build update query dynamically
            $update_fields = [];
            $params = [];

            if (isset($data->title)) {
                $update_fields[] = "title = :title";
                $params[':title'] = htmlspecialchars(strip_tags($data->title));
            }
            if (isset($data->description)) {
                $update_fields[] = "description = :description";
                $params[':description'] = htmlspecialchars(strip_tags($data->description));
            }
            if (isset($data->severity)) {
                $update_fields[] = "severity = :severity";
                $params[':severity'] = $data->severity;
            }
            if (isset($data->status)) {
                $update_fields[] = "status = :status";
                $params[':status'] = $data->status;
            }
            if (isset($data->category)) {
                $category_query = "SELECT category_id FROM concern_categories WHERE category_name = :category LIMIT 1";
                $category_stmt = $db->prepare($category_query);
                $category_stmt->bindParam(':category', $data->category);
                $category_stmt->execute();
                $category = $category_stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($category) {
                    $update_fields[] = "category_id = :category_id";
                    $params[':category_id'] = $category['category_id'];
                }
            }
            if (isset($data->location_id)) {
                $update_fields[] = "location_id = :location_id";
                $params[':location_id'] = $data->location_id;
            }

            if (empty($update_fields)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'No fields to update']);
                exit;
            }

            $query = "UPDATE concern_reports SET " . implode(', ', $update_fields) . " WHERE report_id = :id";
            $params[':id'] = $data->id;

            $stmt = $db->prepare($query);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }

            if ($stmt->execute()) {
                // Fetch updated concern
                $query = "SELECT cr.*, 
                         CONCAT(u.first_name, ' ', u.last_name) as reported_by_name,
                         cc.category_name,
                         l.building_name, l.floor_level, l.room_area,
                         r.role_name as reporter_role
                         FROM concern_reports cr
                         JOIN users u ON cr.user_id = u.user_id
                         JOIN concern_categories cc ON cr.category_id = cc.category_id
                         JOIN locations l ON cr.location_id = l.location_id
                         JOIN roles r ON u.role_id = r.role_id
                         WHERE cr.report_id = :id LIMIT 1";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':id', $data->id);
                $stmt->execute();
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                
                $concern = formatConcern($row);
                
                echo json_encode(['success' => true, 'concern' => $concern]);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Unable to update concern']);
            }
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Incomplete data']);
        }
        break;

    case 'DELETE':
        // Delete concern
        if (isset($_GET['id'])) {
            $query = "DELETE FROM concern_reports WHERE report_id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $_GET['id']);

            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Concern deleted successfully']);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Unable to delete concern']);
            }
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Concern ID required']);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        break;
}

function formatConcern($row) {
    return [
        'id' => $row['report_id'],
        'title' => $row['title'],
        'description' => $row['description'],
        'category' => $row['category_name'],
        'severity' => $row['severity'],
        'status' => $row['status'],
        'location' => [
            'id' => $row['location_id'],
            'building' => $row['building_name'],
            'floor' => $row['floor_level'],
            'room' => $row['room_area']
        ],
        'reportedBy' => $row['reported_by_name'],
        'reporterRole' => $row['reporter_role'],
        'concernDate' => $row['concern_date'],
        'createdAt' => $row['created_at']
    ];
}
?>