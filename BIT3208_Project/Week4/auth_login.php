<?php
declare(strict_types=1);

require __DIR__ . '/auth_bootstrap.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    luxestate_json_response(405, ['success' => false, 'message' => 'Method not allowed.']);
}

$data = luxestate_request_data();
$email = luxestate_normalize_email((string) ($data['email'] ?? ''));
$password = (string) ($data['password'] ?? '');

if ($email === '' || $password === '') {
    luxestate_json_response(422, ['success' => false, 'message' => 'Please fill all fields.']);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    luxestate_json_response(422, ['success' => false, 'message' => 'Please enter a valid email address.']);
}

$statement = luxestate_db()->prepare(
    'SELECT id, full_name, email, password_hash FROM users WHERE email = ? LIMIT 1'
);
$statement->bind_param('s', $email);
$statement->execute();
$result = $statement->get_result();
$user = $result->fetch_assoc();

if (!$user || !password_verify($password, $user['password_hash'])) {
    luxestate_json_response(401, ['success' => false, 'message' => 'Invalid credentials. Please try again or create an account.']);
}

$_SESSION['luxestate_user'] = luxestate_public_user($user);

luxestate_json_response(200, [
    'success' => true,
    'message' => 'Login successful.',
    'user' => $_SESSION['luxestate_user'],
]);
