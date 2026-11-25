```php
<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../core/Security.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Validator.php';

Auth::requireAdmin('../index.php');
$pdo = Database::getConnection();

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    header('Location: index.php');
    exit;
}

$stmt = $pdo->prepare('SELECT * FROM artworks WHERE id = ?');
$stmt->execute([$id]);
$art = $stmt->fetch();

if (!$art) {
    Security::setFlash('error', 'Экспонат не найден.');
    header('Location: index.php');
    exit;
}

$errors = [];
$data = [
    'title'         => (string)$art['title'],
    'artist'        => (string)$art['artist'],
    'medium'        => (string)$art['medium'],
    'dimensions'    => (string)$art['dimensions'],
    'creation_year' => (string)$art['creation_year'],
    'status'        => (string)$art['status'],
    'description'   => (string)$art['description'],
    'image_url'     => (string)($art['image_url'] ?? ''),
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::verifyCsrf($_POST['csrf_token'] ?? null);

    foreach ($data as $key => $_) {
        $data[$key] = trim((string)($_POST[$key] ?? ''));
    }

    $errors = Validator::validateArtwork($data);

    if (!$errors) {
        $update = $pdo->prepare("
            UPDATE artworks
            SET title = ?, artist = ?, medium = ?, dimensions = ?, creation_year = ?, status = ?, description = ?, image_url = ?
            WHERE id = ?
        ");

        $update->execute([
            $data['title'],
            $data['artist'],
            $data['medium'],
            $data['dimensions'],
            (int)$data['creation_year'],
            $data['status'],
            $data['description'],
            $data['image_url'] !== '' ? $data['image_url'] : null,
            $id,
        ]);

        Security::setFlash('success', 'Изменения в карточке экспоната сохранены.');
        header('Location: index.php');
        exit;
    }
}

$basePath = '../';
$pageTitle = 'Редактирование экспоната';
require __DIR__ . '/../includes/header.php';
?>

<section class="form-container form-wide">
    <a href="index.php" class="back-link">← Назад в панель администратора</a>
    <p class="eyebrow">EDIT RECORD / РЕДАКТИРОВАНИЕ #<?= (int)$id ?></p>
    <h1>Редактировать экспонат</h1>

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

        <div class="form-row">
            <label>Название произведения
                <input type="text" name="title" required minlength="2"
                       maxlength="160" value="<?= Security::escape($data['title']) ?>">
            </label>

            <label>Автор / Художник
                <input type="text" name="artist" required minlength="2"
                       maxlength="120" value="<?= Security::escape($data['artist']) ?>">
            </label>
        </div>

        <div class="form-row">
            <label>Техника исполнения
                <select name="medium" required>
                    <?php foreach (Validator::allowedMediums() as $m): ?>
                        <option value="<?= Security::escape($m) ?>"
                            <?= $data['medium'] === $m ? 'selected' : '' ?>>
                            <?= Security::escape($m) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label>Габариты
                <input type="text" name="dimensions" required maxlength="60"
                       value="<?= Security::escape($data['dimensions']) ?>">
            </label>
        </div>

        <div class="form-row">
            <label>Год создания
                <input type="number" name="creation_year" required
                       min="1200" max="<?= date('Y') ?>"
                       value="<?= Security::escape($data['creation_year']) ?>">
            </label>

            <label>Ссылка на изображение (URL)
                <input type="url" name="image_url"
                       value="<?= Security::escape($data['image_url']) ?>">
            </label>
        </div>

        <fieldset class="radio-group">
            <legend>Статус в каталоге</legend>
            <div class="radio-options">
                <?php foreach (Validator::allowedStatuses() as $key => $label): ?>
                    <label class="radio-item">
                        <input type="radio" name="status"
                               value="<?= Security::escape($key) ?>"
                               <?= $data['status'] === $key ? 'checked' : '' ?>
                               required>
                        <span><?= Security::escape($label) ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
        </fieldset>

        <label>Кураторское описание
            <textarea name="description" rows="4" required
                      minlength="10" maxlength="2000"><?= Security::escape($data['description']) ?></textarea>
        </label>

        <button type="submit" class="button button-full">
            Сохранить изменения
        </button>
    </form>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
```
