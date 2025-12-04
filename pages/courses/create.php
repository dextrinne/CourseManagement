<?php
require_once '../../includes/header.php';
requireTeacher();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn = getDBConnection();
    
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $status_id = $_POST['status_id'] ?? 1;
    $teacher_id = getCurrentUserId();
    
    // Загрузка рабочей программы
    $syllabus_file = null;
    if (isset($_FILES['syllabus_file']) && $_FILES['syllabus_file']['error'] === UPLOAD_ERR_OK) {
        $upload_result = uploadFile($_FILES['syllabus_file']);
        if ($upload_result['success']) {
            $syllabus_file = $upload_result['path'];
        }
    }
    
    try {
        $sql = "INSERT INTO courses (title, description, syllabus_file, teacher_id, course_status_id) 
                VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$title, $description, $syllabus_file, $teacher_id, $status_id]);
        
        $course_id = $conn->lastInsertId();
        
        // Добавление уведомления
        addNotification($teacher_id, 'Курс создан', "Вы создали курс: $title", 4);
        
        $_SESSION['success'] = 'Курс успешно создан!';
        header("Location: manage.php?id=$course_id");
        exit();
    } catch (PDOException $e) {
        $_SESSION['error'] = 'Ошибка при создании курса: ' . $e->getMessage();
    }
}
?>

<div class="form-container">
    <form method="POST" action="" enctype="multipart/form-data">
        <div class="form-group">
            <label for="title">Название курса *</label>
            <input type="text" id="title" name="title" required maxlength="255">
        </div>
        
        <div class="form-group">
            <label for="description">Описание курса</label>
            <textarea id="description" name="description" rows="5"></textarea>
        </div>
        
        <div class="form-group">
            <label for="syllabus_file">Рабочая программа (PDF/DOC)</label>
            <input type="file" id="syllabus_file" name="syllabus_file" accept=".pdf,.doc,.docx">
        </div>
        
        <div class="form-group">
            <label for="status_id">Статус курса</label>
            <select id="status_id" name="status_id">
                <?php
                $conn = getDBConnection();
                $sql = "SELECT * FROM course_status WHERE course_status_id != 4";
                $stmt = $conn->query($sql);
                $statuses = $stmt->fetchAll();
                
                foreach ($statuses as $status):
                ?>
                    <option value="<?php echo $status['course_status_id']; ?>">
                        <?php echo $status['name']; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Создать курс
            </button>
            <a href="../../pages/dashboard.php" class="btn btn-secondary">Отмена</a>
        </div>
    </form>
</div>

<?php require_once '../../includes/footer.php'; ?>