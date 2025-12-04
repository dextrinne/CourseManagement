<?php
require_once '../../includes/header.php';
requireTeacher();

$assignment_id = $_GET['id'] ?? 0;
$user_id = getCurrentUserId();

$conn = getDBConnection();

// Получаем информацию о задании с проверкой доступа
$sql = "SELECT a.*, c.title as course_title, c.teacher_id 
        FROM assignments a
        JOIN courses c ON a.course_id = c.course_id
        WHERE a.assignment_id = ? AND c.teacher_id = ?";
$stmt = $conn->prepare($sql);
$stmt->execute([$assignment_id, $user_id]);
$assignment = $stmt->fetch();

if (!$assignment) {
    $_SESSION['error'] = 'Задание не найдено или нет доступа';
    header('Location: ../courses/');
    exit();
}

// Получаем все работы по этому заданию
$sql = "SELECT ss.*, u.first_name, u.last_name, u.email, g.score, g.feedback, g.graded_at
        FROM student_submissions ss
        JOIN users u ON ss.student_id = u.user_id
        LEFT JOIN grades g ON ss.submission_id = g.submission_id
        WHERE ss.assignment_id = ?
        ORDER BY ss.submitted_at DESC";
$stmt = $conn->prepare($sql);
$stmt->execute([$assignment_id]);
$submissions = $stmt->fetchAll();

// Обработка оценки работы
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['grade_submission'])) {
    $submission_id = $_POST['submission_id'] ?? 0;
    $score = $_POST['score'] ?? 0;
    $feedback = $_POST['feedback'] ?? '';
    
    // Проверяем, что оценка не превышает максимальный балл
    if ($score > $assignment['max_score']) {
        $_SESSION['error'] = "Оценка не может превышать максимальный балл ({$assignment['max_score']})";
        header("Location: grade.php?id=$assignment_id");
        exit();
    }
    
    try {
        // Проверяем, есть ли уже оценка
        $sql = "SELECT * FROM grades WHERE submission_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$submission_id]);
        $existing_grade = $stmt->fetch();
        
        if ($existing_grade) {
            // Обновляем существующую оценку
            $sql = "UPDATE grades SET score = ?, feedback = ?, graded_at = NOW() WHERE submission_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$score, $feedback, $submission_id]);
        } else {
            // Добавляем новую оценку
            $sql = "INSERT INTO grades (submission_id, score, feedback, graded_by) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$submission_id, $score, $feedback, $user_id]);
            
            // Обновляем статус работы
            $sql = "UPDATE student_submissions SET submission_status_id = 2 WHERE submission_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$submission_id]);
        }
        
        // Добавляем уведомление студенту
        $sql = "SELECT student_id FROM student_submissions WHERE submission_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$submission_id]);
        $submission = $stmt->fetch();
        
        if ($submission) {
            addNotification($submission['student_id'], 
                          'Работа проверена', 
                          "Ваша работа по заданию '{$assignment['title']}' проверена. Оценка: $score", 
                          3);
        }
        
        $_SESSION['success'] = 'Оценка успешно сохранена';
        header("Location: grade.php?id=$assignment_id");
        exit();
    } catch (PDOException $e) {
        $_SESSION['error'] = 'Ошибка при сохранении оценки: ' . $e->getMessage();
    }
}
?>

<div class="page-header">
    <h2><i class="fas fa-check-circle"></i> Проверка работ</h2>
    <p class="subtitle">Задание: <?php echo htmlspecialchars($assignment['title']); ?></p>
    <p class="subtitle">Курс: <?php echo htmlspecialchars($assignment['course_title']); ?></p>
    <a href="../courses/view.php?id=<?php echo $assignment['course_id']; ?>" class="btn btn-secondary">← Назад к курсу</a>
</div>

<div class="assignment-info-box">
    <div class="info-grid">
        <div class="info-item">
            <span class="info-label">Срок сдачи:</span>
            <span class="info-value"><?php echo date('d.m.Y H:i', strtotime($assignment['deadline'])); ?></span>
        </div>
        <div class="info-item">
            <span class="info-label">Максимальный балл:</span>
            <span class="info-value"><?php echo $assignment['max_score']; ?></span>
        </div>
        <div class="info-item">
            <span class="info-label">Всего работ:</span>
            <span class="info-value"><?php echo count($submissions); ?></span>
        </div>
        <div class="info-item">
            <span class="info-label">Проверено:</span>
            <span class="info-value">
                <?php
                $graded_count = 0;
                foreach ($submissions as $s) {
                    if ($s['score'] !== null) $graded_count++;
                }
                echo $graded_count . ' из ' . count($submissions);
                ?>
            </span>
        </div>
    </div>
</div>

<?php if (empty($submissions)): ?>
    <div class="empty-state">
        <i class="fas fa-inbox fa-3x"></i>
        <h3>Работы пока не отправлены</h3>
        <p>Студенты еще не сдали работы по этому заданию.</p>
    </div>
<?php else: ?>
    <div class="submissions-list">
        <?php foreach ($submissions as $submission): ?>
            <div class="submission-item <?php echo $submission['score'] !== null ? 'graded' : 'ungraded'; ?>">
                <div class="submission-header">
                    <div class="student-info">
                        <h4><?php echo htmlspecialchars($submission['first_name'] . ' ' . $submission['last_name']); ?></h4>
                        <p class="student-email"><?php echo htmlspecialchars($submission['email']); ?></p>
                    </div>
                    <div class="submission-meta">
                        <span class="submission-date">
                            <i class="fas fa-clock"></i>
                            <?php echo date('d.m.Y H:i', strtotime($submission['submitted_at'])); ?>
                        </span>
                        <?php if ($submission['score'] !== null): ?>
                            <span class="score-badge">
                                <i class="fas fa-star"></i>
                                <?php echo $submission['score']; ?>/<?php echo $assignment['max_score']; ?>
                            </span>
                        <?php else: ?>
                            <span class="status-badge status-отправлено">Ожидает проверки</span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="submission-content">
                    <div class="file-info">
                        <i class="fas fa-file"></i>
                        <a href="../../assets/uploads/<?php echo $submission['file_path']; ?>" 
                           target="_blank" class="file-link">
                            Скачать работу
                        </a>
                    </div>
                    
                    <?php if ($submission['comment']): ?>
                        <div class="student-comment">
                            <strong>Комментарий студента:</strong>
                            <p><?php echo nl2br(htmlspecialchars($submission['comment'])); ?></p>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="" class="grading-form">
                        <input type="hidden" name="submission_id" value="<?php echo $submission['submission_id']; ?>">
                        <input type="hidden" name="grade_submission" value="1">
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="score_<?php echo $submission['submission_id']; ?>">Оценка</label>
                                <div class="score-input">
                                    <input type="number" 
                                           id="score_<?php echo $submission['submission_id']; ?>" 
                                           name="score" 
                                           min="0" 
                                           max="<?php echo $assignment['max_score']; ?>" 
                                           step="0.1"
                                           value="<?php echo $submission['score'] ?? ''; ?>"
                                           required>
                                    <span class="max-score">/ <?php echo $assignment['max_score']; ?></span>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="feedback_<?php echo $submission['submission_id']; ?>">Обратная связь</label>
                                <textarea id="feedback_<?php echo $submission['submission_id']; ?>" 
                                          name="feedback" 
                                          rows="3"
                                          placeholder="Добавьте комментарий к работе..."><?php echo htmlspecialchars($submission['feedback'] ?? ''); ?></textarea>
                            </div>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn btn-sm">
                                <i class="fas fa-save"></i> Сохранить оценку
                            </button>
                            <?php if ($submission['graded_at']): ?>
                                <span class="graded-date">
                                    Проверено: <?php echo date('d.m.Y H:i', strtotime($submission['graded_at'])); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    
    <div class="bulk-actions">
        <h3>Массовые действия</h3>
        <div class="bulk-buttons">
            <button type="button" class="btn btn-secondary" onclick="downloadAllSubmissions()">
                <i class="fas fa-download"></i> Скачать все работы
            </button>
            <button type="button" class="btn btn-secondary" onclick="exportGrades()">
                <i class="fas fa-file-export"></i> Экспорт оценок
            </button>
        </div>
    </div>
<?php endif; ?>

<script>
function downloadAllSubmissions() {
    // В реальной системе здесь была бы реализация скачивания архива со всеми работами
    alert('Функция скачивания всех работ будет реализована в следующей версии');
}

function exportGrades() {
    window.location.href = 'export_grades.php?assignment_id=<?php echo $assignment_id; ?>';
}
</script>

<?php require_once '../../includes/footer.php'; ?>