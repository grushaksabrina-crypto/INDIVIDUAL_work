<?php
declare(strict_types=1);

require_once __DIR__ . '/config/Database.php';
require_once __DIR__ . '/core/Security.php';
require_once __DIR__ . '/core/Auth.php';

if (Auth::check()) {
    header('Location: dashboard.php');
    exit;
}

$errors = [];
$fullName = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::verifyCsrf($_POST['csrf_token'] ?? null);

    $fullName = trim((string)($_POST['full_name'] ?? ''));
    $email = trim((string)($_POST['email'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $confirm = (string)($_POST['confirm_password'] ?? '');

    if (mb_strlen($fullName) < 2 || mb_strlen($fullName) > 100) {
        $errors[] = 'Имя должно содержать от 2 до 100 символов.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Введите корректный адрес электронной почты.';
    }
    if (strlen($password) < 8) {
        $errors[] = 'Пароль должен содержать минимум 8 символов.';
    }
    if ($password !== $confirm) {
        $errors[] = 'Введённые пароли не совпадают.';
    }

    if (!$errors) {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);

        if ($stmt->fetch()) {
            $errors[] = 'Учётная запись с таким email уже зарегистрирована.';
        } else {
            $count = (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
            $role = ($count === 0) ? 'admin' : 'user';

            $insert = $pdo->prepare('INSERT INTO users (full_name, email, password_hash, role) VALUES (?, ?, ?, ?)');
            $insert->execute([
                $fullName,
                $email,
                password_hash($password, PASSWORD_DEFAULT),
                $role,
            ]);

            Security::setFlash('success', 'Регистрация завершена. Теперь вы можете войти в закрытый кабинет.');
            header('Location: login.php');
            exit;
        }
    }
}

$pageTitle = 'Регистрация';
require __DIR__ . '/includes/header.php';
?>

<section class="form-container">
    <p class="eyebrow">MEMBERSHIP / РЕГИСТРАЦИЯ</p>
    <h1>Создать учётную запись</h1>
    <p class="muted">Получите доступ к публикации работ и управлению личной коллекцией.</p>

    <?php if ($errors): ?>
        <div class="alert error">
            <ul><?php foreach ($errors as $err): ?><li><?= Security::escape($err) ?></li><?php endforeach; ?></ul>
        </div>
    <?php endif; ?>

    <form method="post" class="editorial-form">
        <input type="hidden" name="csrf_token" value="<?= Security::escape(Security::getCsrfToken()) ?>">
        <label>Имя и фамилия
            <input type="text" name="full_name" required minlength="2" maxlength="100" value="<?= Security::escape($fullName) ?>">
        </label>
        <label>Электронная почта (Email)
            <input type="email" name="email" required value="<?= Security::escape($email) ?>">
        </label>
        <label>Пароль (минимум 8 символов)
            <input type="password" name="password" required minlength="8">
        </label>
        <label>Подтверждение пароля
            <input type="password" name="confirm_password" required minlength="8">
        </label>
        <button type="submit" class="button button-full">Зарегистрироваться</button>
    </form>
    <p class="form-footer-note">Уже есть доступ? <a href="login.php">Войти в систему</a></p>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
