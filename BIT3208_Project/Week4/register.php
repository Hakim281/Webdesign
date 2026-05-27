<?php
declare(strict_types=1);

require __DIR__ . '/auth.php';

require_guest();

$errors = [];
$form = [
    'full_name' => '',
    'email' => '',
    'phone' => '',
];
$flash = get_flash();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form['full_name'] = trim((string) ($_POST['full_name'] ?? ''));
    $form['email'] = trim((string) ($_POST['email'] ?? ''));
    $form['phone'] = trim((string) ($_POST['phone'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $confirmPassword = (string) ($_POST['confirm_password'] ?? '');

    if ($form['full_name'] === '' || mb_strlen($form['full_name']) < 3) {
        $errors[] = 'Enter your full name using at least 3 characters.';
    }

    if (!filter_var($form['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Enter a valid email address.';
    }

    if ($form['phone'] === '' || mb_strlen($form['phone']) < 10) {
        $errors[] = 'Enter a valid phone number.';
    }

    if (mb_strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters.';
    }

    if ($password !== $confirmPassword) {
        $errors[] = 'Passwords do not match.';
    }

    if (!$errors) {
        try {
            if (find_user_by_email($form['email']) !== null) {
                $errors[] = 'That email address is already registered.';
            }

            if (find_user_by_phone($form['phone']) !== null) {
                $errors[] = 'That phone number is already registered.';
            }
        } catch (Throwable $exception) {
            $errors[] = $exception->getMessage();
        }
    }

    if (!$errors) {
        try {
            create_user($form['full_name'], $form['email'], $form['phone'], $password);
            set_flash('success', 'Account created successfully. You can now log in.');
            redirect('login.php');
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
    <title>Create Account | Nyumbani Luxe</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="auth-page">
    <div class="auth-layout">
        <section class="auth-showcase">
            <p class="section-tag">Members Only Access</p>
            <h1>Open your account before exploring the full property collection.</h1>
            <p>
                Create a secure account to browse modern homes, compare prices, and access the
                complete real estate experience inside the portal.
            </p>
            <div class="auth-points">
                <span>Modern home collection</span>
                <span>Secure MySQL login</span>
                <span>XAMPP ready setup</span>
            </div>
            <a class="ghost-link" href="login.php">Already have an account? Sign in</a>
        </section>

        <section class="auth-card">
            <a class="brand" href="register.php">Nyumbani Luxe</a>
            <h2>Create Account</h2>

            <?php if ($flash !== null): ?>
                <div class="message message-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
            <?php endif; ?>

            <?php if ($errors): ?>
                <div class="message message-error"><?= e(implode(' ', $errors)) ?></div>
            <?php endif; ?>

            <form method="post" class="auth-form">
                <label class="field">
                    <span>Full Name</span>
                    <input type="text" name="full_name" value="<?= e($form['full_name']) ?>" placeholder="Enter your full name" required>
                </label>

                <label class="field">
                    <span>Email Address</span>
                    <input type="email" name="email" value="<?= e($form['email']) ?>" placeholder="Enter your email address" required>
                </label>

                <label class="field">
                    <span>Phone Number</span>
                    <input type="text" name="phone" value="<?= e($form['phone']) ?>" placeholder="e.g. 0712345678" required>
                </label>

                <label class="field">
                    <span>Password</span>
                    <input type="password" name="password" placeholder="Create a strong password" required>
                </label>

                <label class="field">
                    <span>Confirm Password</span>
                    <input type="password" name="confirm_password" placeholder="Repeat your password" required>
                </label>

                <button type="submit" class="primary-button">Create My Account</button>
            </form>

            <p class="auth-footer">Back to <a href="login.php">login</a></p>
        </section>
    </div>
</body>
</html>
