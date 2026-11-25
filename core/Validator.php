<?php
declare(strict_types=1);

/**
 * Класс серверной валидации данных форм и справочников предметной области.
 *
 * @package VerdantArchive\Core
 */
class Validator
{
    /**
     * Возвращает список допустимых художественных техник для каталога.
     *
     * @return array<int, string> Массив названий техник
     */
    public static function allowedMediums(): array
    {
        return [
            'Масло, холст',
            'Темпера, дерево',
            'Архитектурная графика',
            'Скульптура, бронза',
            'Акварель, бумага',
            'Смешанная техника',
        ];
    }

    /**
     * Возвращает словарь статусов экспоната с человекочитаемыми подписями.
     *
     * @return array<string, string> Ассоциативный массив [код => описание]
     */
    public static function allowedStatuses(): array
    {
        return [
            'exhibition' => 'В постоянной экспозиции',
            'private'    => 'Частная коллекция',
            'available'  => 'Доступно для приобретения',
        ];
    }

    /**
     * Валидирует поля формы создания или редактирования экспоната.
     *
     * @param array<string, string> $data Очищенные данные формы
     * @return array<int, string> Список сообщений об ошибках (пустой при успехе)
     */
    public static function validateArtwork(array $data): array
    {
        $errors = [];

        if (mb_strlen($data['title']) < 2 || mb_strlen($data['title']) > 160) {
            $errors[] = 'Название работы должно содержать от 2 до 160 символов.';
        }

        if (mb_strlen($data['artist']) < 2 || mb_strlen($data['artist']) > 120) {
            $errors[] = 'Имя автора должно содержать от 2 до 120 символов.';
        }

        if (!in_array($data['medium'], self::allowedMediums(), true)) {
            $errors[] = 'Выберите корректную технику исполнения из списка.';
        }

        if ($data['dimensions'] === '' || mb_strlen($data['dimensions']) > 60) {
            $errors[] = 'Укажите габариты экспоната (до 60 символов, например: 60 × 80 см).';
        }

        $year = filter_var($data['creation_year'], FILTER_VALIDATE_INT);
        $currentYear = (int)date('Y');

        if ($year === false || $year < 1200 || $year > $currentYear) {
            $errors[] = "Год создания должен быть целым числом от 1200 до {$currentYear}.";
        }

        if (!array_key_exists($data['status'], self::allowedStatuses())) {
            $errors[] = 'Выберите корректный статус экспоната.';
        }

        if (mb_strlen($data['description']) < 10 || mb_strlen($data['description']) > 2000) {
            $errors[] = 'Кураторское описание должно содержать от 10 до 2000 символов.';
        }

        if ($data['image_url'] !== '') {
            $scheme = parse_url($data['image_url'], PHP_URL_SCHEME);

            if (!filter_var($data['image_url'], FILTER_VALIDATE_URL) || !in_array($scheme, ['http', 'https'], true)) {
                $errors[] = 'Ссылка на изображение должна начинаться с http:// или https://.';
            }
        }

        return $errors;
    }
}
