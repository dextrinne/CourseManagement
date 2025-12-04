<?php
// require_once ('session.php');
// require_once ('functions.php');
require_once __DIR__ . '/init.php';

if (!isLoggedIn()) {
    header('Location: /lms/pages/login.php');
    exit();
}

$user_id = getCurrentUserId();
$unread_notifications = getUnreadNotifications($user_id, 5);
$notification_count = count($unread_notifications);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Система управления учебным процессом</title>
    <link rel="stylesheet" href="/lms/assets/css/style.css">
    <link rel="stylesheet" href="/lms/assets/css/styleReports.css">
    <link rel="stylesheet" href="/lms/assets/css/styleNotifications.css">
    <link rel="stylesheet" href="/lms/assets/css/styleCourses.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <header class="header">
            <div class="logo">
                <i class="fas fa-graduation-cap"></i>
                <h1>Учебный портал</h1>
            </div>
            <nav class="main-nav">
                <ul>
                    <li><a href="/lms/pages/dashboard.php"><i class="fas fa-home"></i> Главная</a></li>
                    <?php if (isTeacher()): ?>
                    <li><a href="/lms/pages/reports.php"><i class="fas fa-chart-bar"></i> Отчеты</a></li>
                    <?php endif; ?>
                    <li>
                        <a href="/lms/pages/notifications.php" class="notification-link">
                            <i class="fas fa-bell"></i> Уведомления
                            <?php if ($notification_count > 0): ?>
                            <span class="notification-badge"><?php echo $notification_count; ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li><a href="/lms/pages/logout.php"><i class="fas fa-sign-out-alt"></i> Выход</a></li>
                </ul>
            </nav>
        </header>

        <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success">
            <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
        </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-error">
            <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
        </div>
        <?php endif; ?>

        <main class="main-content"></main>