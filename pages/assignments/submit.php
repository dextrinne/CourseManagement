<?php
require_once '../../includes/header.php';

$assignment_id = $_GET['id'] ?? 0;
$user_id = getCurrentUserId();

$conn = getDBConnection();

// Получаем информацию о задании
$sql = "SELECT a.*, c.title as course_title 
        FROM assignments a
        JOIN courses c ON a.course_id = c.course_id
        WHERE a.assignment_id = ?";
$stmt = $conn->prepare($sql);
$stmt->execute([$assignment_id]);
$assignment = $stmt->fetch();

// Проверяем, записан ли студент на курс
$sql = "SELECT 1 FROM course_enrollments 
        WHERE course_id = ? AND student_id = ?";
$stmt = $conn->prepare($sql);
$stmt->execute([$assignment['course_id'], $user_id]);
$is_enrolled = $stmt->fetch();

if (!$assignment || !$is_enrolled) {
    $_SESSION['error'] = 'Задание не найдено или у вас нет доступа';
    header('Location: ../courses/');
    exit();
}

// Проверяем, не просрочено ли задание
$is_overdue = new DateTime($assignment['deadline']) < new DateTime();

// Проверяем, была ли уже сдана работа
$sql = "SELECT * FROM student_submissions 
        WHERE assignment_id = ? AND student_id = ?";
$stmt = $conn->prepare($sql);
$stmt->execute([$assignment_id, $user_id]);
$existing_submission = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($is_overdue) {
        $_SESSION['error'] = 'Срок сдачи задания истек';
        header("Location: submit.php?id=$assignment_id");
        exit();
    }
    
    if (!isset($_FILES['work_file']) || $_FILES['work_file']['error'] !== UPLOAD_ERR_OK) {
        $_SESSION['error'] = 'Пожалуйста, выберите файл для загрузки';
    } else {
        $upload_result = uploadFile($_FILES['work_file']);
        
        if ($upload_result['success']) {
            try {
                $comment = $_POST['comment'] ?? '';
                
                if ($existing_submission) {
                    // Обновляем существующую работу
                    $sql = "UPDATE student_submissions 
                            SET file_path = ?, comment = ?, submitted_at = NOW(), 
                                submission_status_id = 1
                            WHERE submission_id = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->execute([$upload_result['path'], $comment, $existing_submission['submission_id']]);
                    $message = 'Работа обновлена';
                } else {
                    // Добавляем новую работу
                    $sql = "INSERT INTO student_submissions (assignment_id, student_id, file_path, comment) 
                            VALUES (?, ?, ?, ?)";
                    $stmt = $conn->prepare($sql);
                    $stmt->execute([$assignment_id, $user_id, $upload_result['path'], $comment]);
                    $message = 'Работа успешно отправлена';
                }
                
                // Добавляем уведомление преподавателю
                addNotification($assignment['created_by'], 
                              'Новая работа', 
                              "Студент отправил работу по заданию: {$assignment['title']}", 
                              3);
                
                $_SESSION['success'] = $message;
                header("Location: ../courses/view.php?id=" . $assignment['course_id']);
                exit();
            } catch (PDOException $e) {
                $_SESSION['error'] = 'Ошибка при отправке работы: ' . $e->getMessage();
            }
        } else {
            $_SESSION['error'] = $upload_result['message'];
        }
    }
}
?>

<div class="page-header">
    <h2><i class="fas fa-upload"></i> Сдача работы</h2>
    <p class="subtitle">Задание: <?php echo htmlspecialchars($assignment['title']); ?></p>
    <p class="subtitle">Курс: <?php echo htmlspecialchars($assignment['course_title']); ?></p>
    <a href="../courses/view.php?id=<?php echo $assignment['course_id']; ?>" class="btn btn-secondary">← Назад к курсу</a>
</div>

<div class="assignment-info-box">
    <h3>Информация о задании</h3>
    <div class="info-grid">
        <div class="info-item">
            <span class="info-label">Срок сдачи:</span>
            <span class="info-value <?php echo $is_overdue ? 'overdue' : ''; ?>">
                <?php echo date('d.m.Y H:i', strtotime($assignment['deadline'])); ?>
                <?php if ($is_overdue): ?>
                    <span class="overdue-badge">Просрочено</span>
                <?php endif; ?>
            </span>
        </div>
        <div class="info-item">
            <span class="info-label">Максимальный балл:</span>
            <span class="info-value"><?php echo $assignment['max_score']; ?></span>
        </div>
    </div>
    <div class="info-item">
        <span class="info-label">Описание:</span>
        <p class="info-value"><?php echo nl2br(htmlspecialchars($assignment['description'])); ?></p>
    </div>
</div>

<?php if ($is_overdue): ?>
    <div class="alert alert-warning">
        <i class="fas fa-exclamation-triangle"></i>
        Срок сдачи задания истек. Вы не можете отправить работу.
    </div>
<?php else: ?>
    <div class="form-container">
        <form method="POST" action="" enctype="multipart/form-data">
            <?php if ($existing_submission): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    У вас уже есть отправленная работа. Загрузка нового файла заменит существующий.
                    <?php if ($existing_submission['submission_status_id'] == 2): ?>
                        <br>Ваша работа уже проверена. Загрузка новой работы сбросит статус проверки.
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <div class="form-group">
                <label for="work_file">Файл работы *</label>
                <input type="file" id="work_file" name="work_file" accept=".pdf,.doc,.docx,.zip,.rar,.txt" required>
                <small class="form-text">Поддерживаемые форматы: PDF, DOC, DOCX, ZIP, RAR, TXT. Макс. размер: 10MB</small>
            </div>
            
            <div class="form-group">
                <label for="comment">Комментарий (необязательно)</label>
                <textarea id="comment" name="comment" rows="3" placeholder="Добавьте комментарий к вашей работе..."></textarea>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary" <?php echo $is_overdue ? 'disabled' : ''; ?>>
                    <i class="fas fa-paper-plane"></i> Отправить на проверку
                </button>
                <a href="../courses/view.php?id=<?php echo $assignment['course_id']; ?>" class="btn btn-secondary">Отмена</a>
            </div>
        </form>
    </div>
<?php endif; ?>

<?php require_once '../../includes/footer.php'; ?>