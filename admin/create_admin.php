```php
<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../core/Security.php';
require_once __DIR__ . '/../core/Auth.php';

Auth::requireAdmin('../index.php');

$errors = [];
$fullName = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::verifyCsrf($_POST['csrf_token'] ?? null);

    $fullName = trim((string)($_POST['full_name'] ?? ''));
    $email = trim((string)($_POST['email'] ?? ''));
    $password = (string)($_POST['password'] ?? '');

    if (mb_strlen($fullName) < 2 || mb_strlen($fullName) > 100) {
        $errors[] = 'Имя должно содержать от 2 до 100 символов.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Введите корректный email.';
    }
    if (strlen($password) < 8) {
        $errors[] = 'Пароль должен содержать минимум 8 символов.';
    }

    if (!$errors) {
        $pdo = Database::getConnection();
        $check = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $check->execute([$email]);

        if ($check->fetch()) {
            $errors[] = 'Пользователь с таким email уже существует.';
        } else {
            $stmt = $pdo->prepare(
                "INSERT INTO users (full_name, email, password_hash, role)
                VALUES (?, ?, ?, 'admin')"
            );
            $stmt->execute([
                $fullName,
                $email,
                password_hash($password, PASSWORD_DEFAULT)
            ]);

            Security::setFlash('success', "Новый администратор «{$fullName}» успешно создан.");
            header('Location: index.php');
            exit;
        }
    }
}

$basePath = '../';
$pageTitle = 'Создать администратора';
require __DIR__ . '/../includes/header.php';
?>

<section class="form-container">
    <a href="index.php" class="back-link">← Назад в панель администратора</a>
    <p class="eyebrow">PRIVILEGED ACCESS / НОВЫЙ КУРАТОР</p>
    <h1>Создать администратора</h1>

    <?php if ($errors): ?>
        <div class="alert error">
            <ul>
                <?php foreach ($errors as $err): ?>
                    <li><?= Security::escape($err) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="post" class="editorial-form">
        <input type="hidden" name="csrf_token"
               value="<?= Security::escape(Security::getCsrfToken()) ?>">

        <label>Имя куратора
            <input type="text" name="full_name" required minlength="2"
                   maxlength="100" value="<?= Security::escape($fullName) ?>">
        </label>

        <label>Email
            <input type="email" name="email" required
                   value="<?= Security::escape($email) ?>">
        </label>

        <label>Временный пароль (минимум 8 символов)
            <input type="password" name="password" required minlength="8">
        </label>

        <button type="submit" class="button button-full">
            Назначить администратором
        </button>
    </form>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
```
