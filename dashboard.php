<?php
declare(strict_types=1);

require_once __DIR__ . '/config/Database.php';
require_once __DIR__ . '/core/Security.php';
require_once __DIR__ . '/core/Auth.php';
require_once __DIR__ . '/core/Validator.php';

Auth::requireLogin();
$user = Auth::user();
$pdo = Database::getConnection();

$stmt = $pdo->prepare('SELECT * FROM artworks WHERE created_by = ? ORDER BY created_at DESC');
$stmt->execute([$user['id']]);
$myArtworks = $stmt->fetchAll();

$statuses = Validator::allowedStatuses();
$pageTitle = 'Личный кабинет';
require __DIR__ . '/includes/header.php';
?>

<section class="dashboard-section">
    <div class="dashboard-header">
        <div>
            <p class="eyebrow">CURATOR CABINET / ЛИЧНЫЙ КАБИНЕТ</p>
            <h1>Здравствуйте, <?= Security::escape($user['full_name']) ?></h1>
            <p class="muted"><?= Security::escape($user['email']) ?></p>
        </div>
        <div class="dashboard-actions">
            <a href="add_artwork.php" class="button">+ Добавить экспонат</a>
            <?php if (Auth::isAdmin()): ?>
                <a href="admin/index.php" class="button button-outline">Панель администратора</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="stats-row">
        <div class="stat-box">
            <strong><?= count($myArtworks) ?></strong>
            <span>Опубликовано вами работ</span>
        </div>
        <div class="stat-box">
            <strong><?= Auth::isAdmin() ? 'Администратор' : 'Коллекционер' ?></strong>
            <span>Уровень доступа</span>
        </div>
    </div>

    <h2 class="sub-heading">Ваши публикации в архиве</h2>
    <?php if ($myArtworks): ?>
        <div class="table-wrap">
            <table class="editorial-table">
                <thead>
                    <tr>
                        <th>Название</th>
                        <th>Автор</th>
                        <th>Техника</th>
                        <th>Год</th>
                        <th>Статус</th>
                        <th>Действие</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($myArtworks as $item): ?>
                        <tr>
                            <td><strong><?= Security::escape($item['title']) ?></strong></td>
                            <td><?= Security::escape($item['artist']) ?></td>
                            <td><?= Security::escape($item['medium']) ?></td>
                            <td><?= (int)$item['creation_year'] ?></td>
                            <td><?= Security::escape($statuses[$item['status']] ?? $item['status']) ?></td>
                            <td><a href="artwork.php?id=<?= (int)$item['id'] ?>" class="table-link">Просмотр</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="empty-box">
            <p>Вы ещё не добавили ни одного экспоната. <a href="add_artwork.php" style="text-decoration: underline;">Создать первую запись →</a></p>
        </div>
    <?php endif; ?>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
