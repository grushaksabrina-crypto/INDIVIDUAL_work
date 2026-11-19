<?php
declare(strict_types=1);

require_once __DIR__ . '/config/Database.php';
require_once __DIR__ . '/core/Security.php';

$pdo = Database::getConnection();
$token = trim((string)($_GET['token'] ?? $_POST['token'] ?? ''));
$errors = [];
$demoResetLink = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::verifyCsrf($_POST['csrf_token'] ?? null);
    $action = $_POST['action'] ?? 'request';

    if ($action === 'request') {
        $email = trim((string)($_POST['email'] ?? ''));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Введите корректный email.';
        } else {
            $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $newToken = bin2hex(random_bytes(32));
                $expires = date('Y-m-d H:i:s', time() + 3600);

                $pdo->prepare('DELETE FROM password_resets WHERE email = ?')->execute([$email]);
                $pdo->prepare('INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)')
                    ->execute([$email, $newToken, $expires]);

                $demoResetLink = "forgot_password.php?token={$newToken}";
            } else {
                $errors[] = 'Пользователь с таким email не найден.';
            }
        }
    } elseif ($action === 'reset') {
        $newPassword = (string)($_POST['new_password'] ?? '');
        $confirm = (string)($_POST['confirm_password'] ?? '');

        if (strlen($newPassword) < 8) {
            $errors[] = 'Новый пароль должен содержать минимум 8 символов.';
        }
        if ($newPassword !== $confirm) {
            $errors[] = 'Пароли не совпадают.';
        }

        if (!$errors) {
            $stmt = $pdo->prepare('SELECT email FROM password_resets WHERE token = ? AND expires_at > NOW()');
            $stmt->execute([$token]);
            $resetRow = $stmt->fetch();

            if ($resetRow) {
                $pdo->prepare('UPDATE users SET password_hash = ? WHERE email = ?')
                    ->execute([password_hash($newPassword, PASSWORD_DEFAULT), $resetRow['email']]);
                $pdo->prepare('DELETE FROM password_resets WHERE email = ?')->execute([$resetRow['email']]);

                Security::setFlash('success', 'Пароль успешно обновлён. Войдите с новым паролем.');
                header('Location: login.php');
                exit;
            } else {
                $errors[] = 'Срок действия ссылки истёк или токен недействителен.';
            }
        }
    }
}

$pageTitle = 'Восстановление пароля';
require __DIR__ . '/includes/header.php';
?>

<section class="form-container">
    <p class="eyebrow">SECURITY / RECOVERY</p>
    <h1>Восстановление пароля</h1>

    <?php if ($errors): ?>
        <div class="alert error">
            <ul><?php foreach ($errors as $err): ?><li><?= Security::escape($err) ?></li><?php endforeach; ?></ul>
        </div>
    <?php endif; ?>

    <?php if ($demoResetLink): ?>
        <div class="alert success">
            Письмо со ссылкой восстановления сформировано (локальный режим):<br>
            <a href="<?= Security::escape($demoResetLink) ?>" style="text-decoration: underline;">
                Перейти к установке нового пароля →
            </a>
        </div>
    <?php endif; ?>

    <?php if ($token !== ''): ?>
        <p class="muted">Задайте новый пароль для вашей учётной записи.</p>
        <form method="post" class="editorial-form">
            <input type="hidden" name="csrf_token" value="<?= Security::escape(Security::getCsrfToken()) ?>">
            <input type="hidden" name="action" value="reset">
            <input type="hidden" name="token" value="<?= Security::escape($token) ?>">
            <label>Новый пароль (минимум 8 символов)
                <input type="password" name="new_password" required minlength="8">
            </label>
            <label>Повторите новый пароль
                <input type="password" name="confirm_password" required minlength="8">
            </label>
            <button type="submit" class="button button-full">Сохранить новый пароль</button>
        </form>
    <?php else: ?>
        <p class="muted">Укажите email, привязанный к аккаунту, чтобы получить токен сброса пароля.</p>
        <form method="post" class="editorial-form">
            <input type="hidden" name="csrf_token" value="<?= Security::escape(Security::getCsrfToken()) ?>">
            <input type="hidden" name="action" value="request">
            <label>Электронная почта
                <input type="email" name="email" required>
            </label>
            <button type="submit" class="button button-full">Отправить ссылку сброса</button>
        </form>
    <?php endif; ?>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
