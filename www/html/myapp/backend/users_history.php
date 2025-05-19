<?php
session_start();
require_once __DIR__ . '/../../../config.php';;

header('Content-Type: application/json');

try {
    $pdo = getPDO();

    if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
        throw new Exception('Unauthorized access', 403);
    }

    if (!isset($pdo)) {
        throw new Exception('Database connection not available', 500);
    }

    // Export do CSV
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['export'])) {
        exportToCSV($pdo);
        exit;
    }

    $response = [];

    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            // Pagination parameters
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 10;
            $offset = ($page - 1) * $perPage;

            // Get total count
            $countStmt = $pdo->query("SELECT COUNT(*) as total FROM users_history");
            $total = $countStmt->fetchColumn();

            // Get paginated data
            $stmt = $pdo->prepare("SELECT * FROM users_history ORDER BY time DESC LIMIT :limit OFFSET :offset");
            $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();

            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if ($data === false) {
                throw new Exception('Failed to fetch user history', 500);
            }

            $response = [
                'data' => $data,
                'pagination' => [
                    'total' => (int)$total,
                    'per_page' => $perPage,
                    'current_page' => $page,
                    'last_page' => ceil($total / $perPage)
                ]
            ];
            break;

        case 'DELETE':
            $input = json_decode(file_get_contents('php://input'), true);
            if (empty($input['username']) || empty($input['time'])) {
                throw new Exception('Missing username or time', 400);
            }

            $stmt = $pdo->prepare("DELETE FROM users_history WHERE users_username = ? AND time = ?");
            $stmt->execute([$input['username'], $input['time']]);

            if ($stmt->rowCount() > 0) {
                $response = ['success' => true];
            } else {
                throw new Exception('No matching record found', 404);
            }
            break;

        default:
            throw new Exception('Method Not Allowed', 405);
    }

    echo json_encode($response);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
    error_log('PDO Exception: ' . $e->getMessage());
} catch (Exception $e) {
    $code = $e->getCode();
    http_response_code($code >= 400 ? $code : 400);
    echo json_encode(['error' => $e->getMessage()]);

    if ($code >= 500) {
        error_log('Server Exception: ' . $e->getMessage());
    }
}

// Funkcia na export do CSV
function exportToCSV($pdo) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=user_history.csv');

    $output = fopen('php://output', 'w');
    fputcsv($output, ['Username', 'Time', 'Action', 'Source', 'Location']);

    $stmt = $pdo->query("SELECT users_username, time, action_type, source, location FROM users_history ORDER BY time DESC");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, [
            $row['users_username'],
            $row['time'],
            $row['action_type'],
            $row['source'],
            $row['location']
        ]);
    }
    fclose($output);
}
