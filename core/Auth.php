```php
<?php
declare(strict_types=1);

require_once __DIR__ . '/Security.php';

/**
 * Класс управления аутентификацией и разграничением прав доступа (RBAC).
 *
 * @package VerdantArchive\Core
 */
class Auth
{
    /**
     * Проверяет, авторизован ли текущий пользователь.
     *
     * @return bool True, если пользователь вошёл в систему
     */
    public static function check(): bool
    {
        Security::initSession();
        return !empty($_SESSION['user']);
    }

    /**
     * Возвращает данные текущего авторизованного пользователя.
     *
     * @return array<string, mixed>|null Данные пользователя или null
     */
    public static function user(): ?array
    {
        Security::initSession();
        return $_SESSION['user'] ?? null;
    }

    /**
     * Проверяет, обладает ли текущий пользователь ролью администратора.
     *
     * @return bool True, если роль пользователя 'admin'
     */
    public static function isAdmin(): bool
    {
        return self::check() && ($_SESSION['user']['role'] ?? '') === 'admin';
    }

    /**
     * Записывает данные пользователя в сессию после успешной проверки пароля.
     *
     * @param array<string, mixed> $userRow Строка пользователя из БД
     * @return void
     */
    public static function login(array $userRow): void
    {
        Security::initSession();
        session_regenerate_id(true);
        unset($userRow['password_hash']);
        $_SESSION['user'] = $userRow;
    }

    /**
     * Полностью завершает сессию пользователя.
     *
     * @return void
     */
    public static function logout(): void
    {
        Security::initSession();
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        session_destroy();
    }

    /**
     * Ограничивает доступ только для авторизованных пользователей.
     *
     * @param string $loginPath Путь к странице входа
     * @return void
     */
    public static function requireLogin(string $loginPath = 'login.php'): void
    {
        if (!self::check()) {
            Security::setFlash('error', 'Для доступа к закрытому разделу выполните вход.');
            header('Location: ' . $loginPath);
            exit;
        }
    }

    /**
     * Ограничивает доступ только для пользователей с ролью 'admin'.
     *
     * @param string $redirectPath Путь перенаправления при отказе в доступе
     * @return void
     */
    public static function requireAdmin(string $redirectPath = '../index.php'): void
    {
        if (!self::isAdmin()) {
            Security::setFlash('error', 'Доступ разрешён только кураторам-администраторам.');
            header('Location: ' . $redirectPath);
            exit;
        }
    }
}
```
