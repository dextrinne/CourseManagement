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
    header('Location: ../courses/');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    
    if (!isset($_FILES['material_file']) || $_FILES['material_file']['error'] !== UPLOAD_ERR_OK) {
        $_SESSION['error'] = 'Пожалуйста, выберите файл для загрузки';
    } else {
        $upload_result = uploadFile($_FILES['material_file']);
        
        if ($upload_result['success']) {
            try {
                $sql = "INSERT INTO learning_materials (course_id, title, description, file_path, uploaded_by) 
                        VALUES (?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->execute([$course_id, $title, $description, $upload_result['path'], $user_id]);
                
                // Добавляем уведомления для студентов
                $sql = "SELECT student_id FROM course_enrollments WHERE course_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->execute([$course_id]);
                $students = $stmt->fetchAll();
                
                foreach ($students as $student) {
                    addNotification($student['student_id'], 
                                  'Новый учебный материал', 
                                  "Добавлен новый материал: $title", 
                                  2);
                }
                
                $_SESSION['success'] = 'Материал успешно загружен';
                header("Location: ../courses/view.php?id=$course_id");
                exit();
            } catch (PDOException $e) {
                $_SESSION['error'] = 'Ошибка при загрузке материала: ' . $e->getMessage();
            }
        } else {
            $_SESSION['error'] = $upload_result['message'];
        }
    }
}
?>

<div class="form-container">
    <form method="POST" action="" enctype="multipart/form-data">
        <div class="form-group">
            <label for="title">Название материала *</label>
            <input type="text" id="title" name="title" required maxlength="255">
        </div>
        
        <div class="form-group">
            <label for="description">Описание материала</label>
            <textarea id="description" name="description" rows="3" placeholder="Краткое описание материала..."></textarea>
        </div>
        
        <div class="form-group">
            <label for="material_file">Файл материала *</label>
            <input type="file" id="material_file" name="material_file" accept=".pdf,.doc,.docx,.ppt,.pptx,.txt" required>
            <small class="form-text">Поддерживаемые форматы: PDF, DOC, DOCX, PPT, PPTX, TXT. Макс. размер: 10MB</small>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-upload"></i> Загрузить материал
            </button>
            <a href="../courses/view.php?id=<?php echo $course_id; ?>" class="btn btn-secondary">Отмена</a>
        </div>
    </form>
</div>


<?php require_once '../../includes/footer.php'; ?>
