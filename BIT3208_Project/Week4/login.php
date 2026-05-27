<?php
declare(strict_types=1);

require __DIR__ . '/auth.php';

require_guest();

$errors = [];
$loginValue = '';
$flash = get_flash();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $loginValue = trim((string) ($_POST['login'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if ($loginValue === '' || $password === '') {
        $errors[] = 'Enter your email or phone number together with your password.';
    } else {
        try {
            $user = find_user_by_login($loginValue);

            if ($user === null || !password_verify($password, $user['password_hash'])) {
                $errors[] = 'Invalid login details.';
            } else {
                session_regenerate_id(true);
                $_SESSION['user_id'] = (int) $user['id'];
                set_flash('success', 'Welcome back, ' . $user['full_name'] . '.');
                redirect('index.php');
            }
        } catch (Throwable $exception) {
            $errors[] = $exception->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Nyumbani Luxe</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="auth-page">
    <div class="auth-layout">
        <section class="auth-showcase">
            <p class="section-tag">Luxury Property Portal</p>
            <h1>Sign in to view curated homes from KSh 5M to KSh 10M.</h1>
            <p>
                The portal uses PHP sessions and a MySQL database so your members can register,
                log in, and access the protected real estate website through XAMPP.
            </p>
            <div class="auth-points">
                <span>Elegant modern listings</span>
                <span>Private member access</span>
                <span>Ready for localhost</span>
            </div>
            <a class="ghost-link" href="register.php">Need an account? Create one</a>
        </section>

        <section class="auth-card">
            <a class="brand" href="login.php">Nyumbani Luxe</a>
            <h2>Welcome Back</h2>

            <?php if ($flash !== null): ?>
                <div class="message message-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
            <?php endif; ?>

            <?php if ($errors): ?>
                <div class="message message-error"><?= e(implode(' ', $errors)) ?></div>
            <?php endif; ?>

            <form method="post" class="auth-form">
                <label class="field">
                    <span>Email or Phone</span>
                    <input type="text" name="login" value="<?= e($loginValue) ?>" placeholder="Enter your email or phone number" required>
                </label>

                <label class="field">
                    <span>Password</span>
                    <input type="password" name="password" placeholder="Enter your password" required>
                </label>

                <button type="submit" class="primary-button">Login To Portal</button>
            </form>

            <p class="auth-footer">New here? <a href="register.php">Create an account</a></p>
        </section>
    </div>
</body>
</html>
