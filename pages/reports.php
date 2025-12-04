<?php
require_once '../includes/header.php';
requireTeacher();

$user_id = getCurrentUserId();
$conn = getDBConnection();

// Получаем курсы преподавателя
$sql = "SELECT c.*, cs.name as status_name,
        (SELECT COUNT(*) FROM course_enrollments WHERE course_id = c.course_id) as student_count
        FROM courses c
        JOIN course_status cs ON c.course_status_id = cs.course_status_id
        WHERE c.teacher_id = ? AND c.course_status_id != 4
        ORDER BY c.created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->execute([$user_id]);
$courses = $stmt->fetchAll();

// Если выбран курс, получаем детальную статистику
$selected_course_id = $_GET['course_id'] ?? 0;
$course_stats = null;
$student_stats = [];

if ($selected_course_id) {
    // Проверяем доступ к курсу
    $sql = "SELECT * FROM courses WHERE course_id = ? AND teacher_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$selected_course_id, $user_id]);
    $selected_course = $stmt->fetch();
    
    if ($selected_course) {
        // Общая статистика по курсу
        $sql = "SELECT 
                COUNT(DISTINCT a.assignment_id) as total_assignments,
                COUNT(DISTINCT lm.material_id) as total_materials,
                COUNT(DISTINCT ce.student_id) as total_students,
                AVG(g.score) as avg_score,
                MAX(g.score) as max_score,
                MIN(g.score) as min_score
                FROM courses c
                LEFT JOIN assignments a ON c.course_id = a.course_id
                LEFT JOIN learning_materials lm ON c.course_id = lm.course_id
                LEFT JOIN course_enrollments ce ON c.course_id = ce.course_id
                LEFT JOIN student_submissions ss ON a.assignment_id = ss.assignment_id
                LEFT JOIN grades g ON ss.submission_id = g.submission_id
                WHERE c.course_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$selected_course_id]);
        $course_stats = $stmt->fetch();
        
        // Статистика по студентам
        $sql = "SELECT u.user_id, u.first_name, u.last_name, 
                COUNT(DISTINCT a.assignment_id) as total_assignments,
                COUNT(DISTINCT ss.submission_id) as submitted_count,
                AVG(g.score) as avg_score,
                MAX(g.score) as max_score,
                MIN(g.score) as min_score
                FROM users u
                JOIN course_enrollments ce ON u.user_id = ce.student_id
                LEFT JOIN assignments a ON ce.course_id = a.course_id
                LEFT JOIN student_submissions ss ON a.assignment_id = ss.assignment_id AND ss.student_id = u.user_id
                LEFT JOIN grades g ON ss.submission_id = g.submission_id
                WHERE ce.course_id = ?
                GROUP BY u.user_id
                ORDER BY u.last_name, u.first_name";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$selected_course_id]);
        $student_stats = $stmt->fetchAll();
    }
}
?>

<div class="dashboard">
    <div class="dashboard-grid">
        <div class="dashboard-card main-card">
            <div class="reports-container">
                <div class="reports-sidebar">
                    <h3><i class="fas fa-book"></i> Мои курсы</h3>
                    <?php if (empty($courses)): ?>
                        <p class="empty-message">У вас нет курсов для отчетов</p>
                    <?php else: ?>
                        <div class="courses-list">
                            <?php foreach ($courses as $course): ?>
                                <a href="?course_id=<?php echo $course['course_id']; ?>" 
                                   class="course-item <?php echo $selected_course_id == $course['course_id'] ? 'active' : ''; ?>">
                                    <div class="course-title"><?php echo htmlspecialchars($course['title']); ?></div>
                                    <div class="course-meta">
                                        <span class="student-count">
                                            <i class="fas fa-users"></i> <?php echo $course['student_count']; ?>
                                        </span>
                                        <span class="course-status"><?php echo $course['status_name']; ?></span>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="reports-content">
                    <?php if ($selected_course_id && $selected_course): ?>
                        <div class="report-header">
                            <h3>Отчет по курсу: <?php echo htmlspecialchars($selected_course['title']); ?></h3>
                            <div class="report-actions">
                                <button onclick="printReport()" class="btn btn-sm">
                                    <i class="fas fa-print"></i> Печать
                                </button>
                                <button onclick="exportReport()" class="btn btn-sm">
                                    <i class="fas fa-download"></i> Экспорт
                                </button>
                            </div>
                        </div>
                        
                        <div class="stats-grid">
                            <div class="stat-card">
                                <div class="stat-icon">
                                    <i class="fas fa-users"></i>
                                </div>
                                <div class="stat-info">
                                    <h3><?php echo $course_stats['total_students'] ?? 0; ?></h3>
                                    <p>Студентов</p>
                                </div>
                            </div>
                            
                            <div class="stat-card">
                                <div class="stat-icon">
                                    <i class="fas fa-tasks"></i>
                                </div>
                                <div class="stat-info">
                                    <h3><?php echo $course_stats['total_assignments'] ?? 0; ?></h3>
                                    <p>Заданий</p>
                                </div>
                            </div>
                            
                            <div class="stat-card">
                                <div class="stat-icon">
                                    <i class="fas fa-book"></i>
                                </div>
                                <div class="stat-info">
                                    <h3><?php echo $course_stats['total_materials'] ?? 0; ?></h3>
                                    <p>Материалов</p>
                                </div>
                            </div>
                            
                            <div class="stat-card">
                                <div class="stat-icon">
                                    <i class="fas fa-chart-line"></i>
                                </div>
                                <div class="stat-info">
                                    <h3><?php echo round($course_stats['avg_score'] ?? 0, 1); ?></h3>
                                    <p>Средний балл</p>
                                </div>
                            </div>
                        </div>
                        
                        <?php if (!empty($student_stats)): ?>
                            <div class="dashboard-card" style="margin-top: 20px;">
                                <h3><i class="fas fa-user-graduate"></i> Успеваемость студентов</h3>
                                <div class="table-container">
                                    <table>
                                        <thead>
                                            <tr>
                                                <th>Студент</th>
                                                <th>Всего заданий</th>
                                                <th>Сдано работ</th>
                                                <th>Средний балл</th>
                                                <th>Макс. балл</th>
                                                <th>Мин. балл</th>
                                                <th>Прогресс</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($student_stats as $student): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($student['last_name'] . ' ' . $student['first_name']); ?></td>
                                                    <td><?php echo $student['total_assignments']; ?></td>
                                                    <td><?php echo $student['submitted_count']; ?></td>
                                                    <td><?php echo round($student['avg_score'] ?? 0, 1); ?></td>
                                                    <td><?php echo $student['max_score'] ?? '-'; ?></td>
                                                    <td><?php echo $student['min_score'] ?? '-'; ?></td>
                                                    <td>
                                                        <?php if ($student['total_assignments'] > 0): ?>
                                                            <?php $progress = ($student['submitted_count'] / $student['total_assignments']) * 100; ?>
                                                            <div class="progress-bar">
                                                                <div class="progress-fill" style="width: <?php echo $progress; ?>%"></div>
                                                                <span class="progress-text"><?php echo round($progress); ?>%</span>
                                                            </div>
                                                        <?php else: ?>
                                                            -
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-chart-pie fa-3x"></i>
                            <p>Выберите курс из списка слева, чтобы увидеть детальную статистику и успеваемость студентов.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="dashboard-sidebar">
            <div class="dashboard-card">
                <h3><i class="fas fa-info-circle"></i> О отчетах</h3>
                <p style="font-size: 0.9rem; color: #666; line-height: 1.5;">
                    Здесь вы можете просматривать статистику по вашим курсам: количество студентов, заданий, материалов и средние баллы.
                </p>
            </div>
            
            <div class="dashboard-card">
                <h3><i class="fas fa-lightbulb"></i> Советы</h3>
                <ul class="tips-list">
                    <li>Выберите курс для просмотра детальной статистики</li>
                    <li>Нажмите "Печать" для печати отчета</li>
                    <li>Следите за прогрессом студентов</li>
                    <li>Используйте фильтры для анализа данных</li>
                </ul>
            </div>
            
            <div class="dashboard-card">
                <h3><i class="fas fa-calculator"></i> Быстрые расчеты</h3>
                <div class="quick-calc">
                    <div class="calc-item">
                        <span>Всего курсов:</span>
                        <strong><?php echo count($courses); ?></strong>
                    </div>
                    <div class="calc-item">
                        <span>Всего студентов:</span>
                        <strong>
                            <?php 
                            $total_students = 0;
                            foreach ($courses as $course) {
                                $total_students += $course['student_count'];
                            }
                            echo $total_students;
                            ?>
                        </strong>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function printReport() {
    window.print();
}

function exportReport() {
    alert('Функция экспорта отчета будет реализована в следующей версии');
}
</script>

<?php require_once '../includes/footer.php'; ?>