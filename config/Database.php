```php
<?php
declare(strict_types=1);

/**
 * Класс управления подключением к базе данных MySQL через PDO.
 * Реализует паттерн Singleton для переиспользования одного соединения.
 *
 * @package VerdantArchive\Config
 */
class Database
{
    /** @var string Адрес сервера базы данных */
    private static string $host = 'localhost';

    /** @var string Имя базы данных */
    private static string $dbName = 'verdant_archive';

    /** @var string Имя пользователя БД */
    private static string $username = 'root';

    /** @var string Пароль пользователя БД */
    private static string $password = '';

    /** @var PDO|null Единственный экземпляр подключения PDO */
    private static ?PDO $instance = null;

    /**
     * Возвращает активное PDO-соединение с базой данных.
     *
     * @return PDO Экземпляр настроенного соединения PDO
     * @throws PDOException При ошибке подключения к серверу MySQL
     */
    public static function getConnection(): PDO
    {
        if (self::$instance === null) {
            $dsn = sprintf(
                'mysql:host=%s;dbname=%s;charset=utf8mb4',
                self::$host,
                self::$dbName
            );

            self::$instance = new PDO($dsn, self::$username, self::$password, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        }

        return self::$instance;
    }
}
```
