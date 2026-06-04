<?php
declare(strict_types=1);

require __DIR__ . '/auth_bootstrap.php';

$user = $_SESSION['luxestate_user'] ?? null;

luxestate_json_response(200, [
    'success' => true,
    'authenticated' => is_array($user),
    'user' => is_array($user) ? $user : null,
]);
