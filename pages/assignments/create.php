<?php
require_once '../../includes/header.php';
requireTeacher();

$course_id = $_GET['course_id'] ?? 0;

// Проверяем доступ к курсу
$user_id = getCurrentUserId();
$conn = getDBConnection();
$sql = "SELECT * FROM courses WHERE course_id = ? AND teacher_id = ?";
$stmt = $conn->prepare($sql);
$stmt->execute([$course_id, $user_id]);
$course = $stmt->fetch();

if (!$course) {
    $_SESSION['error'] = 'Курс не найден или нет доступа';
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $deadline = $_POST['deadline'] ?? '';
    $max_score = $_POST['max_score'] ?? 100;
    
    try {
        $sql = "INSERT INTO assignments (course_id, title, description, deadline, max_score, created_by) 
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$course_id, $title, $description, $deadline, $max_score, $user_id]);
        
        $assignment_id = $conn->lastInsertId();
        
        // Добавляем уведомления для студентов
        $sql = "SELECT student_id FROM course_enrollments WHERE course_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$course_id]);
        $students = $stmt->fetchAll();
        
        foreach ($students as $student) {
            addNotification($student['student_id'], 
                          'Новое задание', 
                          "Добавлено новое задание: $title. Срок сдачи: $deadline", 
                          1);
        }
        
        // Уведомление для преподавателя
        addNotification($user_id, 'Задание создано', "Вы создали задание: $title", 1);
        
        $_SESSION['success'] = 'Задание успешно создано';
        header("Location: ../courses/view.php?id=$course_id");
        exit();
    } catch (PDOException $e) {
        $_SESSION['error'] = 'Ошибка при создании задания: ' . $e->getMessage();
    }
}
?>

<div class="form-container">
    <form method="POST" action="">
        <div class="form-group">
            <label for="title">Название задания *</label>
            <input type="text" id="title" name="title" required maxlength="255">
        </div>
        
        <div class="form-group">
            <label for="description">Описание задания *</label>
            <textarea id="description" name="description" rows="6" required></textarea>
        </div>
        
        <div class="form-grid">
            <div class="form-group">
                <label for="deadline">Срок сдачи *</label>
                <input type="datetime-local" id="deadline" name="deadline" required>
            </div>
            
            <div class="form-group">
                <label for="max_score">Максимальный балл</label>
                <input type="number" id="max_score" name="max_score" min="0" max="1000" step="0.1" value="100">
            </div>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Создать задание
            </button>
            <a href="../courses/view.php?id=<?php echo $course_id; ?>" class="btn btn-secondary">Отмена</a>
        </div>
    </form>
</div>

<script>
// Устанавливаем минимальную дату - сегодня
const today = new Date();
const formattedDate = today.toISOString().slice(0, 16);
document.getElementById('deadline').min = formattedDate;

// Устанавливаем значение по умолчанию - завтра в 23:59
const tomorrow = new Date(today);
tomorrow.setDate(tomorrow.getDate() + 1);
tomorrow.setHours(23, 59);
const tomorrowFormatted = tomorrow.toISOString().slice(0, 16);
document.getElementById('deadline').value = tomorrowFormatted;
</script>


<?php require_once '../../includes/footer.php'; ?>
