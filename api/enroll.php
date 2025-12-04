<?php
require_once '../includes/session.php';
require_once '../includes/database.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if (!isLoggedIn() || !isTeacher()) {
    echo json_encode(['success' => false, 'message' => 'Доступ запрещен']);
    exit();
}

$method = $_SERVER['REQUEST_METHOD'];
$conn = getDBConnection();

if ($method === 'POST') {
    // Запись студента на курс
    $course_id = $_POST['course_id'] ?? 0;
    $student_email = $_POST['student_email'] ?? '';
    
    // Проверяем доступ преподавателя к курсу
    $user_id = getCurrentUserId();
    $sql = "SELECT * FROM courses WHERE course_id = ? AND teacher_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$course_id, $user_id]);
    $course = $stmt->fetch();
    
    if (!$course) {
        echo json_encode(['success' => false, 'message' => 'Курс не найден или нет доступа']);
        exit();
    }
    
    // Ищем студента по email
    $sql = "SELECT * FROM users WHERE email = ? AND role_id = 2";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$student_email]);
    $student = $stmt->fetch();
    
    if (!$student) {
        echo json_encode(['success' => false, 'message' => 'Студент с таким email не найден']);
        exit();
    }
    
    // Проверяем, не записан ли уже студент
    $sql = "SELECT 1 FROM course_enrollments WHERE course_id = ? AND student_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$course_id, $student['user_id']]);
    $already_enrolled = $stmt->fetch();
    
    if ($already_enrolled) {
        echo json_encode(['success' => false, 'message' => 'Студент уже записан на этот курс']);
        exit();
    }
    
    // Записываем студента
    try {
        $sql = "INSERT INTO course_enrollments (course_id, student_id) VALUES (?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$course_id, $student['user_id']]);
        
        // Добавляем уведомление студенту
        addNotification($student['user_id'], 
                      'Запись на курс', 
                      "Вы записаны на курс: {$course['title']}", 
                      4);
        
        echo json_encode(['success' => true, 'message' => 'Студент успешно записан на курс']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Ошибка при записи: ' . $e->getMessage()]);
    }
    
} elseif ($method === 'DELETE') {
    // Удаление студента с курса
    parse_str(file_get_contents('php://input'), $data);
    $course_id = $data['course_id'] ?? 0;
    $student_id = $data['student_id'] ?? 0;
    
    // Проверяем доступ преподавателя к курсу
    $user_id = getCurrentUserId();
    $sql = "SELECT * FROM courses WHERE course_id = ? AND teacher_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$course_id, $user_id]);
    $course = $stmt->fetch();
    
    if (!$course) {
        echo json_encode(['success' => false, 'message' => 'Курс не найден или нет доступа']);
        exit();
    }
    
    // Удаляем запись
    try {
        $sql = "DELETE FROM course_enrollments WHERE course_id = ? AND student_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$course_id, $student_id]);
        
        // Добавляем уведомление студенту
        addNotification($student_id, 
                      'Удаление с курса', 
                      "Вы удалены с курса: {$course['title']}", 
                      4);
        
        echo json_encode(['success' => true, 'message' => 'Студент удален с курса']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Ошибка при удалении: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Метод не поддерживается']);
}
?>