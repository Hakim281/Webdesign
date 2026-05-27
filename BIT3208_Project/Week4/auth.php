<?php
declare(strict_types=1);

session_start();

function app_config(): array
{
    static $config = null;

    if ($config === null) {
        $config = require __DIR__ . '/config.php';
    }

    return $config;
}

function app_pdo(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $config = app_config();
    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
        $config['db_host'],
        $config['db_port'],
        $config['db_name']
    );

    try {
        $pdo = new PDO(
            $dsn,
            $config['db_user'],
            $config['db_pass'],
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );
    } catch (PDOException $exception) {
        throw new RuntimeException(
            'Database connection failed. Import database.sql in phpMyAdmin and confirm config.php matches your XAMPP MySQL details.'
        );
    }

    return $pdo;
}

function redirect(string $path): never
{
    header('Location: ' . $path);
    exit;
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function set_flash(string $type, string $message): void
{
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message,
    ];
}

function get_flash(): ?array
{
    if (!isset($_SESSION['flash'])) {
        return null;
    }

    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);

    return $flash;
}

function find_user_by_login(string $login): ?array
{
    $statement = app_pdo()->prepare(
        'SELECT id, full_name, email, phone, password_hash, created_at
         FROM users
         WHERE email = :login OR phone = :login
         LIMIT 1'
    );
    $statement->execute(['login' => $login]);
    $user = $statement->fetch();

    return $user ?: null;
}

function find_user_by_email(string $email): ?array
{
    $statement = app_pdo()->prepare(
        'SELECT id, full_name, email, phone, created_at
         FROM users
         WHERE email = :email
         LIMIT 1'
    );
    $statement->execute(['email' => $email]);
    $user = $statement->fetch();

    return $user ?: null;
}

function find_user_by_phone(string $phone): ?array
{
    $statement = app_pdo()->prepare(
        'SELECT id, full_name, email, phone, created_at
         FROM users
         WHERE phone = :phone
         LIMIT 1'
    );
    $statement->execute(['phone' => $phone]);
    $user = $statement->fetch();

    return $user ?: null;
}

function find_user_by_id(int $id): ?array
{
    $statement = app_pdo()->prepare(
        'SELECT id, full_name, email, phone, created_at
         FROM users
         WHERE id = :id
         LIMIT 1'
    );
    $statement->execute(['id' => $id]);
    $user = $statement->fetch();

    return $user ?: null;
}

function current_user(): ?array
{
    if (!isset($_SESSION['user_id'])) {
        return null;
    }

    return find_user_by_id((int) $_SESSION['user_id']);
}

function require_guest(): void
{
    if (current_user() !== null) {
        redirect('index.php');
    }
}

function require_auth(): array
{
    $user = current_user();

    if ($user === null) {
        set_flash('error', 'Please log in or create an account to access the property portal.');
        redirect('login.php');
    }

    return $user;
}

function create_user(string $fullName, string $email, string $phone, string $password): void
{
    $statement = app_pdo()->prepare(
        'INSERT INTO users (full_name, email, phone, password_hash)
         VALUES (:full_name, :email, :phone, :password_hash)'
    );
    $statement->execute([
        'full_name' => $fullName,
        'email' => $email,
        'phone' => $phone,
        'password_hash' => password_hash($password, PASSWORD_DEFAULT),
    ]);
}

function create_contact_request(
    int $userId,
    string $fullName,
    string $email,
    string $phone,
    string $preferredLocation,
    string $message
): void {
    $statement = app_pdo()->prepare(
        'INSERT INTO contact_requests (user_id, full_name, email, phone, preferred_location, message)
         VALUES (:user_id, :full_name, :email, :phone, :preferred_location, :message)'
    );
    $statement->execute([
        'user_id' => $userId,
        'full_name' => $fullName,
        'email' => $email,
        'phone' => $phone,
        'preferred_location' => $preferredLocation,
        'message' => $message,
    ]);
}

function list_properties(): array
{
    $statement = app_pdo()->query(
        'SELECT id, title, location, price_ksh, bedrooms, bathrooms, size_sqft, status_label, image_path, short_description, featured
         FROM properties
         ORDER BY featured DESC, price_ksh DESC, id ASC'
    );

    return $statement->fetchAll();
}
