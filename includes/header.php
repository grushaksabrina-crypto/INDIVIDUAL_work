<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/Security.php';
require_once __DIR__ . '/../core/Auth.php';

Security::initSession();
$basePath = $basePath ?? '';
$flash = Security::getFlash();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= Security::escape($pageTitle ?? 'Галерея') ?> — VERDANT ARCHIVE</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;1,400&family=Inter:wght@300;400;500&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="<?= $basePath ?>assets/style.css">
</head>
<body>

<header class="site-header">
    <div class="container header-inner">
        <a href="<?= $basePath ?>index.php" class="brand">
            <span class="brand-mark"></span>
            <span class="brand-text">
                <strong>VERDANT ARCHIVE</strong>
                <small>A ROOM FOR LOOKING LONGER</small>
            </span>
        </a>

        <nav class="main-nav">
            <a href="<?= $basePath ?>index.php#collection">Коллекция</a>

            <?php if (Auth::check()): ?>
                <a href="<?= $basePath ?>add_artwork.php">Добавить работу</a>
                <a href="<?= $basePath ?>dashboard.php">Кабинет</a>

                <?php if (Auth::isAdmin()): ?>
                    <a href="<?= $basePath ?>admin/index.php" class="nav-accent">Кураторская (Admin)</a>
                <?php endif; ?>

                <a href="<?= $basePath ?>logout.php" class="nav-pill">Выйти</a>
            <?php else: ?>
                <a href="<?= $basePath ?>login.php">Войти</a>
                <a href="<?= $basePath ?>register.php" class="nav-pill">Регистрация</a>
            <?php endif; ?>
        </nav>
    </div>
</header>

<main class="container">
    <?php if ($flash): ?>
        <div class="alert <?= Security::escape($flash['type']) ?>">
            <?= Security::escape($flash['message']) ?>
        </div>
    <?php endif; ?>
