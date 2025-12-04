<?php
require_once '../../includes/header.php';

// Добавляем ссылку на CSS файл в head
echo '<link rel="stylesheet" href="../../assets/css/courses-style.css">';

$course_id = $_GET['id'] ?? 0;
$user_id = getCurrentUserId();
$role_id = $_SESSION['role_id'];

$course = getCourseById($course_id, $user_id, $role_id);

if (!$course) {
    $_SESSION['error'] = 'Курс не найден или у вас нет доступа';
    header('Location: index.php');
    exit();
}

$assignments = getCourseAssignments($course_id, $user_id, $role_id);
$materials = getCourseMaterials($course_id);
$is_teacher = isTeacher();
?>

<div class="course-view">
    <!-- Заголовок курса -->
    <div class="course-header">
        <div class="course-info">
            <h1><?php echo htmlspecialchars($course['title']); ?></h1>
            <p class="course-description"><?php echo nl2br(htmlspecialchars($course['description'])); ?></p>
            
            <div class="course-meta">
                <span class="meta-item">
                    <i class="fas fa-user-tie"></i>
                    Преподаватель: <?php echo $course['first_name'] . ' ' . $course['last_name']; ?>
                </span>
                <span class="meta-item">
                    <i class="fas fa-calendar-alt"></i>
                    Создан: <?php echo date('d.m.Y', strtotime($course['created_at'])); ?>
                </span>
                <span class="status-badge status-<?php echo strtolower($course['status_name']); ?>">
                    <?php echo $course['status_name']; ?>
                </span>
                
                <?php if ($course['syllabus_file']): ?>
                    <a href="../../assets/uploads/<?php echo $course['syllabus_file']; ?>" 
                       class="btn btn-sm" target="_blank" style="margin-left: auto;">
                        <i class="fas fa-file-pdf"></i> Рабочая программа
                    </a>
                <?php endif; ?>
            </div>
        </div>
        
        <?php if ($is_teacher): ?>
            <div class="course-actions">
                <a href="manage.php?id=<?php echo $course_id; ?>" class="btn btn-primary">
                    <i class="fas fa-cog"></i> Управление курсом
                </a>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Вкладки -->
    <div class="course-tabs">
        <button class="tab-btn active" onclick="switchTab('assignments')">
            <i class="fas fa-tasks"></i> Задания
        </button>
        <button class="tab-btn" onclick="switchTab('materials')">
            <i class="fas fa-book"></i> Материалы
        </button>
        <?php if ($is_teacher): ?>
            <button class="tab-btn" onclick="switchTab('students')">
                <i class="fas fa-users"></i> Студенты
            </button>
        <?php endif; ?>
    </div>
    
    <div class="tab-content active" id="assignments-tab">
        <div class="section-header">
            <h3><i class="fas fa-tasks"></i> Учебные задания</h3>
            <?php if ($is_teacher): ?>
                <a href="../assignments/create.php?course_id=<?php echo $course_id; ?>" class="btn btn-sm">
                    <i class="fas fa-plus"></i> Добавить задание
                </a>
            <?php endif; ?>
        </div>
        
        <?php if (empty($assignments)): ?>
            <p class="empty-message">Задания пока не добавлены</p>
        <?php else: ?>
            <div class="assignments-list">
                <?php foreach ($assignments as $assignment): ?>
                    <div class="assignment-item <?php echo $assignment['is_overdue'] ? 'overdue' : ''; ?>">
                        <div class="assignment-info">
                            <h4><?php echo htmlspecialchars($assignment['title']); ?></h4>
                            <p class="assignment-description">
                                <?php echo htmlspecialchars(substr($assignment['description'], 0, 150)); ?>...
                            </p>
                            <div class="assignment-meta">
                                <span class="deadline">
                                    <i class="fas fa-clock"></i>
                                    До: <?php echo date('d.m.Y H:i', strtotime($assignment['deadline'])); ?>
                                </span>
                                <span class="max-score">
                                    <i class="fas fa-star"></i>
                                    Макс. балл: <?php echo $assignment['max_score']; ?>
                                </span>
                                <?php if (!$is_teacher): ?>
                                    <span class="submission-status">
                                        <?php if ($assignment['submitted']): ?>
                                            <span class="status-badge status-отправлено">
                                                <i class="fas fa-check"></i> Отправлено
                                            </span>
                                        <?php elseif ($assignment['is_overdue']): ?>
                                            <span class="status-badge status-просрочено">
                                                <i class="fas fa-exclamation-triangle"></i> Просрочено
                                            </span>
                                        <?php else: ?>
                                            <span class="status-badge status-не-сдано">
                                                <i class="fas fa-clock"></i> Не сдано
                                            </span>
                                        <?php endif; ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="assignment-actions">
                            <?php if ($is_teacher): ?>
                                <a href="../assignments/grade.php?id=<?php echo $assignment['assignment_id']; ?>" 
                                   class="btn btn-sm">
                                    <i class="fas fa-check-circle"></i> Проверить
                                </a>
                                <a href="../assignments/edit.php?id=<?php echo $assignment['assignment_id']; ?>" 
                                   class="btn btn-sm btn-secondary">
                                    <i class="fas fa-edit"></i> Редактировать
                                </a>
                            <?php elseif (!$assignment['submitted'] && !$assignment['is_overdue']): ?>
                                <a href="../assignments/submit.php?id=<?php echo $assignment['assignment_id']; ?>" 
                                   class="btn btn-sm btn-primary">
                                    <i class="fas fa-upload"></i> Сдать работу
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="tab-content" id="materials-tab">
        <div class="section-header">
            <h3><i class="fas fa-book"></i> Учебные материалы</h3>
            <?php if ($is_teacher): ?>
                <a href="../materials/upload.php?course_id=<?php echo $course_id; ?>" class="btn btn-sm">
                    <i class="fas fa-plus"></i> Добавить материал
                </a>
            <?php endif; ?>
        </div>
        
        <?php if (empty($materials)): ?>
            <p class="empty-message">Материалы пока не добавлены</p>
        <?php else: ?>
            <div class="materials-list">
                <?php foreach ($materials as $material): ?>
                    <div class="material-item">
                        <div class="material-icon">
                            <?php
                            $ext = pathinfo($material['file_path'], PATHINFO_EXTENSION);
                            $icon = 'fa-file';
                            if (in_array($ext, ['pdf'])) $icon = 'fa-file-pdf';
                            elseif (in_array($ext, ['doc', 'docx'])) $icon = 'fa-file-word';
                            elseif (in_array($ext, ['ppt', 'pptx'])) $icon = 'fa-file-powerpoint';
                            ?>
                            <i class="fas <?php echo $icon; ?>"></i>
                        </div>
                        <div class="material-info">
                            <h4><?php echo htmlspecialchars($material['title']); ?></h4>
                            <?php if ($material['description']): ?>
                                <p class="material-description">
                                    <?php echo htmlspecialchars($material['description']); ?>
                                </p>
                            <?php endif; ?>
                            <div class="material-meta">
                                <span class="uploader">
                                    <i class="fas fa-user"></i>
                                    <?php echo $material['first_name'] . ' ' . $material['last_name']; ?>
                                </span>
                                <span class="upload-date">
                                    <i class="fas fa-calendar"></i>
                                    <?php echo date('d.m.Y', strtotime($material['created_at'])); ?>
                                </span>
                            </div>
                        </div>
                        <div class="material-actions">
                            <a href="../../assets/uploads/<?php echo $material['file_path']; ?>" 
                               class="btn btn-sm" target="_blank" download>
                                <i class="fas fa-download"></i> Скачать
                            </a>
                            <?php if ($is_teacher): ?>
                                <a href="../materials/edit.php?id=<?php echo $material['material_id']; ?>" 
                                   class="btn btn-sm btn-secondary">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="../materials/delete.php?id=<?php echo $material['material_id']; ?>" 
                                   class="btn btn-sm btn-danger"
                                   onclick="return confirmDelete('Удалить этот материал?')">
                                    <i class="fas fa-trash"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <?php if ($is_teacher): ?>
        <div class="tab-content" id="students-tab">
            <div class="section-header">
                <h3><i class="fas fa-users"></i> Студенты курса</h3>
                <button class="btn btn-sm" onclick="showEnrollModal()">
                    <i class="fas fa-user-plus"></i> Записать студента
                </button>
            </div>
            
            <?php
            $students = getCourseStudents($course_id);
            if (empty($students)): ?>
                <p class="empty-message">На курс еще не записаны студенты</p>
            <?php else: ?>
                <div class="students-table">
                    <table>
                        <thead>
                            <tr>
                                <th>ФИО</th>
                                <th>Email</th>
                                <th>Дата записи</th>
                                <th>Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($students as $student): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></td>
                                    <td><?php echo htmlspecialchars($student['email']); ?></td>
                                    <td><?php echo date('d.m.Y', strtotime($student['enrolled_at'])); ?></td>
                                    <td>
                                        <a href="javascript:void(0)" 
                                           onclick="unenrollStudent(<?php echo $student['user_id']; ?>)"
                                           class="btn btn-sm btn-danger"
                                           title="Удалить с курса">
                                            <i class="fas fa-user-minus"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Модальное окно для записи студента -->
        <div id="enrollModal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h3>Записать студента на курс</h3>
                    <span class="close-modal" onclick="closeEnrollModal()">&times;</span>
                </div>
                <div class="modal-body">
                    <form id="enrollForm" onsubmit="enrollStudent(event)">
                        <input type="hidden" name="course_id" value="<?php echo $course_id; ?>">
                        <div class="form-group">
                            <label for="student_email">Email студента:</label>
                            <input type="email" id="student_email" name="student_email" required>
                        </div>
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">Записать</button>
                            <button type="button" class="btn btn-secondary" onclick="closeEnrollModal()">Отмена</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
// JavaScript код остается без изменений
function switchTab(tabName) {
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

<?php if ($is_teacher): ?>
function showEnrollModal() {
    document.getElementById('enrollModal').style.display = 'flex';
}

function closeEnrollModal() {
    document.getElementById('enrollModal').style.display = 'none';
    document.getElementById('enrollForm').reset();
}

function enrollStudent(e) {
    e.preventDefault();
    
    const form = document.getElementById('enrollForm');
    const formData = new FormData(form);
    
    fetch('../../api/enroll.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            location.reload();
        } else {
            alert('Ошибка: ' + data.message);
        }
    });
}

function unenrollStudent(studentId) {
    if (confirm('Удалить студента с курса?')) {
        const formData = new FormData();
        formData.append('student_id', studentId);
        formData.append('course_id', <?php echo $course_id; ?>);
        
        fetch('../../api/enroll.php', {
            method: 'DELETE',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                location.reload();
            } else {
                alert('Ошибка: ' + data.message);
            }
        });
    }
}
<?php endif; ?>
</script>

<?php require_once '../../includes/footer.php'; ?>