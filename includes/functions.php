<?php
// require_once ('../config/database.php');
require_once dirname(__DIR__) . '/config/database.php';

// Получить все курсы пользователя
function getUserCourses($user_id, $is_teacher = false) {
    $conn = getDBConnection();
    
    if ($is_teacher) {
        $sql = "SELECT c.*, cs.name as status_name, 
                (SELECT COUNT(*) FROM course_enrollments WHERE course_id = c.course_id) as student_count
                FROM courses c
                JOIN course_status cs ON c.course_status_id = cs.course_status_id
                WHERE c.teacher_id = ? AND c.course_status_id != 4
                ORDER BY c.created_at DESC";
    } else {
        $sql = "SELECT c.*, cs.name as status_name, 
                u.first_name, u.last_name
                FROM courses c
                JOIN course_enrollments ce ON c.course_id = ce.course_id
                JOIN course_status cs ON c.course_status_id = cs.course_status_id
                JOIN users u ON c.teacher_id = u.user_id
                WHERE ce.student_id = ? AND c.course_status_id = 2
                ORDER BY c.created_at DESC";
    }
    
    $stmt = $conn->prepare($sql);
    $stmt->bindValue(1, $user_id, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

// Получить курс по ID с проверкой доступа
function getCourseById($course_id, $user_id, $role_id) {
    $conn = getDBConnection();
    
    if ($role_id == 1) { // Преподаватель
        $sql = "SELECT c.*, cs.name as status_name, u.first_name, u.last_name,
                (SELECT COUNT(*) FROM course_enrollments WHERE course_id = c.course_id) as student_count
                FROM courses c
                JOIN course_status cs ON c.course_status_id = cs.course_status_id
                JOIN users u ON c.teacher_id = u.user_id
                WHERE c.course_id = ? AND c.teacher_id = ?";
        $params = [$course_id, $user_id];
    } else { // Студент
        $sql = "SELECT c.*, cs.name as status_name, u.first_name, u.last_name
                FROM courses c
                JOIN course_enrollments ce ON c.course_id = ce.course_id
                JOIN course_status cs ON c.course_status_id = cs.course_status_id
                JOIN users u ON c.teacher_id = u.user_id
                WHERE c.course_id = ? AND ce.student_id = ? AND c.course_status_id = 2";
        $params = [$course_id, $user_id];
    }
    
    $stmt = $conn->prepare($sql);
    $stmt->bindValue(1, $params[0], PDO::PARAM_INT);
    $stmt->bindValue(2, $params[1], PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetch();
}

// Получить задания курса
function getCourseAssignments($course_id, $user_id, $role_id) {
    $conn = getDBConnection();
    
    $sql = "SELECT a.*, 
            (SELECT COUNT(*) FROM student_submissions ss 
             WHERE ss.assignment_id = a.assignment_id AND ss.student_id = ?) as submitted,
            (SELECT ss.submission_status_id FROM student_submissions ss 
             WHERE ss.assignment_id = a.assignment_id AND ss.student_id = ? LIMIT 1) as submission_status
            FROM assignments a
            WHERE a.course_id = ?
            ORDER BY a.deadline ASC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindValue(1, $user_id, PDO::PARAM_INT);
    $stmt->bindValue(2, $user_id, PDO::PARAM_INT);
    $stmt->bindValue(3, $course_id, PDO::PARAM_INT);
    $stmt->execute();
    $assignments = $stmt->fetchAll();
    
    // Добавляем статус просрочки
    foreach ($assignments as &$assignment) {
        if (new DateTime($assignment['deadline']) < new DateTime() && $assignment['submission_status'] == 1) {
            $assignment['is_overdue'] = true;
        } else {
            $assignment['is_overdue'] = false;
        }
    }
    
    return $assignments;
}

// Получить материалы курса
function getCourseMaterials($course_id) {
    $conn = getDBConnection();
    
    $sql = "SELECT lm.*, u.first_name, u.last_name 
            FROM learning_materials lm
            JOIN users u ON lm.uploaded_by = u.user_id
            WHERE lm.course_id = ?
            ORDER BY lm.created_at DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindValue(1, $course_id, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

// Получить студентов курса
function getCourseStudents($course_id) {
    $conn = getDBConnection();
    
    $sql = "SELECT u.user_id, u.first_name, u.last_name, u.email, u.username, ce.enrolled_at
            FROM users u
            JOIN course_enrollments ce ON u.user_id = ce.student_id
            WHERE ce.course_id = ? AND u.role_id = 2
            ORDER BY ce.enrolled_at DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindValue(1, $course_id, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

// Добавить уведомление
function addNotification($user_id, $title, $message, $type_id = 1) {
    $conn = getDBConnection();
    
    $sql = "INSERT INTO notifications (user_id, title, message, notification_type_id) 
            VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bindValue(1, $user_id, PDO::PARAM_INT);
    $stmt->bindValue(2, $title, PDO::PARAM_STR);
    $stmt->bindValue(3, $message, PDO::PARAM_STR);
    $stmt->bindValue(4, $type_id, PDO::PARAM_INT);
    return $stmt->execute();
}

// Получить непрочитанные уведомления
function getUnreadNotifications($user_id, $limit = 10) {
    $conn = getDBConnection();
    
    $sql = "SELECT n.*, nt.name as type_name
            FROM notifications n
            JOIN notification_type nt ON n.notification_type_id = nt.notification_type_id
            WHERE n.user_id = ? AND n.is_read = FALSE
            ORDER BY n.created_at DESC
            LIMIT ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindValue(1, $user_id, PDO::PARAM_INT);
    $stmt->bindValue(2, (int)$limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

// Загрузить файл
function uploadFile($file, $target_dir = '../assets/uploads/') {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'Ошибка загрузки файла'];
    }
    
    // Проверка типа файла
    $allowed_types = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'txt', 'zip', 'rar'];
    $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if (!in_array($file_ext, $allowed_types)) {
        return ['success' => false, 'message' => 'Недопустимый тип файла'];
    }
    
    // Проверка размера (макс 10MB)
    if ($file['size'] > 10 * 1024 * 1024) {
        return ['success' => false, 'message' => 'Файл слишком большой (макс. 10MB)'];
    }
    
    // Генерация уникального имени
    $new_filename = uniqid() . '_' . time() . '.' . $file_ext;
    $target_path = $target_dir . $new_filename;
    
    if (move_uploaded_file($file['tmp_name'], $target_path)) {
        return ['success' => true, 'path' => $new_filename, 'original_name' => $file['name']];
    }
    
    return ['success' => false, 'message' => 'Не удалось сохранить файл'];
}
?>