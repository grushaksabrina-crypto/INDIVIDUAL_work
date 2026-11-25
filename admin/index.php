```php
<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../core/Security.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Validator.php';

Auth::requireAdmin('../index.php');
$pdo = Database::getConnection();
$currentUser = Auth::user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::verifyCsrf($_POST['csrf_token'] ?? null);
    $action = $_POST['action'] ?? '';

    if ($action === 'delete_artwork') {
        $artId = filter_input(INPUT_POST, 'artwork_id', FILTER_VALIDATE_INT);
        if ($artId) {
            $pdo->prepare('DELETE FROM artworks WHERE id = ?')->execute([$artId]);
            Security::setFlash('success', 'Экспонат удалён из базы данных.');
        }
    } elseif ($action === 'toggle_role') {
        $targetId = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
        if ($targetId && $targetId !== (int)$currentUser['id']) {
            $stmt = $pdo->prepare('SELECT role FROM users WHERE id = ?');
            $stmt->execute([$targetId]);
            $target = $stmt->fetch();

            if ($target) {
                $newRole = ($target['role'] === 'admin') ? 'user' : 'admin';
                $pdo->prepare('UPDATE users SET role = ? WHERE id = ?')->execute([$newRole, $targetId]);
                Security::setFlash('success', "Роль пользователя обновлена на «{$newRole}».");
            }
        }
    } elseif ($action === 'delete_user') {
        $targetId = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
        if ($targetId && $targetId !== (int)$currentUser['id']) {
            $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$targetId]);
            Security::setFlash('success', 'Учётная запись пользователя удалена.');
        }
    }

    header('Location: index.php');
    exit;
}

$artworks = $pdo->query("
    SELECT a.*, u.full_name AS curator_name
    FROM artworks a
    LEFT JOIN users u ON u.id = a.created_by
    ORDER BY a.id DESC
")->fetchAll();

$users = $pdo->query('SELECT id, full_name, email, role, created_at FROM users ORDER BY id ASC')->fetchAll();
$statuses = Validator::allowedStatuses();

$basePath = '../';
$pageTitle = 'Админ-панель куратора';
require __DIR__ . '/../includes/header.php';
?>

<section class="dashboard-section">
    <div class="dashboard-header">
        <div>
            <p class="eyebrow">CURATORIAL CONTROL / АДМИНИСТРИРОВАНИЕ</p>
            <h1>Управление архивом</h1>
        </div>
        <div class="dashboard-actions">
            <a href="../add_artwork.php" class="button">+ Новый экспонат</a>
            <a href="create_admin.php" class="button button-outline">+ Создать администратора</a>
        </div>
    </div>

    <div class="stats-row">
        <div class="stat-box">
            <strong><?= count($artworks) ?></strong>
            <span>Всего экспонатов в БД</span>
        </div>
        <div class="stat-box">
            <strong><?= count($users) ?></strong>
            <span>Зарегистрированных участников</span>
        </div>
    </div>

    <h2 class="sub-heading">Каталог экспонатов (редактирование и удаление)</h2>
    <div class="table-wrap">
        <table class="editorial-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Название</th>
                    <th>Автор</th>
                    <th>Техника</th>
                    <th>Статус</th>
                    <th>Куратор</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($artworks as $art): ?>
                    <tr>
                        <td>#<?= (int)$art['id'] ?></td>
                        <td><strong><?= Security::escape($art['title']) ?></strong></td>
                        <td><?= Security::escape($art['artist']) ?></td>
                        <td><?= Security::escape($art['medium']) ?></td>
                        <td><?= Security::escape($statuses[$art['status']] ?? $art['status']) ?></td>
                        <td><?= Security::escape($art['curator_name'] ?? 'Архив') ?></td>
                        <td class="actions-cell">
                            <a href="edit_artwork.php?id=<?= (int)$art['id'] ?>" class="table-link">Изменить</a>
                            <form method="post" style="display:inline;" onsubmit="return confirm('Удалить этот экспонат?');">
                                <input type="hidden" name="csrf_token" value="<?= Security::escape(Security::getCsrfToken()) ?>">
                                <input type="hidden" name="action" value="delete_artwork">
                                <input type="hidden" name="artwork_id" value="<?= (int)$art['id'] ?>">
                                <button type="submit" class="link-danger">Удалить</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <h2 class="sub-heading" style="margin-top: 56px;">Учётные записи и роли</h2>
    <div class="table-wrap">
        <table class="editorial-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Имя</th>
                    <th>Email</th>
                    <th>Роль</th>
                    <th>Управление</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                    <tr>
                        <td>#<?= (int)$u['id'] ?></td>
                        <td><?= Security::escape($u['full_name']) ?></td>
                        <td><?= Security::escape($u['email']) ?></td>
                        <td><span class="role-badge role-<?= Security::escape($u['role']) ?>"><?= Security::escape($u['role']) ?></span></td>
                        <td class="actions-cell">
                            <?php if ((int)$u['id'] !== (int)$currentUser['id']): ?>
                                <form method="post" style="display:inline;">
                                    <input type="hidden" name="csrf_token" value="<?= Security::escape(Security::getCsrfToken()) ?>">
                                    <input type="hidden" name="action" value="toggle_role">
                                    <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                                    <button type="submit" class="table-link">Сменить роль</button>
                                </form>
                                <form method="post" style="display:inline;" onsubmit="return confirm('Удалить пользователя?');">
                                    <input type="hidden" name="csrf_token" value="<?= Security::escape(Security::getCsrfToken()) ?>">
                                    <input type="hidden" name="action" value="delete_user">
                                    <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                                    <button type="submit" class="link-danger">Удалить</button>
                                </form>
                            <?php else: ?>
                                <span class="muted">Текущий сеанс</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
```
