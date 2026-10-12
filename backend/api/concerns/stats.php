<?php
/**
 * Concern Statistics API Endpoint
 * Provides admin statistics for concerns
 */

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With');

include_once '../../config/database.php';

$database = new Database();
$db = $database->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Get total concerns
    $total_query = "SELECT COUNT(*) as total FROM concerns";
    $total_stmt = $db->prepare($total_query);
    $total_stmt->execute();
    $total_result = $total_stmt->fetch(PDO::FETCH_ASSOC);
    $total_concerns = $total_result['total'];

    // Get open concerns (reported or investigating)
    $open_query = "SELECT COUNT(*) as open FROM concerns WHERE status IN ('reported', 'investigating')";
    $open_stmt = $db->prepare($open_query);
    $open_stmt->execute();
    $open_result = $open_stmt->fetch(PDO::FETCH_ASSOC);
    $open_concerns = $open_result['open'];

    // Get resolved concerns
    $resolved_query = "SELECT COUNT(*) as resolved FROM concerns WHERE status = 'resolved'";
    $resolved_stmt = $db->prepare($resolved_query);
    $resolved_stmt->execute();
    $resolved_result = $resolved_stmt->fetch(PDO::FETCH_ASSOC);
    $resolved_concerns = $resolved_result['resolved'];

    // Get critical concerns
    $critical_query = "SELECT COUNT(*) as critical FROM concerns WHERE severity = 'critical'";
    $critical_stmt = $db->prepare($critical_query);
    $critical_stmt->execute();
    $critical_result = $critical_stmt->fetch(PDO::FETCH_ASSOC);
    $critical_concerns = $critical_result['critical'];

    // Get concerns by category
    $category_query = "SELECT category, COUNT(*) as count FROM concerns GROUP BY category";
    $category_stmt = $db->prepare($category_query);
    $category_stmt->execute();
    $category_results = $category_stmt->fetchAll(PDO::FETCH_ASSOC);
    $concerns_by_category = [];
    foreach ($category_results as $row) {
        $concerns_by_category[$row['category']] = $row['count'];
    }

    // Get concerns by severity
    $severity_query = "SELECT severity, COUNT(*) as count FROM concerns GROUP BY severity";
    $severity_stmt = $db->prepare($severity_query);
    $severity_stmt->execute();
    $severity_results = $severity_stmt->fetchAll(PDO::FETCH_ASSOC);
    $concerns_by_severity = [];
    foreach ($severity_results as $row) {
        $concerns_by_severity[$row['severity']] = $row['count'];
    }

    // Calculate average resolution time (in hours)
    $resolution_time_query = "SELECT AVG(TIMESTAMPDIFF(HOUR, reported_at, resolved_at)) as avg_time
                              FROM concerns
                              WHERE status = 'resolved' AND resolved_at IS NOT NULL";
    $resolution_time_stmt = $db->prepare($resolution_time_query);
    $resolution_time_stmt->execute();
    $resolution_time_result = $resolution_time_stmt->fetch(PDO::FETCH_ASSOC);
    $average_resolution_time = $resolution_time_result['avg_time'] ?? 0;

    // Get concerns this month
    $month_query = "SELECT COUNT(*) as this_month FROM concerns
                    WHERE MONTH(reported_at) = MONTH(CURRENT_DATE())
                    AND YEAR(reported_at) = YEAR(CURRENT_DATE())";
    $month_stmt = $db->prepare($month_query);
    $month_stmt->execute();
    $month_result = $month_stmt->fetch(PDO::FETCH_ASSOC);
    $concerns_this_month = $month_result['this_month'];

    $stats = [
        'totalConcerns' => $total_concerns,
        'openConcerns' => $open_concerns,
        'resolvedConcerns' => $resolved_concerns,
        'criticalConcerns' => $critical_concerns,
        'concernsByCategory' => $concerns_by_category,
        'concernsBySeverity' => $concerns_by_severity,
        'averageResolutionTime' => (float)$average_resolution_time,
        'concernsThisMonth' => $concerns_this_month
    ];

    echo json_encode(['success' => true, 'stats' => $stats]);
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}
?>