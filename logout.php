<?php
declare(strict_types=1);

require_once __DIR__ . '/core/Auth.php';

Auth::logout();
header('Location: index.php');
exit;
