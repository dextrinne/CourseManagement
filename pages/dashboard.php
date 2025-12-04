<?php
require_once ('../includes/header.php');

$user_id = getCurrentUserId();
$is_teacher = isTeacher();
$courses = getUserCourses($user_id, $is_teacher);

// Получить ближайшие дедлайны для студента
$upcoming_deadlines = [];
if (!$is_teacher) {
    $conn = getDBConnection();
    $sql = "SELECT a.*, c.title as course_title 
            FROM assignments a
            JOIN courses c ON a.course_id = c.course_id
            JOIN course_enrollments ce ON c.course_id = ce.course_id
            WHERE ce.student_id = ? AND a.deadline > NOW() 
            AND NOT EXISTS (
                SELECT 1 FROM student_submissions ss 
                WHERE ss.assignment_id = a.assignment_id AND ss.student_id = ?
            )
            ORDER BY a.deadline ASC
            LIMIT 5";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$user_id, $user_id]);
    $upcoming_deadlines = $stmt->fetchAll();
}
?>

<div class="dashboard">
    <div class="welcome-section">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
            <div>
                <h2>Добро пожаловать, <?php echo $_SESSION['first_name'] . ' ' . $_SESSION['last_name']; ?>!</h2>
                <p class="role-badge"><?php echo $_SESSION['role_name']; ?></p>
            </div>
        </div>
    </div>
    
    <div class="dashboard-grid">
        <div class="dashboard-card main-card">
            <div class="section-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h3><i class="fas fa-book"></i> Мои курсы</h3>
                <?php if ($is_teacher): ?>
                    <a href="courses/create.php" class="btn btn-sm btn-primary">
                        <i class="fas fa-plus"></i> Новый курс
                    </a>
                <?php endif; ?>
            </div>
            
            <?php if (empty($courses)): ?>
                <div class="empty-state" style="text-align: center; padding: 40px 20px;">
                    <i class="fas fa-book-open fa-3x" style="color: #cbd5e0; margin-bottom: 15px;"></i>
                    <h3 style="color: #4a5568; margin-bottom: 10px;">
                        <?php echo $is_teacher ? 'У вас пока нет курсов' : 'Вы не записаны на курсы'; ?>
                    </h3>
                    <p style="color: #718096; max-width: 400px; margin: 0 auto 20px;">
                        <?php if ($is_teacher): ?>
                            Создайте первый курс, чтобы начать работу со студентами
                        <?php else: ?>
                            Обратитесь к преподавателю для записи на курсы
                        <?php endif; ?>
                    </p>
                    <?php if ($is_teacher): ?>
                        <a href="courses/create.php" class="btn btn-primary">
                            <i class="fas fa-plus-circle"></i> Создать первый курс
                        </a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="courses-list">
                    <?php foreach ($courses as $course): ?>
                        <div class="course-item">
                            <div class="course-info">
                                <h4>
                                    <a href="courses/view.php?id=<?php echo $course['course_id']; ?>" style="color: inherit; text-decoration: none;">
                                        <?php echo htmlspecialchars($course['title']); ?>
                                    </a>
                                </h4>
                                <p class="course-description"><?php echo htmlspecialchars(substr($course['description'], 0, 100)); ?>...</p>
                                <div class="course-meta">
                                    <span class="status-badge status-<?php echo strtolower($course['status_name']); ?>">
                                        <?php echo $course['status_name']; ?>
                                    </span>
                                    <?php if ($is_teacher): ?>
                                        <span class="student-count">
                                            <i class="fas fa-users"></i> <?php echo $course['student_count']; ?> студентов
                                        </span>
                                    <?php endif; ?>
                                    <span class="course-date">
                                        <i class="far fa-calendar"></i> <?php echo date('d.m.Y', strtotime($course['created_at'])); ?>
                                    </span>
                                </div>
                            </div>
                            <div class="course-actions">
                                <a href="courses/view.php?id=<?php echo $course['course_id']; ?>" class="btn btn-sm">
                                    <i class="fas fa-eye"></i> Просмотр
                                </a>
                                <?php if ($is_teacher && $course['course_status_id'] != 4): ?>
                                    <a href="courses/manage.php?id=<?php echo $course['course_id']; ?>" class="btn btn-sm btn-secondary">
                                        <i class="fas fa-cog"></i> Управление
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="dashboard-sidebar">
            <!-- Блок для студентов: ближайшие дедлайны -->
            <?php if (!$is_teacher && !empty($upcoming_deadlines)): ?>
                <div class="dashboard-card">
                    <div class="section-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                        <h3><i class="fas fa-clock"></i> Ближайшие дедлайны</h3>
                        <span class="badge" style="background: #e53e3e; color: white; padding: 2px 8px; border-radius: 12px; font-size: 0.8rem;">
                            <?php echo count($upcoming_deadlines); ?>
                        </span>
                    </div>
                    <div class="deadlines-list">
                        <?php foreach ($upcoming_deadlines as $deadline): ?>
                            <div class="deadline-item">
                                <div class="deadline-header">
                                    <div class="deadline-title"><?php echo htmlspecialchars($deadline['title']); ?></div>
                                    <div class="deadline-course"><?php echo htmlspecialchars($deadline['course_title']); ?></div>
                                </div>
                                <div class="deadline-time">
                                    <i class="fas fa-calendar-alt"></i>
                                    <?php echo date('d.m.Y H:i', strtotime($deadline['deadline'])); ?>
                                </div>
                                <a href="assignments/submit.php?id=<?php echo $deadline['assignment_id']; ?>" class="btn btn-sm" style="width: 100%; margin-top: 10px;">
                                    <i class="fas fa-upload"></i> Сдать работу
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Блок для преподавателей: быстрые действия -->
            <?php if ($is_teacher): ?>
                <div class="dashboard-card">
                    <h3><i class="fas fa-bolt"></i> Быстрые действия</h3>
                    <div class="quick-actions">
                        <a href="courses/create.php" class="quick-action">
                            <i class="fas fa-plus-circle"></i>
                            <span>Создать курс</span>
                        </a>
                        <a href="courses/" class="quick-action">
                            <i class="fas fa-list"></i>
                            <span>Все курсы</span>
                        </a>
                        <a href="reports.php" class="quick-action">
                            <i class="fas fa-chart-bar"></i>
                            <span>Отчеты</span>
                        </a>
                        <a href="notifications.php" class="quick-action">
                            <i class="fas fa-bell"></i>
                            <span>Уведомления</span>
                        </a>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Уведомления (для всех) -->
            <div class="dashboard-card">
                <div class="section-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                    <h3><i class="fas fa-bell"></i> Уведомления</h3>
                    <?php if (!empty($unread_notifications)): ?>
                        <span class="badge" style="background: #e53e3e; color: white; padding: 2px 8px; border-radius: 12px; font-size: 0.8rem;">
                            <?php echo count($unread_notifications); ?> новых
                        </span>
                    <?php endif; ?>
                </div>
                
                <?php if (empty($unread_notifications)): ?>
                    <div class="empty-message" style="text-align: center; padding: 10px; color: #718096; font-style: italic;">
                        <i class="far fa-bell-slash" style="font-size: 1.5rem; margin-bottom: 10px; display: block;"></i>
                        Нет новых уведомлений
                    </div>
                <?php else: ?>
                    <div class="notifications-list">
                        <?php foreach ($unread_notifications as $notification): ?>
                            <div class="notification-item">
                                <div class="notification-header">
                                    <div class="notification-title"><?php echo htmlspecialchars($notification['title']); ?></div>
                                    <span class="notification-type"><?php echo $notification['type_name']; ?></span>
                                </div>
                                <div class="notification-message"><?php echo htmlspecialchars(substr($notification['message'], 0, 60)); ?>...</div>
                                <div class="notification-time">
                                    <i class="far fa-clock"></i>
                                    <?php echo date('H:i', strtotime($notification['created_at'])); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <a href="notifications.php" class="btn btn-sm" style="width: 100%; margin-top: 15px;">
                    <i class="fas fa-list"></i> Все уведомления
                </a>
            </div>
        </div>
    </div>
</div>

<?php require_once ('../includes/footer.php'); ?>