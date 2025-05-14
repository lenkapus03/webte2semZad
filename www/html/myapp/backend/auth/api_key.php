<?php
require_once "utilities.php";
require_once __DIR__ . '/../../config.php';

function generateApiKey(): string {
    return bin2hex(random_bytes(32));
}

function assignApiKeyToUser(string $username): ?string {
    $pdo = getPDO();

    if(!$username) return null;

    $newApiKey = generateApiKey();

    try {
        $stmt = $pdo->prepare("UPDATE users SET api_key = ? WHERE username = ?");
        $stmt->execute([$newApiKey, $username]);
        return $newApiKey;
    } catch (PDOException $e) {
        error_log("API key assignment failed for {$username}: " . $e->getMessage());
        return null;
    }
}

function getUserApiKey(string $username): ?string {
    $pdo = getPDO();
    if(!$username) return null;


    try {
        $stmt = $pdo->prepare("SELECT api_key FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['api_key'] ?? null;
    } catch (PDOException $e) {
        error_log("API key retrieval failed for {$username}: " . $e->getMessage());
        return null;
    }
}

function validateApiKey(string $apiKey): bool {
    $pdo = getPDO();
    if(!$apiKey) return false;


    try {
        $stmt = $pdo->prepare("SELECT 1 FROM users WHERE api_key = ?");
        $stmt->execute([$apiKey]);
        return (bool)$stmt->fetch();
    } catch (PDOException $e) {
        error_log("API key validation failed: " . $e->getMessage());
        return false;
    }
}
