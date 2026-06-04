<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$allowedOrigins = [
    'null',
    'http://localhost',
    'http://127.0.0.1',
];

if (in_array($origin, $allowedOrigins, true)) {
    header('Access-Control-Allow-Origin: ' . $origin);
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Allow-Headers: Content-Type');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Vary: Origin');
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
    http_response_code(204);
    exit;
}

function luxestate_db(): mysqli
{
    static $connection = null;

    if ($connection instanceof mysqli) {
        return $connection;
    }

    $connection = new mysqli('127.0.0.1', 'root', '', 'luxestate_kenya');
    $connection->set_charset('utf8mb4');

    return $connection;
}

function luxestate_request_data(): array
{
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    if (stripos($contentType, 'application/json') !== false) {
        $raw = file_get_contents('php://input');
        if ($raw === false || $raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }

    return $_POST;
}

function luxestate_json_response(int $statusCode, array $payload): never
{
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function luxestate_normalize_email(string $email): string
{
    return strtolower(trim($email));
}

function luxestate_public_user(array $user): array
{
    return [
        'id' => (int) $user['id'],
        'name' => $user['full_name'],
        'email' => $user['email'],
    ];
}
