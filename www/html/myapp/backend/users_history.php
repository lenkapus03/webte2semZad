<?php
header('Content-Type: application/json');
session_start();

require_once '../../../config.php';


$response = [];

try {

    $pdo = getPDO();

    if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
        throw new Exception('Unauthorized access', 403);
    }

    if (!isset($pdo)) {
        throw new Exception('Database connection not available', 500);
    }

    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            $stmt = $pdo->query("SELECT * FROM users_history ORDER BY time DESC");
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if ($data === false) {
                throw new Exception('Failed to fetch user history', 500);
            }

            $response = $data;
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

} catch (PDOException $e) {
    http_response_code(500);
    $response = ['error' => 'Database error'];
    error_log('PDO Exception: ' . $e->getMessage());
} catch (Exception $e) {
    $code = $e->getCode();
    http_response_code($code >= 400 ? $code : 400);
    $response = ['error' => $e->getMessage()];

    if ($code >= 500) {
        error_log('Server Exception: ' . $e->getMessage());
    }
}

echo json_encode($response);
