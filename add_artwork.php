<?php
declare(strict_types=1);

require_once __DIR__ . '/config/Database.php';
require_once __DIR__ . '/core/Security.php';
require_once __DIR__ . '/core/Auth.php';
require_once __DIR__ . '/core/Validator.php';

Auth::requireLogin();

$errors = [];
$data = [
    'title'         => '',
    'artist'        => '',
    'medium'        => '',
    'dimensions'    => '',
    'creation_year' => date('Y'),
    'status'        => 'exhibition',
    'description'   => '',
    'image_url'     => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::verifyCsrf($_POST['csrf_token'] ?? null);

    foreach ($data as $key => $_) {
        $data[$key] = trim((string)($_POST[$key] ?? ''));
    }

    $errors = Validator::validateArtwork($data);

    if (!$errors) {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            INSERT INTO artworks (title, artist, medium, dimensions, creation_year, status, description, image_url, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $data['title'],
            $data['artist'],
            $data['medium'],
            $data['dimensions'],
            (int)$data['creation_year'],
            $data['status'],
            $data['description'],
            $data['image_url'] !== '' ? $data['image_url'] : null,
            Auth::user()['id'],
        ]);

        Security::setFlash('success', 'Экспонат успешно включён в каталог галереи.');
        header('Location: index.php#collection');
        exit;
    }
}

$pageTitle = 'Добавить экспонат';
require __DIR__ . '/includes/header.php';
?>

<section class="form-container form-wide">
    <p class="eyebrow">NEW ACQUISITION / НОВАЯ РАБОТА</p>
    <h1>Добавить экспонат в архив</h1>
    <p class="muted">Заполните кураторскую карточку произведения.</p>

    <?php if ($errors): ?>
        <div class="alert error">
            <ul><?php foreach ($errors as $err): ?><li><?= Security::escape($err) ?></li><?php endforeach; ?></ul>
        </div>
    <?php endif; ?>

    <form method="post" class="editorial-form">
        <input type="hidden" name="csrf_token" value="<?= Security::escape(Security::getCsrfToken()) ?>">

        <div class="form-row">
            <label>Название произведения
                <input type="text" name="title" required minlength="2" maxlength="160" value="<?= Security::escape($data['title']) ?>">
            </label>
            <label>Автор / Художник
                <input type="text" name="artist" required minlength="2" maxlength="120" value="<?= Security::escape($data['artist']) ?>">
            </label>
        </div>

        <div class="form-row">
            <label>Техника исполнения
                <select name="medium" required>
                    <option value="">Выберите технику</option>
                    <?php foreach (Validator::allowedMediums() as $m): ?>
                        <option value="<?= Security::escape($m) ?>" <?= $data['medium'] === $m ? 'selected' : '' ?>>
                            <?= Security::escape($m) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Габариты
                <input type="text" name="dimensions" required maxlength="60" placeholder="Например: 60 × 80 см" value="<?= Security::escape($data['dimensions']) ?>">
            </label>
        </div>

        <div class="form-row">
            <label>Год создания
                <input type="number" name="creation_year" required min="1200" max="<?= date('Y') ?>" value="<?= Security::escape((string)$data['creation_year']) ?>">
            </label>
            <label>Ссылка на изображение (URL)
                <input type="url" name="image_url" placeholder="https://images.unsplash.com/..." value="<?= Security::escape($data['image_url']) ?>">
            </label>
        </div>

        <fieldset class="radio-group">
            <legend>Статус в каталоге</legend>
            <div class="radio-options">
                <?php foreach (Validator::allowedStatuses() as $key => $label): ?>
                    <label class="radio-item">
                        <input type="radio" name="status" value="<?= Security::escape($key) ?>" <?= $data['status'] === $key ? 'checked' : '' ?> required>
                        <span><?= Security::escape($label) ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
        </fieldset>

        <label>Кураторское описание
            <textarea name="description" rows="4" required minlength="10" maxlength="2000"><?= Security::escape($data['description']) ?></textarea>
        </label>

        <button type="submit" class="button button-full">Опубликовать в экспозиции</button>
    </form>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
