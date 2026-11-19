<?php
declare(strict_types=1);

require_once __DIR__ . '/config/Database.php';
require_once __DIR__ . '/core/Security.php';
require_once __DIR__ . '/core/Validator.php';

$pdo = Database::getConnection();

$statsRow = $pdo->query("
    SELECT 
        COUNT(*) AS total_works,
        COUNT(DISTINCT artist) AS total_artists,
        SUM(CASE WHEN status = 'exhibition' THEN 1 ELSE 0 END) AS on_display
    FROM artworks
")->fetch();

$query = trim((string)($_GET['q'] ?? ''));
$medium = trim((string)($_GET['medium'] ?? ''));
$status = trim((string)($_GET['status'] ?? ''));

$sql = "SELECT a.*, u.full_name AS curator_name
        FROM artworks a
        LEFT JOIN users u ON u.id = a.created_by
        WHERE 1=1";
$params = [];

if ($query !== '') {
    $sql .= " AND (a.title LIKE :q1 OR a.artist LIKE :q2 OR a.description LIKE :q3)";
    $like = '%' . $query . '%';
    $params['q1'] = $like;
    $params['q2'] = $like;
    $params['q3'] = $like;
}
if ($medium !== '' && in_array($medium, Validator::allowedMediums(), true)) {
    $sql .= " AND a.medium = :medium";
    $params['medium'] = $medium;
}
if ($status !== '' && array_key_exists($status, Validator::allowedStatuses())) {
    $sql .= " AND a.status = :status";
    $params['status'] = $status;
}

$sql .= " ORDER BY a.created_at DESC, a.id DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$artworks = $stmt->fetchAll();

$statuses = Validator::allowedStatuses();
$pageTitle = 'Частная экспозиция';
require __DIR__ . '/includes/header.php';
?>

<section class="hero-gallery">
    <div class="hero-top-meta">
        <span>PRIVATE VIEW / NO. 01</span>
        <span>ONLINE EXHIBITION &amp; ARCHIVE</span>
    </div>

    <div class="hero-grid">
        <div class="hero-copy">
            <p class="eyebrow">SOME THINGS ASK YOU TO SLOW DOWN</p>
            <h1>Время движется.<br><em>Искусство остаётся с нами.</em></h1>
            <p class="hero-lead">
                Не просто лента изображений, а пространство для вдумчивого созерцания.
                Кураторский архив живописи, графики и коллекционных объектов.
            </p>
            <div class="hero-actions">
                <a href="#collection" class="button">Войти в коллекцию ↗</a>
                <div class="hero-counters">
                    <div><strong><?= (int)($statsRow['total_works'] ?? 0) ?></strong><span>Экспонатов</span></div>
                    <div><strong><?= (int)($statsRow['total_artists'] ?? 0) ?></strong><span>Авторов</span></div>
                    <div><strong><?= (int)($statsRow['on_display'] ?? 0) ?></strong><span>В зале</span></div>
                </div>
            </div>
        </div>

        <div class="hero-frame">
            <img src="https://images.unsplash.com/photo-1579783902614-a3fb3927b6a5?auto=format&fit=crop&w=1100&q=85" alt="Главный экспонат зала">
        </div>
    </div>
</section>

<section class="collection-section" id="collection">
    <div class="section-header">
        <div>
            <p class="eyebrow">THE COLLECTION / КАТАЛОГ</p>
            <h2>Экспозиция архива</h2>
        </div>
        <span class="muted">Найдено работ: <?= count($artworks) ?></span>
    </div>

    <form method="get" action="index.php#collection" class="filter-bar">
        <input type="search" name="q" value="<?= Security::escape($query) ?>" placeholder="Поиск по названию, автору или описанию...">
        <select name="medium">
            <option value="">Все техники</option>
            <?php foreach (Validator::allowedMediums() as $item): ?>
                <option value="<?= Security::escape($item) ?>" <?= $medium === $item ? 'selected' : '' ?>>
                    <?= Security::escape($item) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <select name="status">
            <option value="">Любой статус</option>
            <?php foreach ($statuses as $key => $label): ?>
                <option value="<?= Security::escape($key) ?>" <?= $status === $key ? 'selected' : '' ?>>
                    <?= Security::escape($label) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="button">Найти</button>
        <?php if ($query !== '' || $medium !== '' || $status !== ''): ?>
            <a href="index.php#collection" class="reset-link">Сбросить</a>
        <?php endif; ?>
    </form>

    <?php if ($artworks): ?>
        <div class="artwork-grid">
            <?php foreach ($artworks as $art): ?>
                <article class="artwork-card">
                    <a href="artwork.php?id=<?= (int)$art['id'] ?>" class="artwork-thumb">
                        <?php if (!empty($art['image_url'])): ?>
                            <img src="<?= Security::escape($art['image_url']) ?>" alt="<?= Security::escape($art['title']) ?>">
                        <?php else: ?>
                            <div class="thumb-placeholder">ARCHIVE NO. <?= (int)$art['id'] ?></div>
                        <?php endif; ?>
                    </a>
                    <div class="artwork-body">
                        <div class="artwork-meta">
                            <span><?= Security::escape($art['medium']) ?></span>
                            <span><?= (int)$art['creation_year'] ?></span>
                        </div>
                        <h3>
                            <a href="artwork.php?id=<?= (int)$art['id'] ?>"><?= Security::escape($art['title']) ?></a>
                        </h3>
                        <p class="artist-name"><?= Security::escape($art['artist']) ?></p>
                        <p class="artwork-excerpt"><?= Security::escape($art['description']) ?></p>
                        <div class="artwork-footer">
                            <span class="status-tag status-<?= Security::escape($art['status']) ?>">
                                <?= Security::escape($statuses[$art['status']] ?? $art['status']) ?>
                            </span>
                            <a href="artwork.php?id=<?= (int)$art['id'] ?>" class="read-more">Открыть →</a>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="empty-box">
            <p>По заданным критериям в архиве не найдено ни одной работы.</p>
        </div>
    <?php endif; ?>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
