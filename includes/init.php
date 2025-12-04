<?php
// includes/init.php

// Определяем корневую директорию
define('ROOT_PATH', dirname(__DIR__));

// Автозагрузка необходимых файлов
$required_files = [
    ROOT_PATH . '\config\database.php',
    __DIR__ . '\session.php',
    __DIR__ . '\functions.php'
];

foreach ($required_files as $file) {
    if (file_exists($file)) {
        require_once $file;
    } else {
        die("Ошибка: файл $file не найден");
    }
}

// Инициализация сессии
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>