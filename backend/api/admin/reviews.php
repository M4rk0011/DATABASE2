<?php
/**
 * Admin Reviews API Endpoint
 * Handles admin review operations for concern reports
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
        // Get all reviews or specific review
        if (isset($_GET['id'])) {
            $query = "SELECT rr.*, 
                     a.full_name as admin_name,
                     cr.title as concern_title
                     FROM report_reviews rr
                     JOIN admins a ON rr.admin_id = a.admin_id
                     JOIN concern_reports cr ON rr.report_id = cr.report_id
                     WHERE rr.review_id = :id LIMIT 1";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $_GET['id']);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($row) {
                $review = formatReview($row);
                echo json_encode(['success' => true, 'review' => $review]);
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Review not found']);
            }
        } else {
            // Get reviews for specific concern
            if (isset($_GET['report_id'])) {
                $query = "SELECT rr.*, 
                         a.full_name as admin_name,
                         cr.title as concern_title
                         FROM report_reviews rr
                         JOIN admins a ON rr.admin_id = a.admin_id
                         JOIN concern_reports cr ON rr.report_id = cr.report_id
                         WHERE rr.report_id = :report_id
                         ORDER BY rr.reviewed_at DESC";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':report_id', $_GET['report_id']);
                $stmt->execute();
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

                $reviews = array_map('formatReview', $rows);
                echo json_encode(['success' => true, 'reviews' => $reviews]);
            } else {
                // Get all reviews
                $query = "SELECT rr.*, 
                         a.full_name as admin_name,
                         cr.title as concern_title
                         FROM report_reviews rr
                         JOIN admins a ON rr.admin_id = a.admin_id
                         JOIN concern_reports cr ON rr.report_id = cr.report_id
                         ORDER BY rr.reviewed_at DESC";
                $stmt = $db->prepare($query);
                $stmt->execute();
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

                $reviews = array_map('formatReview', $rows);
                echo json_encode(['success' => true, 'reviews' => $reviews]);
            }
        }
        break;

    case 'POST':
        // Create new review
        $data = json_decode(file_get_contents("php://input"));

        if (!empty($data->report_id) && !empty($data->admin_id) && !empty($data->status_set)) {
            $query = "INSERT INTO report_reviews (report_id, admin_id, status_set, remarks) 
                      VALUES (:report_id, :admin_id, :status_set, :remarks)";

            $stmt = $db->prepare($query);

            $status_set = $data->status_set;
            $remarks = !empty($data->remarks) ? htmlspecialchars(strip_tags($data->remarks)) : null;

            $stmt->bindParam(':report_id', $data->report_id);
            $stmt->bindParam(':admin_id', $data->admin_id);
            $stmt->bindParam(':status_set', $status_set);
            $stmt->bindParam(':remarks', $remarks);

            if ($stmt->execute()) {
                // Update concern status
                $update_query = "UPDATE concern_reports SET status = :status WHERE report_id = :report_id";
                $update_stmt = $db->prepare($update_query);
                $update_stmt->bindParam(':status', $status_set);
                $update_stmt->bindParam(':report_id', $data->report_id);
                $update_stmt->execute();

                $review_id = $db->lastInsertId();
                
                // Fetch the created review
                $query = "SELECT rr.*, 
                         a.full_name as admin_name,
                         cr.title as concern_title
                         FROM report_reviews rr
                         JOIN admins a ON rr.admin_id = a.admin_id
                         JOIN concern_reports cr ON rr.report_id = cr.report_id
                         WHERE rr.review_id = :id LIMIT 1";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':id', $review_id);
                $stmt->execute();
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                
                $review = formatReview($row);
                
                http_response_code(201);
                echo json_encode(['success' => true, 'review' => $review]);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Unable to create review']);
            }
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Incomplete data']);
        }
        break;

    case 'DELETE':
        // Delete review
        if (isset($_GET['id'])) {
            $query = "DELETE FROM report_reviews WHERE review_id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $_GET['id']);

            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Review deleted successfully']);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Unable to delete review']);
            }
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Review ID required']);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        break;
}

function formatReview($row) {
    return [
        'id' => $row['review_id'],
        'reportId' => $row['report_id'],
        'adminId' => $row['admin_id'],
        'adminName' => $row['admin_name'],
        'concernTitle' => $row['concern_title'],
        'statusSet' => $row['status_set'],
        'remarks' => $row['remarks'],
        'reviewedAt' => $row['reviewed_at']
    ];
}
?>