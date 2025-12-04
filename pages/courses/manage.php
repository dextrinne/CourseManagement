<?php
require_once '../../includes/header.php';
requireTeacher();

$course_id = $_GET['id'] ?? 0;
$user_id = getCurrentUserId();

$course = getCourseById($course_id, $user_id, 1);

if (!$course) {
    $_SESSION['error'] = 'Курс не найден или у вас нет доступа';
    header('Location: index.php');
    exit();
}

// Обработка обновления курса
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn = getDBConnection();
    
    $title = $_POST['title'] ?? $course['title'];
    $description = $_POST['description'] ?? $course['description'];
    $status_id = $_POST['status_id'] ?? $course['course_status_id'];
    
    try {
        $sql = "UPDATE courses SET title = ?, description = ?, course_status_id = ? WHERE course_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$title, $description, $status_id, $course_id]);
        
        $_SESSION['success'] = 'Курс обновлен';
        header("Location: manage.php?id=$course_id");
        exit();
    } catch (PDOException $e) {
        $_SESSION['error'] = 'Ошибка при обновлении: ' . $e->getMessage();
    }
}

// Получаем статистику курса
$conn = getDBConnection();
$sql = "SELECT COUNT(*) as assignments_count FROM assignments WHERE course_id = ?";
$stmt = $conn->prepare($sql);
$stmt->execute([$course_id]);
$assignments_count = $stmt->fetch()['assignments_count'];

$sql = "SELECT COUNT(*) as materials_count FROM learning_materials WHERE course_id = ?";
$stmt = $conn->prepare($sql);
$stmt->execute([$course_id]);
$materials_count = $stmt->fetch()['materials_count'];

$sql = "SELECT AVG(g.score) as avg_score FROM grades g
        JOIN student_submissions ss ON g.submission_id = ss.submission_id
        JOIN assignments a ON ss.assignment_id = a.assignment_id
        WHERE a.course_id = ?";
$stmt = $conn->prepare($sql);
$stmt->execute([$course_id]);
$avg_score_result = $stmt->fetch();
$avg_score = $avg_score_result['avg_score'] ? round($avg_score_result['avg_score'], 1) : 0;

$sql = "SELECT * FROM course_status";
$stmt = $conn->query($sql);
$statuses = $stmt->fetchAll();
?>

<div class="dashboard">
    <div class="dashboard-grid">
        <div class="dashboard-card main-card">
            <!-- Вкладки управления -->
            <div class="manage-tabs">
                <button class="tab-btn active" onclick="switchManageTab('edit')">
                    <i class="fas fa-edit"></i> Редактирование
                </button>
                <button class="tab-btn" onclick="switchManageTab('stats')">
                    <i class="fas fa-chart-bar"></i> Статистика
                </button>
                <button class="tab-btn" onclick="switchManageTab('danger')">
                    <i class="fas fa-exclamation-triangle"></i> Опасная зона
                </button>
            </div>
            
            <!-- Содержимое вкладки "Редактирование" -->
            <div class="tab-content active" id="edit-tab">
                <div class="form-container">
                    <form method="POST" action="">
                        <div class="form-group">
                            <label for="title"><i class="fas fa-heading"></i> Название курса</label>
                            <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($course['title']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="description"><i class="fas fa-align-left"></i> Описание курса</label>
                            <textarea id="description" name="description" rows="5"><?php echo htmlspecialchars($course['description']); ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="status_id"><i class="fas fa-info-circle"></i> Статус курса</label>
                            <select id="status_id" name="status_id">
                                <?php foreach ($statuses as $status): ?>
                                    <?php $selected = $status['course_status_id'] == $course['course_status_id'] ? 'selected' : ''; ?>
                                    <option value="<?php echo $status['course_status_id']; ?>" <?php echo $selected; ?>>
                                        <?php echo $status['name']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Сохранить изменения
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Содержимое вкладки "Статистика" -->
            <div class="tab-content" id="stats-tab">
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon" style="background: #bee3f8; color: #2c5282;">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $course['student_count']; ?></h3>
                            <p>Студентов</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon" style="background: #fed7d7; color: #c53030;">
                            <i class="fas fa-tasks"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $assignments_count; ?></h3>
                            <p>Заданий</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon" style="background: #c6f6d5; color: #276749;">
                            <i class="fas fa-book"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $materials_count; ?></h3>
                            <p>Материалов</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon" style="background: #e9d8fd; color: #553c9a;">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $avg_score; ?></h3>
                            <p>Средний балл</p>
                        </div>
                    </div>
                </div>
                
                <!-- Дополнительная информация -->
                <div class="additional-info" style="margin-top: 30px;">
                    <h4><i class="fas fa-info-circle"></i> Информация о курсе</h4>
                    <div class="info-grid">
                        <div class="info-item">
                            <span class="info-label">Статус:</span>
                            <span class="status-badge status-<?php echo strtolower($course['status_name']); ?>">
                                <?php echo $course['status_name']; ?>
                            </span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Создан:</span>
                            <span><?php echo date('d.m.Y', strtotime($course['created_at'])); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">ID курса:</span>
                            <span>#<?php echo $course_id; ?></span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Содержимое вкладки "Опасная зона" -->
            <div class="tab-content" id="danger-tab">
                <div class="danger-zone">
                    <div class="warning-box" style="background: #fff5f5; border: 2px solid #fed7d7; border-radius: 8px; padding: 20px; margin-bottom: 30px;">
                        <h3 style="color: #c53030; display: flex; align-items: center; gap: 10px;">
                            <i class="fas fa-exclamation-triangle"></i> Опасная зона
                        </h3>
                        <p style="color: #718096; margin-top: 10px;">
                            Эти действия необратимы. Будьте осторожны при их выполнении.
                        </p>
                    </div>
                    
                    <div class="danger-actions">
                        <div class="danger-action" style="background: #fffaf0; border: 1px solid #feebc8; border-radius: 8px; padding: 20px; margin-bottom: 20px;">
                            <h4 style="color: #dd6b20; display: flex; align-items: center; gap: 10px;">
                                <i class="fas fa-archive"></i> Архивировать курс
                            </h4>
                            <p style="color: #718096; margin: 10px 0;">
                                Переведет курс в статус "Завершен". Студенты больше не смогут сдавать работы.
                            </p>
                            <form method="POST" action="update_status.php" style="display: inline;">
                                <input type="hidden" name="course_id" value="<?php echo $course_id; ?>">
                                <input type="hidden" name="status_id" value="3">
                                <button type="submit" class="btn btn-warning" 
                                        onclick="return confirm('Вы уверены, что хотите перевести курс в статус "Завершен"? Студенты не смогут сдавать новые работы.')">
                                    <i class="fas fa-archive"></i> Архивировать курс
                                </button>
                            </form>
                        </div>
                        
                        <div class="danger-action" style="background: #fff5f5; border: 1px solid #fed7d7; border-radius: 8px; padding: 20px; margin-bottom: 20px;">
                            <h4 style="color: #c53030; display: flex; align-items: center; gap: 10px;">
                                <i class="fas fa-trash"></i> Удалить курс
                            </h4>
                            <p style="color: #718096; margin: 10px 0;">
                                Переведет курс в статус "Удален". Все данные будут скрыты, но останутся в базе.
                            </p>
                            <form method="POST" action="update_status.php" style="display: inline;">
                                <input type="hidden" name="course_id" value="<?php echo $course_id; ?>">
                                <input type="hidden" name="status_id" value="4">
                                <button type="submit" class="btn btn-danger" 
                                        onclick="return confirm('ВНИМАНИЕ! Вы уверены, что хотите удалить курс? Это действие необратимо. Все данные будут скрыты от студентов.')">
                                    <i class="fas fa-trash"></i> Удалить курс
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="dashboard-sidebar">
            <!-- Быстрые действия -->
            <div class="dashboard-card">
                <h3><i class="fas fa-bolt"></i> Быстрые действия</h3>
                <div class="quick-actions">
                    <a href="view.php?id=<?php echo $course_id; ?>" class="quick-action">
                        <i class="fas fa-eye"></i>
                        <span>Просмотр курса</span>
                    </a>
                    <a href="../assignments/create.php?course_id=<?php echo $course_id; ?>" class="quick-action">
                        <i class="fas fa-plus-circle"></i>
                        <span>Добавить задание</span>
                    </a>
                    <a href="../materials/upload.php?course_id=<?php echo $course_id; ?>" class="quick-action">
                        <i class="fas fa-upload"></i>
                        <span>Загрузить материал</span>
                    </a>
                    <a href="?id=<?php echo $course_id; ?>&tab=stats" class="quick-action">
                        <i class="fas fa-chart-bar"></i>
                        <span>Статистика</span>
                    </a>
                </div>
            </div>
            
            <!-- Информация о статусе -->
            <div class="dashboard-card">
                <h3><i class="fas fa-info-circle"></i> Информация о статусах</h3>
                <div class="status-info">
                    <div class="status-item" style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
                        <span class="status-badge status-черновик" style="margin: 0;">Черновик</span>
                        <span style="font-size: 0.85rem; color: #718096;">Курс в разработке</span>
                    </div>
                    <div class="status-item" style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
                        <span class="status-badge status-активен" style="margin: 0;">Активен</span>
                        <span style="font-size: 0.85rem; color: #718096;">Доступен студентам</span>
                    </div>
                    <div class="status-item" style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
                        <span class="status-badge status-завершен" style="margin: 0;">Завершен</span>
                        <span style="font-size: 0.85rem; color: #718096;">Архивирован</span>
                    </div>
                    <div class="status-item" style="display: flex; align-items: center; gap: 10px;">
                        <span class="status-badge status-удален" style="margin: 0;">Удален</span>
                        <span style="font-size: 0.85rem; color: #718096;">Скрыт от всех</span>
                    </div>
                </div>
            </div>
            
            <!-- Важные заметки -->
            <div class="dashboard-card">
                <h3><i class="fas fa-lightbulb"></i> Советы</h3>
                <ul class="tips-list">
                    <li>Перед удалением курса убедитесь, что все работы проверены</li>
                    <li>Архивируйте курс после завершения семестра</li>
                    <li>Регулярно обновляйте учебные материалы</li>
                    <li>Следите за дедлайнами заданий</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
function switchManageTab(tabName) {
    // Скрыть все вкладки
    document.querySelectorAll('.tab-content').forEach(tab => {
        tab.classList.remove('active');
    });
    
    // Убрать активный класс у всех кнопок
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    
    // Показать выбранную вкладку
    document.getElementById(tabName + '-tab').classList.add('active');
    
    // Активировать кнопку
    event.target.classList.add('active');
}

// Автоматическое переключение вкладки из URL
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const tab = urlParams.get('tab');
    if (tab && ['edit', 'stats', 'danger'].includes(tab)) {
        switchManageTab(tab);
        // Нужно активировать кнопку вручную
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        document.querySelector(`[onclick*="${tab}"]`).classList.add('active');
    }
});
</script>

<style>
/* Стили для страницы управления курсом */
.manage-tabs {
    display: flex;
    border-bottom: 2px solid #e2e8f0;
    margin-bottom: 25px;
    gap: 5px;
}

.tab-btn {
    padding: 12px 20px;
    background: none;
    border: none;
    border-bottom: 3px solid transparent;
    color: #718096;
    cursor: pointer;
    font-size: 0.95rem;
    font-weight: 500;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    gap: 8px;
    border-radius: 6px 6px 0 0;
}

.tab-btn:hover {
    background: #f7fafc;
    color: #4a5568;
}

.tab-btn.active {
    color: #4a5568;
    border-bottom-color: #4a5568;
    background: #f7fafc;
}

.tab-content {
    display: none;
    animation: fadeIn 0.3s ease-out;
}

.tab-content.active {
    display: block;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin-bottom: 25px;
}

.stat-card {
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 15px;
    display: flex;
    align-items: center;
    gap: 15px;
    transition: transform 0.3s, box-shadow 0.3s;
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.stat-icon {
    width: 50px;
    height: 50px;
    background: #f7fafc;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    flex-shrink: 0;
}

.stat-info h3 {
    margin: 0;
    font-size: 1.5rem;
    color: #2d3748;
    font-weight: 600;
}

.stat-info p {
    margin: 5px 0 0;
    color: #718096;
    font-size: 0.9rem;
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
    border: 1px solid #e2e8f0;
}

.info-item {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.info-label {
    font-weight: 500;
    color: #4a5568;
    font-size: 0.9rem;
}

.quick-actions {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.quick-action {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 15px;
    background: #f8f9fa;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    text-decoration: none;
    color: #4a5568;
    transition: all 0.3s;
}

.quick-action:hover {
    background: #e2e8f0;
    color: #2d3748;
    transform: translateX(5px);
}

.quick-action i {
    font-size: 1rem;
    color: #4a5568;
}

.tips-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.tips-list li {
    padding: 8px 0;
    border-bottom: 1px solid #e2e8f0;
    font-size: 0.9rem;
    color: #666;
}

.tips-list li:last-child {
    border-bottom: none;
}

.tips-list li:before {
    content: "💡";
    margin-right: 8px;
}

.form-container {
    background: white;
    border-radius: 8px;
    padding: 25px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    border: 1px solid #e2e8f0;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    color: #4a5568;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 8px;
}

.form-group input,
.form-group textarea,
.form-group select {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    font-size: 0.95rem;
    transition: border-color 0.3s;
}

.form-group input:focus,
.form-group textarea:focus,
.form-group select:focus {
    outline: none;
    border-color: #4a5568;
    box-shadow: 0 0 0 3px rgba(74, 85, 104, 0.1);
}

.form-actions {
    margin-top: 25px;
    padding-top: 20px;
    border-top: 1px solid #e2e8f0;
    display: flex;
    justify-content: flex-end;
}

@media (max-width: 768px) {
    .dashboard-grid {
        grid-template-columns: 1fr;
    }
    
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .manage-tabs {
        flex-direction: column;
    }
    
    .tab-btn {
        justify-content: center;
        border-radius: 6px;
        margin-bottom: 5px;
        border-bottom: none;
        border-left: 3px solid transparent;
    }
    
    .tab-btn.active {
        border-left-color: #4a5568;
        border-bottom: none;
    }
    
    .info-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<?php require_once '../../includes/footer.php'; ?>