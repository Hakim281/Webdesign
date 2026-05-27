<?php

declare(strict_types=1);

function load_env(): void
{
    static $loaded = false;
    if ($loaded) {
        return;
    }

    $env_path = __DIR__ . '/.env';
    if (!is_file($env_path)) {
        $loaded = true;
        return;
    }

    $lines = file($env_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        $loaded = true;
        return;
    }

    foreach ($lines as $line) {
        $trimmed = trim($line);
        if ($trimmed === '' || str_starts_with($trimmed, '#') || !str_contains($trimmed, '=')) {
            continue;
        }

        [$key, $value] = explode('=', $trimmed, 2);
        $key = trim($key);
        if ($key === '' || getenv($key) !== false) {
            continue;
        }

        $value = trim($value);
        $value = trim($value, "\"'");
        putenv($key . '=' . $value);
        $_ENV[$key] = $value;
    }

    $loaded = true;
}

function get_env_value(string $key, ?string $default = null): ?string
{
    load_env();
    $value = getenv($key);
    if ($value === false || $value === '') {
        return $default;
    }
    return $value;
}

function get_database_url(): string
{
    return get_env_value(
        'DATABASE_URL',
        'mysql://root:@127.0.0.1:3306/student_management_system?charset=utf8mb4'
    );
}

function parse_database_config(string $database_url): array
{
    if (str_starts_with($database_url, 'mysql:')) {
        return [
            'dsn' => $database_url,
            'username' => get_env_value('DB_USERNAME', 'root'),
            'password' => get_env_value('DB_PASSWORD', ''),
        ];
    }

    $parts = parse_url($database_url);
    if ($parts === false || ($parts['scheme'] ?? '') !== 'mysql') {
        throw new RuntimeException('DATABASE_URL must be a valid MySQL connection string.', 500);
    }

    $host = $parts['host'] ?? '127.0.0.1';
    $port = (int) ($parts['port'] ?? 3306);
    $database = isset($parts['path']) ? ltrim($parts['path'], '/') : '';
    if ($database === '') {
        throw new RuntimeException('DATABASE_URL must include a database name.', 500);
    }

    $query = [];
    if (isset($parts['query'])) {
        parse_str($parts['query'], $query);
    }
    $charset = $query['charset'] ?? 'utf8mb4';

    return [
        'dsn' => sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $host,
            $port,
            $database,
            $charset
        ),
        'username' => urldecode($parts['user'] ?? get_env_value('DB_USERNAME', 'root')),
        'password' => urldecode($parts['pass'] ?? get_env_value('DB_PASSWORD', '')),
    ];
}

function get_pdo(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $config = parse_database_config(get_database_url());

    try {
        $pdo = new PDO(
            $config['dsn'],
            $config['username'],
            $config['password'],
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );
    } catch (PDOException $error) {
        throw new RuntimeException('Could not connect to MySQL. Check your XAMPP MySQL service and DATABASE_URL.', 500);
    }

    return $pdo;
}
