<?php
declare(strict_types=1);

require_once __DIR__ . '/config/Database.php';
require_once __DIR__ . '/core/Security.php';
require_once __DIR__ . '/core/Validator.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    header('Location: index.php');
    exit;
}

$pdo = Database::getConnection();
$stmt = $pdo->prepare("
    SELECT a.*, u.full_name AS curator_name
    FROM artworks a
    LEFT JOIN users u ON u.id = a.created_by
    WHERE a.id = ?
");
$stmt->execute([$id]);
$art = $stmt->fetch();

if (!$art) {
    http_response_code(404);
    Security::setFlash('error', 'Запрашиваемый экспонат не найден в архиве.');
    header('Location: index.php');
    exit;
}

$statuses = Validator::allowedStatuses();
$pageTitle = $art['title'];
require __DIR__ . '/includes/header.php';
?>

<section class="single-artwork">
    <a href="index.php#collection" class="back-link">← Вернуться в коллекцию</a>

    <div class="single-grid">
        <div class="single-frame">
            <?php if (!empty($art['image_url'])): ?>
                <img src="<?= Security::escape($art['image_url']) ?>" alt="<?= Security::escape($art['title']) ?>">
            <?php else: ?>
                <div class="thumb-placeholder large">VERDANT ARCHIVE / NO. <?= (int)$art['id'] ?></div>
            <?php endif; ?>
        </div>

        <div class="single-details">
            <p class="eyebrow">THE COLLECTION / #<?= str_pad((string)$art['id'], 2, '0', STR_PAD_LEFT) ?></p>
            <h1><?= Security::escape($art['title']) ?></h1>
            <p class="single-artist"><?= Security::escape($art['artist']) ?></p>
            <p class="single-desc"><?= nl2br(Security::escape($art['description'])) ?></p>

            <dl class="spec-table">
                <div class="spec-row">
                    <dt>Техника</dt>
                    <dd><?= Security::escape($art['medium']) ?></dd>
                </div>
                <div class="spec-row">
                    <dt>Габариты</dt>
                    <dd><?= Security::escape($art['dimensions']) ?></dd>
                </div>
                <div class="spec-row">
                    <dt>Год создания</dt>
                    <dd><?= (int)$art['creation_year'] ?></dd>
                </div>
                <div class="spec-row">
                    <dt>Статус</dt>
                    <dd><?= Security::escape($statuses[$art['status']] ?? $art['status']) ?></dd>
                </div>
                <?php if (!empty($art['curator_name'])): ?>
                <div class="spec-row">
                    <dt>Куратор архива</dt>
                    <dd><?= Security::escape($art['curator_name']) ?></dd>
                </div>
                <?php endif; ?>
            </dl>
        </div>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
