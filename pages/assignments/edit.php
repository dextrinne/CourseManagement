<?php
require_once '../../includes/header.php';
requireTeacher();

$assignment_id = $_GET['id'] ?? 0;
$user_id = getCurrentUserId();

$conn = getDBConnection();

// Получаем задание с проверкой доступа
$sql = "SELECT a.*, c.teacher_id, c.title as course_title 
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $deadline = $_POST['deadline'] ?? '';
    $max_score = $_POST['max_score'] ?? 100;
    
    try {
        $sql = "UPDATE assignments 
                SET title = ?, description = ?, deadline = ?, max_score = ? 
                WHERE assignment_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$title, $description, $deadline, $max_score, $assignment_id]);
        
        // Добавляем уведомления для студентов
        $sql = "SELECT student_id FROM course_enrollments WHERE course_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$assignment['course_id']]);
        $students = $stmt->fetchAll();
        
        foreach ($students as $student) {
            addNotification($student['student_id'], 
                          'Задание обновлено', 
                          "Обновлено задание: $title. Новый срок сдачи: $deadline", 
                          1);
        }
        
        $_SESSION['success'] = 'Задание успешно обновлено';
        header("Location: ../courses/view.php?id=" . $assignment['course_id']);
        exit();
    } catch (PDOException $e) {
        $_SESSION['error'] = 'Ошибка при обновлении задания: ' . $e->getMessage();
    }
}
?>

<div class="page-header">
    <h2><i class="fas fa-edit"></i> Редактирование задания</h2>
    <p class="subtitle">Курс: <?php echo htmlspecialchars($assignment['course_title']); ?></p>
    <a href="../courses/view.php?id=<?php echo $assignment['course_id']; ?>" class="btn btn-secondary">← Назад к курсу</a>
</div>

<div class="form-container">
    <form method="POST" action="">
        <div class="form-group">
            <label for="title">Название задания *</label>
            <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($assignment['title']); ?>" required>
        </div>
        
        <div class="form-group">
            <label for="description">Описание задания *</label>
            <textarea id="description" name="description" rows="6" required><?php echo htmlspecialchars($assignment['description']); ?></textarea>
        </div>
        
        <div class="form-grid">
            <div class="form-group">
                <label for="deadline">Срок сдачи *</label>
                <input type="datetime-local" id="deadline" name="deadline" 
                       value="<?php echo date('Y-m-d\TH:i', strtotime($assignment['deadline'])); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="max_score">Максимальный балл</label>
                <input type="number" id="max_score" name="max_score" min="0" max="1000" step="0.1" 
                       value="<?php echo $assignment['max_score']; ?>">
            </div>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Сохранить изменения
            </button>
            <a href="../courses/view.php?id=<?php echo $assignment['course_id']; ?>" class="btn btn-secondary">Отмена</a>
        </div>
    </form>
</div>

<?php require_once '../../includes/footer.php'; ?>