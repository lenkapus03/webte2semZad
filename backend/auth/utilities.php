<?php
require "/var/www/configs/config.php";
function getLocationFromIP($ip): string
{
    $apiUrl = "http://ip-api.com/json/$ip?fields=city,country";
    $response = @file_get_contents($apiUrl);

    if ($response === false) {
        error_log("Failed to fetch IP location for $ip");
        return 'Unknown';
    }

    $data = json_decode($response, true);

    if (is_array($data) && isset($data['city']) && isset($data['country'])) {
        return $data['city'] . ', ' . $data['country'];
    } else {
        return 'Unknown';
    }
}


function validateUsername(string $username): array {
    $errors = [];

    if (empty($username)) {
        $errors[] = 'Username is required.';
    } elseif (strlen($username) < 3 || strlen($username) > 30) {
        $errors[] = 'Username must be between 3 and 30 characters.';
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $errors[] = 'Username must contain only letters, numbers, and underscores.';
    }

    return [
        'valid' => empty($errors),
        'errors' => $errors
    ];
}

function validatePassword(string $password): array {
    $errors = [];

    if (empty($password)) {
        $errors[] = 'Password is required.';
    } elseif (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters long.';
    } elseif (!preg_match('/[a-z]/', $password)) {
        $errors[] = 'Password must include at least one lowercase letter.';
    } elseif (!preg_match('/[A-Z]/', $password)) {
        $errors[] = 'Password must include at least one uppercase letter.';
    } elseif (!preg_match('/\d/', $password)) {
        $errors[] = 'Password must include at least one number.';
    }

    return [
        'valid' => empty($errors),
        'errors' => $errors
    ];
}


function validateUsernamePassword(string $username, string $password): array {
    $usernameResult = validateUsername($username);
    $passwordResult = validatePassword($password);

    $combinedErrors = array_merge(
        $usernameResult['errors'] ?? [],
        $passwordResult['errors'] ?? []
    );

    return [
        'valid' => $usernameResult['valid'] && $passwordResult['valid'],
        'errors' => $combinedErrors
    ];
}

function validateRole(string $role): array {
    $validRoles = ['user', 'admin'];

    if (empty($role)) {
        return ['valid' => false, 'errors' => ['Role is required.']];
    }

    if (!in_array($role, $validRoles)) {
        return ['valid' => false, 'errors' => ['Invalid role selected.']];
    }

    return ['valid' => true, 'errors' => []];
}

function logUserAction(string $username, string $action_type, string $source = 'frontend'): bool
{
    $pdo = getPDO();
    try{
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $location = getLocationFromIP($ip);
        $currentTime = date('Y-m-d H:i:s');

        $stmt = $pdo->prepare(
            "INSERT INTO users_history (users_username, time, action_type, source, location)
             VALUES (?, ?, ?, ?, ?)"
        );

        return $stmt->execute([$username, $currentTime, $action_type, $source, $location]);
    } catch (PDOException $e) {
        error_log("Failed to log user action: " . $e->getMessage());
        return false;
    }
}