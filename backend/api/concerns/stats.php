<?php
/**
 * Concern Statistics API Endpoint
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
    $total_query = "SELECT COUNT(*) as total FROM concern_reports";
    $total_stmt = $db->prepare($total_query);
    $total_stmt->execute();
    $total_result = $total_stmt->fetch(PDO::FETCH_ASSOC);
    $total_concerns = $total_result['total'];

    // Get pending concerns
    $pending_query = "SELECT COUNT(*) as pending FROM concern_reports WHERE status = 'pending'";
    $pending_stmt = $db->prepare($pending_query);
    $pending_stmt->execute();
    $pending_result = $pending_stmt->fetch(PDO::FETCH_ASSOC);
    $pending_concerns = $pending_result['pending'];

    // Get resolved concerns
    $resolved_query = "SELECT COUNT(*) as resolved FROM concern_reports WHERE status = 'resolved'";
    $resolved_stmt = $db->prepare($resolved_query);
    $resolved_stmt->execute();
    $resolved_result = $resolved_stmt->fetch(PDO::FETCH_ASSOC);
    $resolved_concerns = $resolved_result['resolved'];

    // Get critical concerns
    $critical_query = "SELECT COUNT(*) as critical FROM concern_reports WHERE severity = 'critical'";
    $critical_stmt = $db->prepare($critical_query);
    $critical_stmt->execute();
    $critical_result = $critical_stmt->fetch(PDO::FETCH_ASSOC);
    $critical_concerns = $critical_result['critical'];

    // Get concerns by category
    $category_query = "SELECT cc.category_name, COUNT(cr.report_id) as count 
                      FROM concern_categories cc
                      LEFT JOIN concern_reports cr ON cc.category_id = cr.category_id
                      GROUP BY cc.category_id, cc.category_name";
    $category_stmt = $db->prepare($category_query);
    $category_stmt->execute();
    $category_results = $category_stmt->fetchAll(PDO::FETCH_ASSOC);
    $concerns_by_category = [];
    foreach ($category_results as $row) {
        $concerns_by_category[$row['category_name']] = $row['count'];
    }

    // Get concerns by severity
    $severity_query = "SELECT severity, COUNT(*) as count FROM concern_reports GROUP BY severity";
    $severity_stmt = $db->prepare($severity_query);
    $severity_stmt->execute();
    $severity_results = $severity_stmt->fetchAll(PDO::FETCH_ASSOC);
    $concerns_by_severity = [];
    foreach ($severity_results as $row) {
        $concerns_by_severity[$row['severity']] = $row['count'];
    }

    // Get concerns this month
    $month_query = "SELECT COUNT(*) as this_month FROM concern_reports 
                    WHERE MONTH(concern_date) = MONTH(CURRENT_DATE()) 
                    AND YEAR(concern_date) = YEAR(CURRENT_DATE())";
    $month_stmt = $db->prepare($month_query);
    $month_stmt->execute();
    $month_result = $month_stmt->fetch(PDO::FETCH_ASSOC);
    $concerns_this_month = $month_result['this_month'];

    $stats = [
        'totalConcerns' => $total_concerns,
        'pendingConcerns' => $pending_concerns,
        'resolvedConcerns' => $resolved_concerns,
        'criticalConcerns' => $critical_concerns,
        'concernsByCategory' => $concerns_by_category,
        'concernsBySeverity' => $concerns_by_severity,
        'concernsThisMonth' => $concerns_this_month
    ];

    echo json_encode(['success' => true, 'stats' => $stats]);
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}
?>