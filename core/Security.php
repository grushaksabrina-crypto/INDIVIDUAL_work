<?php
declare(strict_types=1);

/**
 * Класс обеспечения безопасности: экранирование вывода (XSS),
 * генерация и проверка CSRF-токенов, управление flash-сообщениями.
 *
 * @package VerdantArchive\Core
 */
class Security
{
    /**
     * Инициализирует защищённую PHP-сессию, если она ещё не запущена.
     *
     * @return void
     */
    public static function initSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start([
                'cookie_httponly' => true,
                'cookie_samesite' => 'Lax',
            ]);
        }
    }

    /**
     * Безопасно экранирует строку для вывода в HTML-шаблоне (защита от XSS).
     *
     * @param string|null $value Исходная строка
     * @return string Экранированная строка
     */
    public static function escape(?string $value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /**
     * Возвращает текущий CSRF-токен сессии или генерирует новый.
     *
     * @return string Шестнадцатеричный CSRF-токен длиной 64 символа
     */
    public static function getCsrfToken(): string
    {
        self::initSession();

        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['csrf_token'];
    }

    /**
     * Проверяет валидность переданного CSRF-токена из POST-запроса.
     *
     * @param string|null $token Токен из формы
     * @return void Завершает выполнение скрипта с кодом 403 при несовпадении
     */
    public static function verifyCsrf(?string $token): void
    {
        self::initSession();

        if (!$token || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
            http_response_code(403);
            exit('Ошибка безопасности: недействительный CSRF-токен.');
        }
    }

    /**
     * Сохраняет одноразовое уведомление в сессию.
     *
     * @param string $type Тип сообщения ('success' или 'error')
     * @param string $message Текст уведомления
     * @return void
     */
    public static function setFlash(string $type, string $message): void
    {
        self::initSession();

        $_SESSION['flash'] = ['type' => $type, 'message' => $message];
    }

    /**
     * Извлекает и удаляет одноразовое уведомление из сессии.
     *
     * @return array{type: string, message: string}|null Массив уведомления или null
     */
    public static function getFlash(): ?array
    {
        self::initSession();

        if (!empty($_SESSION['flash'])) {
            $flash = $_SESSION['flash'];
            unset($_SESSION['flash']);

            return $flash;
        }

        return null;
    }
}
