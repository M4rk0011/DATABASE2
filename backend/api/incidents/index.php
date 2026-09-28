<?php
/**
 * Incidents API Endpoint
 * Handles incident CRUD operations
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
        // Get all incidents or specific incident
        if (isset($_GET['id'])) {
            $query = "SELECT i.*, 
                     u1.username as reported_by_username,
                     u2.username as assigned_to_username
                     FROM incidents i
                     LEFT JOIN users u1 ON i.reported_by = u1.id
                     LEFT JOIN users u2 ON i.assigned_to = u2.id
                     WHERE i.id = :id LIMIT 1";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $_GET['id']);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($row) {
                $incident = formatIncident($row);
                echo json_encode(['success' => true, 'incident' => $incident]);
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Incident not found']);
            }
        } else {
            // Handle filters
            $where = ["1=1"];
            $params = [];

            if (isset($_GET['category'])) {
                $where[] = "category = :category";
                $params[':category'] = $_GET['category'];
            }
            if (isset($_GET['status'])) {
                $where[] = "status = :status";
                $params[':status'] = $_GET['status'];
            }
            if (isset($_GET['reported_by'])) {
                $where[] = "reported_by = :reported_by";
                $params[':reported_by'] = $_GET['reported_by'];
            }
            if (isset($_GET['search'])) {
                $where[] = "(title LIKE :search OR description LIKE :search OR address LIKE :search)";
                $params[':search'] = '%' . $_GET['search'] . '%';
            }

            $query = "SELECT i.*, 
                     u1.username as reported_by_username,
                     u2.username as assigned_to_username
                     FROM incidents i
                     LEFT JOIN users u1 ON i.reported_by = u1.id
                     LEFT JOIN users u2 ON i.assigned_to = u2.id
                     WHERE " . implode(' AND ', $where) . "
                     ORDER BY i.reported_at DESC";

            $stmt = $db->prepare($query);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $incidents = array_map('formatIncident', $rows);
            echo json_encode(['success' => true, 'incidents' => $incidents]);
        }
        break;

    case 'POST':
        // Create new incident
        $data = json_decode(file_get_contents("php://input"));

        $required_fields = ['title', 'description', 'category', 'severity', 'latitude', 'longitude', 'address', 'reported_by'];
        foreach ($required_fields as $field) {
            if (empty($data->$field)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => "Missing required field: $field"]);
                exit;
            }
        }

        // Validate campus boundaries
        if (!isWithinCampusBounds($data->latitude, $data->longitude)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Incident location is outside campus boundaries']);
            exit;
        }

        $query = "INSERT INTO incidents (title, description, category, severity, status, latitude, longitude, address, building, floor, room, landmark, reported_by, contact_name, contact_email, contact_phone, contact_student_id, contact_department, additional_notes, is_anonymous, images, witnesses) 
                  VALUES (:title, :description, :category, :severity, 'reported', :latitude, :longitude, :address, :building, :floor, :room, :landmark, :reported_by, :contact_name, :contact_email, :contact_phone, :contact_student_id, :contact_department, :additional_notes, :is_anonymous, :images, :witnesses)";

        $stmt = $db->prepare($query);

        // Clean and prepare data
        $title = htmlspecialchars(strip_tags($data->title));
        $description = htmlspecialchars(strip_tags($data->description));
        $category = $data->category;
        $severity = $data->severity;
        $latitude = $data->latitude;
        $longitude = $data->longitude;
        $address = htmlspecialchars(strip_tags($data->address));
        $building = !empty($data->building) ? htmlspecialchars(strip_tags($data->building)) : null;
        $floor = !empty($data->floor) ? htmlspecialchars(strip_tags($data->floor)) : null;
        $room = !empty($data->room) ? htmlspecialchars(strip_tags($data->room)) : null;
        $landmark = !empty($data->landmark) ? htmlspecialchars(strip_tags($data->landmark)) : null;
        $reported_by = $data->reported_by;
        $contact_name = !empty($data->contactInfo->name) ? htmlspecialchars(strip_tags($data->contactInfo->name)) : null;
        $contact_email = !empty($data->contactInfo->email) ? htmlspecialchars(strip_tags($data->contactInfo->email)) : null;
        $contact_phone = !empty($data->contactInfo->phone) ? htmlspecialchars(strip_tags($data->contactInfo->phone)) : null;
        $contact_student_id = !empty($data->contactInfo->studentId) ? htmlspecialchars(strip_tags($data->contactInfo->studentId)) : null;
        $contact_department = !empty($data->contactInfo->department) ? htmlspecialchars(strip_tags($data->contactInfo->department)) : null;
        $additional_notes = !empty($data->additionalNotes) ? htmlspecialchars(strip_tags($data->additionalNotes)) : null;
        $is_anonymous = isset($data->isAnonymous) ? (bool)$data->isAnonymous : false;
        $images = !empty($data->images) ? json_encode($data->images) : json_encode([]);
        $witnesses = !empty($data->witnesses) ? json_encode($data->witnesses) : json_encode([]);

        $stmt->bindParam(':title', $title);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':category', $category);
        $stmt->bindParam(':severity', $severity);
        $stmt->bindParam(':latitude', $latitude);
        $stmt->bindParam(':longitude', $longitude);
        $stmt->bindParam(':address', $address);
        $stmt->bindParam(':building', $building);
        $stmt->bindParam(':floor', $floor);
        $stmt->bindParam(':room', $room);
        $stmt->bindParam(':landmark', $landmark);
        $stmt->bindParam(':reported_by', $reported_by);
        $stmt->bindParam(':contact_name', $contact_name);
        $stmt->bindParam(':contact_email', $contact_email);
        $stmt->bindParam(':contact_phone', $contact_phone);
        $stmt->bindParam(':contact_student_id', $contact_student_id);
        $stmt->bindParam(':contact_department', $contact_department);
        $stmt->bindParam(':additional_notes', $additional_notes);
        $stmt->bindParam(':is_anonymous', $is_anonymous, PDO::PARAM_BOOL);
        $stmt->bindParam(':images', $images);
        $stmt->bindParam(':witnesses', $witnesses);

        if ($stmt->execute()) {
            $incident_id = $db->lastInsertId();
            
            // Fetch the created incident
            $query = "SELECT i.*, u1.username as reported_by_username, u2.username as assigned_to_username
                     FROM incidents i
                     LEFT JOIN users u1 ON i.reported_by = u1.id
                     LEFT JOIN users u2 ON i.assigned_to = u2.id
                     WHERE i.id = :id LIMIT 1";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $incident_id);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $incident = formatIncident($row);
            
            http_response_code(201);
            echo json_encode(['success' => true, 'incident' => $incident]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Unable to create incident']);
        }
        break;

    case 'PUT':
        // Update incident
        $data = json_decode(file_get_contents("php://input"));

        if (!empty($data->id)) {
            // Check if incident exists
            $check_query = "SELECT id FROM incidents WHERE id = :id LIMIT 1";
            $check_stmt = $db->prepare($check_query);
            $check_stmt->bindParam(':id', $data->id);
            $check_stmt->execute();

            if ($check_stmt->rowCount() === 0) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Incident not found']);
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
            if (isset($data->category)) {
                $update_fields[] = "category = :category";
                $params[':category'] = $data->category;
            }
            if (isset($data->severity)) {
                $update_fields[] = "severity = :severity";
                $params[':severity'] = $data->severity;
            }
            if (isset($data->status)) {
                $update_fields[] = "status = :status";
                $params[':status'] = $data->status;
                
                // Set resolved_at if status is resolved
                if ($data->status === 'resolved') {
                    $update_fields[] = "resolved_at = NOW()";
                }
            }
            if (isset($data->assignedTo)) {
                $update_fields[] = "assigned_to = :assigned_to";
                $params[':assigned_to'] = $data->assignedTo;
            }
            if (isset($data->contactInfo)) {
                if (isset($data->contactInfo->name)) {
                    $update_fields[] = "contact_name = :contact_name";
                    $params[':contact_name'] = htmlspecialchars(strip_tags($data->contactInfo->name));
                }
                if (isset($data->contactInfo->email)) {
                    $update_fields[] = "contact_email = :contact_email";
                    $params[':contact_email'] = htmlspecialchars(strip_tags($data->contactInfo->email));
                }
                if (isset($data->contactInfo->phone)) {
                    $update_fields[] = "contact_phone = :contact_phone";
                    $params[':contact_phone'] = htmlspecialchars(strip_tags($data->contactInfo->phone));
                }
            }
            if (isset($data->additionalNotes)) {
                $update_fields[] = "additional_notes = :additional_notes";
                $params[':additional_notes'] = htmlspecialchars(strip_tags($data->additionalNotes));
            }
            if (isset($data->images)) {
                $update_fields[] = "images = :images";
                $params[':images'] = json_encode($data->images);
            }
            if (isset($data->witnesses)) {
                $update_fields[] = "witnesses = :witnesses";
                $params[':witnesses'] = json_encode($data->witnesses);
            }

            if (empty($update_fields)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'No fields to update']);
                exit;
            }

            $query = "UPDATE incidents SET " . implode(', ', $update_fields) . " WHERE id = :id";
            $params[':id'] = $data->id;

            $stmt = $db->prepare($query);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }

            if ($stmt->execute()) {
                // Fetch updated incident
                $query = "SELECT i.*, u1.username as reported_by_username, u2.username as assigned_to_username
                         FROM incidents i
                         LEFT JOIN users u1 ON i.reported_by = u1.id
                         LEFT JOIN users u2 ON i.assigned_to = u2.id
                         WHERE i.id = :id LIMIT 1";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':id', $data->id);
                $stmt->execute();
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                
                $incident = formatIncident($row);
                
                echo json_encode(['success' => true, 'incident' => $incident]);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Unable to update incident']);
            }
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Incomplete data']);
        }
        break;

    case 'DELETE':
        // Delete incident
        if (isset($_GET['id'])) {
            $query = "DELETE FROM incidents WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $_GET['id']);

            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Incident deleted successfully']);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Unable to delete incident']);
            }
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Incident ID required']);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        break;
}

function formatIncident($row) {
    return [
        'id' => $row['id'],
        'title' => $row['title'],
        'description' => $row['description'],
        'category' => $row['category'],
        'severity' => $row['severity'],
        'status' => $row['status'],
        'location' => [
            'latitude' => (float)$row['latitude'],
            'longitude' => (float)$row['longitude'],
            'address' => $row['address'],
            'building' => $row['building'],
            'floor' => $row['floor'],
            'room' => $row['room'],
            'landmark' => $row['landmark']
        ],
        'reportedBy' => $row['reported_by_username'] ?? $row['reported_by'],
        'reportedAt' => $row['reported_at'],
        'updatedAt' => $row['updated_at'],
        'resolvedAt' => $row['resolved_at'],
        'assignedTo' => $row['assigned_to_username'] ?? $row['assigned_to'],
        'images' => json_decode($row['images'] ?? '[]', true) ?: [],
        'witnesses' => json_decode($row['witnesses'] ?? '[]', true) ?: [],
        'contactInfo' => [
            'name' => $row['contact_name'],
            'email' => $row['contact_email'],
            'phone' => $row['contact_phone'],
            'studentId' => $row['contact_student_id'],
            'department' => $row['contact_department']
        ],
        'additionalNotes' => $row['additional_notes'],
        'isAnonymous' => (bool)$row['is_anonymous']
    ];
}

function isWithinCampusBounds($latitude, $longitude) {
    $southwest = [16.414543, 120.595925];
    $northeast = [16.416568, 120.599075];

    return (
        $latitude >= $southwest[0] &&
        $latitude <= $northeast[0] &&
        $longitude >= $southwest[1] &&
        $longitude <= $northeast[1]
    );
}
?>