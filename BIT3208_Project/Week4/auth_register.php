<?php
declare(strict_types=1);

require __DIR__ . '/auth_bootstrap.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    luxestate_json_response(405, ['success' => false, 'message' => 'Method not allowed.']);
}

$data = luxestate_request_data();
$name = trim((string) ($data['name'] ?? ''));
$email = luxestate_normalize_email((string) ($data['email'] ?? ''));
$phone = trim((string) ($data['phone'] ?? ''));
$password = (string) ($data['password'] ?? '');

if ($name === '' || $email === '' || $phone === '' || $password === '') {
    luxestate_json_response(422, ['success' => false, 'message' => 'Please fill all fields.']);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    luxestate_json_response(422, ['success' => false, 'message' => 'Please enter a valid email address.']);
}

if (strlen($password) < 8) {
    luxestate_json_response(422, ['success' => false, 'message' => 'Password must be at least 8 characters.']);
}

$db = luxestate_db();

$checkStatement = $db->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
$checkStatement->bind_param('s', $email);
$checkStatement->execute();
if ($checkStatement->get_result()->fetch_assoc()) {
    luxestate_json_response(409, ['success' => false, 'message' => 'Email already registered. Please sign in.']);
}

$passwordHash = password_hash($password, PASSWORD_BCRYPT);
$insertStatement = $db->prepare(
    'INSERT INTO users (full_name, email, phone, password_hash) VALUES (?, ?, ?, ?)'
);
$insertStatement->bind_param('ssss', $name, $email, $phone, $passwordHash);
$insertStatement->execute();

$user = [
    'id' => $db->insert_id,
    'full_name' => $name,
    'email' => $email,
];

$_SESSION['luxestate_user'] = luxestate_public_user($user);

luxestate_json_response(201, [
    'success' => true,
    'message' => 'Account created successfully.',
    'user' => $_SESSION['luxestate_user'],
]);
