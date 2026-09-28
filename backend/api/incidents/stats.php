<?php
/**
 * Incident Statistics API Endpoint
 * Provides admin statistics for incidents
 */

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With');

include_once '../../config/database.php';

$database = new Database();
$db = $database->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Get total incidents
    $total_query = "SELECT COUNT(*) as total FROM incidents";
    $total_stmt = $db->prepare($total_query);
    $total_stmt->execute();
    $total_result = $total_stmt->fetch(PDO::FETCH_ASSOC);
    $total_incidents = $total_result['total'];

    // Get open incidents (reported or investigating)
    $open_query = "SELECT COUNT(*) as open FROM incidents WHERE status IN ('reported', 'investigating')";
    $open_stmt = $db->prepare($open_query);
    $open_stmt->execute();
    $open_result = $open_stmt->fetch(PDO::FETCH_ASSOC);
    $open_incidents = $open_result['open'];

    // Get resolved incidents
    $resolved_query = "SELECT COUNT(*) as resolved FROM incidents WHERE status = 'resolved'";
    $resolved_stmt = $db->prepare($resolved_query);
    $resolved_stmt->execute();
    $resolved_result = $resolved_stmt->fetch(PDO::FETCH_ASSOC);
    $resolved_incidents = $resolved_result['resolved'];

    // Get critical incidents
    $critical_query = "SELECT COUNT(*) as critical FROM incidents WHERE severity = 'critical'";
    $critical_stmt = $db->prepare($critical_query);
    $critical_stmt->execute();
    $critical_result = $critical_stmt->fetch(PDO::FETCH_ASSOC);
    $critical_incidents = $critical_result['critical'];

    // Get incidents by category
    $category_query = "SELECT category, COUNT(*) as count FROM incidents GROUP BY category";
    $category_stmt = $db->prepare($category_query);
    $category_stmt->execute();
    $category_results = $category_stmt->fetchAll(PDO::FETCH_ASSOC);
    $incidents_by_category = [];
    foreach ($category_results as $row) {
        $incidents_by_category[$row['category']] = $row['count'];
    }

    // Get incidents by severity
    $severity_query = "SELECT severity, COUNT(*) as count FROM incidents GROUP BY severity";
    $severity_stmt = $db->prepare($severity_query);
    $severity_stmt->execute();
    $severity_results = $severity_stmt->fetchAll(PDO::FETCH_ASSOC);
    $incidents_by_severity = [];
    foreach ($severity_results as $row) {
        $incidents_by_severity[$row['severity']] = $row['count'];
    }

    // Calculate average resolution time (in hours)
    $resolution_time_query = "SELECT AVG(TIMESTAMPDIFF(HOUR, reported_at, resolved_at)) as avg_time 
                              FROM incidents 
                              WHERE status = 'resolved' AND resolved_at IS NOT NULL";
    $resolution_time_stmt = $db->prepare($resolution_time_query);
    $resolution_time_stmt->execute();
    $resolution_time_result = $resolution_time_stmt->fetch(PDO::FETCH_ASSOC);
    $average_resolution_time = $resolution_time_result['avg_time'] ?? 0;

    // Get incidents this month
    $month_query = "SELECT COUNT(*) as this_month FROM incidents 
                    WHERE MONTH(reported_at) = MONTH(CURRENT_DATE()) 
                    AND YEAR(reported_at) = YEAR(CURRENT_DATE())";
    $month_stmt = $db->prepare($month_query);
    $month_stmt->execute();
    $month_result = $month_stmt->fetch(PDO::FETCH_ASSOC);
    $incidents_this_month = $month_result['this_month'];

    $stats = [
        'totalIncidents' => $total_incidents,
        'openIncidents' => $open_incidents,
        'resolvedIncidents' => $resolved_incidents,
        'criticalIncidents' => $critical_incidents,
        'incidentsByCategory' => $incidents_by_category,
        'incidentsBySeverity' => $incidents_by_severity,
        'averageResolutionTime' => (float)$average_resolution_time,
        'incidentsThisMonth' => $incidents_this_month
    ];

    echo json_encode(['success' => true, 'stats' => $stats]);
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}
?>