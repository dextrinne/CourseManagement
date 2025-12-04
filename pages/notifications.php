<?php
require_once '../includes/header.php';

$user_id = getCurrentUserId();
$conn = getDBConnection();

// Обработка действий с уведомлениями
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['mark_all_read'])) {
        $sql = "UPDATE notifications SET is_read = TRUE WHERE user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$user_id]);
        $_SESSION['success'] = 'Все уведомления помечены как прочитанные';
        header('Location: notifications.php');
        exit();
    }
    
    if (isset($_POST['mark_read'])) {
        $notification_id = $_POST['notification_id'];
        $sql = "UPDATE notifications SET is_read = TRUE WHERE notification_id = ? AND user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$notification_id, $user_id]);
        $_SESSION['success'] = 'Уведомление помечено как прочитанное';
        header('Location: notifications.php');
        exit();
    }
    
    if (isset($_POST['delete_notification'])) {
        $notification_id = $_POST['notification_id'];
        $sql = "DELETE FROM notifications WHERE notification_id = ? AND user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$notification_id, $user_id]);
        $_SESSION['success'] = 'Уведомление удалено';
        header('Location: notifications.php');
        exit();
    }
    
    if (isset($_POST['delete_all_read'])) {
        $sql = "DELETE FROM notifications WHERE user_id = ? AND is_read = TRUE";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$user_id]);
        $_SESSION['success'] = 'Все прочитанные уведомления удалены';
        header('Location: notifications.php');
        exit();
    }
}

// Получить статистику уведомлений
$sql = "SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN is_read = FALSE THEN 1 ELSE 0 END) as unread,
        SUM(CASE WHEN is_read = TRUE THEN 1 ELSE 0 END) as read_count,
        SUM(CASE WHEN DATE(created_at) = CURDATE() THEN 1 ELSE 0 END) as today
        FROM notifications 
        WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->execute([$user_id]);
$stats = $stmt->fetch();

// Получить все уведомления с пагинацией
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Получить уведомления с фильтрами
$filter = $_GET['filter'] ?? 'all';
$type_filter = $_GET['type'] ?? 'all';

$where_conditions = ["n.user_id = ?"];
$params = [$user_id];

if ($filter === 'unread') {
    $where_conditions[] = "n.is_read = FALSE";
} elseif ($filter === 'read') {
    $where_conditions[] = "n.is_read = TRUE";
}

if ($type_filter !== 'all') {
    $where_conditions[] = "nt.name = ?";
    $params[] = $type_filter;
}

$where_clause = implode(' AND ', $where_conditions);

// Получить уведомления с правильной передачей параметров LIMIT/OFFSET
$sql = "SELECT n.*, nt.name as type_name 
        FROM notifications n
        JOIN notification_type nt ON n.notification_type_id = nt.notification_type_id
        WHERE $where_clause
        ORDER BY n.created_at DESC
        LIMIT ? OFFSET ?";

// Добавляем LIMIT и OFFSET как целые числа
$params_for_query = $params;
$params_for_query[] = (int)$limit;
$params_for_query[] = (int)$offset;

$stmt = $conn->prepare($sql);

// Правильно связываем параметры с типами
foreach ($params_for_query as $key => $value) {
    $param_type = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
    $stmt->bindValue($key + 1, $value, $param_type);
}

$stmt->execute();
$notifications = $stmt->fetchAll();

// Получить общее количество для пагинации
$sql = "SELECT COUNT(*) as total 
        FROM notifications n
        JOIN notification_type nt ON n.notification_type_id = nt.notification_type_id
        WHERE $where_clause";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$total_result = $stmt->fetch();
$total_notifications = $total_result['total'];
$total_pages = ceil($total_notifications / $limit);

// Получить все типы уведомлений для фильтра
$sql = "SELECT DISTINCT name FROM notification_type ORDER BY name";
$stmt = $conn->query($sql);
$notification_types = $stmt->fetchAll();
?>

<div class="dashboard">
    <div class="dashboard-grid">
        <div class="dashboard-card main-card">
            <!-- Статистика уведомлений -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-bell"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $stats['total'] ?? 0; ?></h3>
                        <p>Всего уведомлений</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: #fed7d7; color: #c53030;">
                        <i class="fas fa-exclamation-circle"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $stats['unread'] ?? 0; ?></h3>
                        <p>Непрочитанных</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: #c6f6d5; color: #276749;">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $stats['read_count'] ?? 0; ?></h3>
                        <p>Прочитанных</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: #bee3f8; color: #2c5282;">
                        <i class="fas fa-calendar-day"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $stats['today'] ?? 0; ?></h3>
                        <p>Сегодня</p>
                    </div>
                </div>
            </div>
            
            <!-- Фильтры и действия -->
            <div class="filters-actions" style="margin: 20px 0; padding: 15px; background: #f8f9fa; border-radius: 8px;">
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
                    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                        <!-- Фильтр по статусу -->
                        <div class="filter-group">
                            <label style="font-weight: 500; margin-right: 8px;">Статус:</label>
                            <select id="statusFilter" onchange="updateFilters()" class="form-select" style="padding: 6px 12px; border-radius: 4px; border: 1px solid #e2e8f0;">
                                <option value="all" <?php echo $filter === 'all' ? 'selected' : ''; ?>>Все</option>
                                <option value="unread" <?php echo $filter === 'unread' ? 'selected' : ''; ?>>Непрочитанные</option>
                                <option value="read" <?php echo $filter === 'read' ? 'selected' : ''; ?>>Прочитанные</option>
                            </select>
                        </div>
                        
                        <!-- Фильтр по типу -->
                        <div class="filter-group">
                            <label style="font-weight: 500; margin-right: 8px;">Тип:</label>
                            <select id="typeFilter" onchange="updateFilters()" class="form-select" style="padding: 6px 12px; border-radius: 4px; border: 1px solid #e2e8f0;">
                                <option value="all" <?php echo $type_filter === 'all' ? 'selected' : ''; ?>>Все типы</option>
                                <?php foreach ($notification_types as $type): ?>
                                    <option value="<?php echo htmlspecialchars($type['name']); ?>" 
                                            <?php echo $type_filter === $type['name'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($type['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div style="display: flex; gap: 10px;">
                        <form method="POST" action="" style="display: inline;">
                            <button type="submit" name="mark_all_read" class="btn btn-primary btn-sm" 
                                    onclick="return confirm('Пометить все уведомления как прочитанные?')">
                                <i class="fas fa-check-double"></i> Прочитать все
                            </button>
                        </form>
                        
                        <form method="POST" action="" style="display: inline;">
                            <button type="submit" name="delete_all_read" class="btn btn-danger btn-sm" 
                                    onclick="return confirm('Удалить все прочитанные уведомления? Это действие необратимо.')">
                                <i class="fas fa-trash"></i> Удалить прочитанные
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Список уведомлений -->
            <?php if (empty($notifications)): ?>
                <div class="empty-state" style="text-align: center; padding: 40px 20px;">
                    <i class="fas fa-bell-slash fa-3x" style="color: #cbd5e0; margin-bottom: 15px;"></i>
                    <p style="color: #4a5568; margin-bottom: 10px;">Уведомлений не найдено</p>
                    <p style="color: #718096; max-width: 400px; margin: 0 auto;">Попробуйте изменить фильтры или создайте новые действия в системе.</p>
                </div>
            <?php else: ?>
                <div class="notifications-list">
                    <?php foreach ($notifications as $notification): ?>
                        <div class="notification-item <?php echo $notification['is_read'] ? 'read' : 'unread'; ?>">
                            <div class="notification-icon">
                                <?php
                                $icon = 'fa-bell';
                                $icon_color = '#4a5568';
                                switch ($notification['type_name']) {
                                    case 'Задание': 
                                        $icon = 'fa-tasks'; 
                                        $icon_color = '#3182ce';
                                        break;
                                    case 'Материал': 
                                        $icon = 'fa-book'; 
                                        $icon_color = '#38a169';
                                        break;
                                    case 'Оценка': 
                                        $icon = 'fa-star'; 
                                        $icon_color = '#d69e2e';
                                        break;
                                    case 'Дедлайн': 
                                        $icon = 'fa-clock'; 
                                        $icon_color = '#e53e3e';
                                        break;
                                    case 'Объявление': 
                                        $icon = 'fa-bullhorn'; 
                                        $icon_color = '#805ad5';
                                        break;
                                }
                                ?>
                                <i class="fas <?php echo $icon; ?>" style="color: <?php echo $icon_color; ?>;"></i>
                            </div>
                            
                            <div class="notification-content" style="flex: 1;">
                                <div class="notification-header" style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 8px;">
                                    <div>
                                        <h4 style="margin: 0; color: #2d3748; font-weight: 600;">
                                            <?php echo htmlspecialchars($notification['title']); ?>
                                            <?php if (!$notification['is_read']): ?>
                                                <span class="badge" style="background: #e53e3e; color: white; padding: 2px 6px; border-radius: 10px; font-size: 0.7rem; margin-left: 8px;">Новое</span>
                                            <?php endif; ?>
                                        </h4>
                                        <div style="display: flex; gap: 10px; margin-top: 5px;">
                                            <span class="notification-type" style="background: <?php echo $icon_color; ?>20; color: <?php echo $icon_color; ?>; padding: 2px 8px; border-radius: 12px; font-size: 0.8rem;">
                                                <?php echo htmlspecialchars($notification['type_name']); ?>
                                            </span>
                                            <span class="notification-time" style="color: #718096; font-size: 0.85rem;">
                                                <i class="far fa-clock"></i> <?php echo date('d.m.Y H:i', strtotime($notification['created_at'])); ?>
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <div class="notification-actions" style="display: flex; gap: 5px;">
                                        <?php if (!$notification['is_read']): ?>
                                            <form method="POST" action="" style="display: inline;">
                                                <input type="hidden" name="notification_id" value="<?php echo $notification['notification_id']; ?>">
                                                <button type="submit" name="mark_read" class="btn btn-success btn-sm" style="padding: 4px 8px;">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                        
                                        <form method="POST" action="" style="display: inline;">
                                            <input type="hidden" name="notification_id" value="<?php echo $notification['notification_id']; ?>">
                                            <button type="submit" name="delete_notification" class="btn btn-danger btn-sm" style="padding: 4px 8px;"
                                                    onclick="return confirm('Удалить это уведомление?')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                                
                                <p class="notification-message" style="color: #4a5568; line-height: 1.5; margin-bottom: 10px;">
                                    <?php echo nl2br(htmlspecialchars($notification['message'])); ?>
                                </p>
                                
                                <?php if (!$notification['is_read']): ?>
                                    <div style="margin-top: 10px;">
                                        <form method="POST" action="" style="display: inline;">
                                            <input type="hidden" name="notification_id" value="<?php echo $notification['notification_id']; ?>">
                                            <button type="submit" name="mark_read" class="btn btn-primary btn-sm" style="padding: 4px 12px;">
                                                <i class="fas fa-check"></i> Пометить как прочитанное
                                            </button>
                                        </form>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Навинация -->
                <?php if ($total_pages > 1): ?>
                    <div class="pagination" style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #e2e8f0;">
                        <div style="display: flex; justify-content: center; align-items: center; gap: 10px;">
                            <?php if ($page > 1): ?>
                                <a href="?page=<?php echo $page-1; ?>&filter=<?php echo $filter; ?>&type=<?php echo $type_filter; ?>" class="btn btn-sm">
                                    <i class="fas fa-chevron-left"></i> Назад
                                </a>
                            <?php endif; ?>
                            
                            <span style="color: #718096;">
                                Страница <?php echo $page; ?> из <?php echo $total_pages; ?>
                            </span>
                            
                            <?php if ($page < $total_pages): ?>
                                <a href="?page=<?php echo $page+1; ?>&filter=<?php echo $filter; ?>&type=<?php echo $type_filter; ?>" class="btn btn-sm">
                                    Вперед <i class="fas fa-chevron-right"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        
        <div class="dashboard-sidebar">
            <!-- Быстрые действия -->
            <div class="dashboard-card">
                <h3><i class="fas fa-bolt"></i> Быстрые действия</h3>
                <div class="quick-actions">
                    <form method="POST" action="" style="width: 100%;">
                        <button type="submit" name="mark_all_read" class="quick-action" onclick="return confirm('Пометить все уведомления как прочитанные?')">
                            <i class="fas fa-check-double"></i>
                            <span>Прочитать все</span>
                        </button>
                    </form>
                    
                    <form method="POST" action="" style="width: 100%;">
                        <button type="submit" name="delete_all_read" class="quick-action" onclick="return confirm('Удалить все прочитанные уведомления?')">
                            <i class="fas fa-trash-alt"></i>
                            <span>Удалить прочитанные</span>
                        </button>
                    </form>
                    
                    <a href="?filter=unread" class="quick-action">
                        <i class="fas fa-exclamation-circle"></i>
                        <span>Только непрочитанные</span>
                    </a>
                    
                    <a href="?filter=all&type=all" class="quick-action">
                        <i class="fas fa-list"></i>
                        <span>Показать все</span>
                    </a>
                </div>
            </div>
            
            <!-- Статистика по типам -->
            <div class="dashboard-card">
                <h3><i class="fas fa-chart-pie"></i> По типам</h3>
                <div class="type-stats">
                    <?php
                    $sql = "SELECT nt.name, COUNT(*) as count 
                            FROM notifications n
                            JOIN notification_type nt ON n.notification_type_id = nt.notification_type_id
                            WHERE n.user_id = ?
                            GROUP BY nt.name
                            ORDER BY count DESC";
                    $stmt = $conn->prepare($sql);
                    $stmt->execute([$user_id]);
                    $type_stats = $stmt->fetchAll();
                    
                    if (empty($type_stats)):
                    ?>
                        <p style="color: #718096; font-style: italic;">Нет данных</p>
                    <?php else: ?>
                        <?php foreach ($type_stats as $type_stat): ?>
                            <div class="type-stat-item" style="display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #e2e8f0;">
                                <span><?php echo htmlspecialchars($type_stat['name']); ?></span>
                                <span style="font-weight: 600;"><?php echo $type_stat['count']; ?></span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Информация -->
            <div class="dashboard-card">
                <h3><i class="fas fa-info-circle"></i> Информация</h3>
                <div style="font-size: 0.9rem; color: #666; line-height: 1.5;">
                    <p>Уведомления помогают вам оставаться в курсе важных событий в системе.</p>
                    <p style="margin-top: 10px;">
                        <i class="fas fa-circle" style="color: #e53e3e; font-size: 0.7rem;"></i> 
                        <span style="margin-left: 5px;">Непрочитанные уведомления</span>
                    </p>
                    <p>
                        <i class="fas fa-circle" style="color: #38a169; font-size: 0.7rem;"></i> 
                        <span style="margin-left: 5px;">Прочитанные уведомления</span>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function updateFilters() {
    const statusFilter = document.getElementById('statusFilter').value;
    const typeFilter = document.getElementById('typeFilter').value;
    window.location.href = `?filter=${statusFilter}&type=${typeFilter}`;
}

// Автоматическое обновление уведомлений каждые 30 секунд
setTimeout(function() {
    const unreadCount = <?php echo $stats['unread'] ?? 0; ?>;
    if (unreadCount > 0) {
        // Проверяем, есть ли новые уведомления
        fetch('../api/check_notifications.php?user_id=<?php echo $user_id; ?>')
            .then(response => response.json())
            .then(data => {
                if (data.new_notifications > unreadCount) {
                    if (confirm('Появились новые уведомления! Обновить страницу?')) {
                        location.reload();
                    }
                }
            });
    }
}, 30000); // 30 секунд

// Пометить уведомление как прочитанное при клике
document.addEventListener('DOMContentLoaded', function() {
    const notificationItems = document.querySelectorAll('.notification-item.unread');
    notificationItems.forEach(item => {
        item.addEventListener('click', function(e) {
            if (!e.target.closest('.notification-actions') && !e.target.closest('button') && !e.target.closest('form')) {
                const notificationId = this.querySelector('[name="notification_id"]')?.value;
                if (notificationId) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.style.display = 'none';
                    
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'notification_id';
                    input.value = notificationId;
                    
                    const button = document.createElement('button');
                    button.type = 'submit';
                    button.name = 'mark_read';
                    
                    form.appendChild(input);
                    form.appendChild(button);
                    document.body.appendChild(form);
                    form.submit();
                }
            }
        });
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>