<?php
declare(strict_types=1);

require_once __DIR__ . '/config/Database.php';
require_once __DIR__ . '/core/Security.php';
require_once __DIR__ . '/core/Auth.php';

if (Auth::check()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::verifyCsrf($_POST['csrf_token'] ?? null);

    $email = trim((string)($_POST['email'] ?? ''));
    $password = (string)($_POST['password'] ?? '');

    $pdo = Database::getConnection();
    $stmt = $pdo->prepare('SELECT id, full_name, email, password_hash, role FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        Auth::login($user);
        header('Location: dashboard.php');
        exit;
    }

    $error = 'Неверный email или пароль.';
}

$pageTitle = 'Вход в систему';
require __DIR__ . '/includes/header.php';
?>

<section class="form-container">
    <p class="eyebrow">PRIVATE ACCESS / ВХОД</p>
    <h1>Вход в галерею</h1>
    <p class="muted">Введите учётные данные для доступа к закрытым разделам.</p>

    <?php if ($error): ?>
        <div class="alert error"><?= Security::escape($error) ?></div>
    <?php endif; ?>

    <form method="post" class="editorial-form">
        <input type="hidden" name="csrf_token" value="<?= Security::escape(Security::getCsrfToken()) ?>">
        <label>Email
            <input type="email" name="email" required value="<?= Security::escape($email) ?>">
        </label>
        <label>Пароль
            <input type="password" name="password" required>
        </label>
        <button type="submit" class="button button-full">Войти</button>
    </form>

    <div class="form-links-row">
        <a href="forgot_password.php">Забыли пароль?</a>
        <a href="register.php">Создать аккаунт</a>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
