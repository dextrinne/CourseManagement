<?php
require_once '../../includes/session.php';
requireTeacher();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $course_id = $_POST['course_id'] ?? 0;
    $status_id = $_POST['status_id'] ?? 0;
    $user_id = getCurrentUserId();
    
    $conn = getDBConnection();
    
    // Проверяем, что курс принадлежит преподавателю
    $sql = "SELECT * FROM courses WHERE course_id = ? AND teacher_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$course_id, $user_id]);
    $course = $stmt->fetch();
    
    if ($course) {
        $sql = "UPDATE courses SET course_status_id = ? WHERE course_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$status_id, $course_id]);
        
        // Добавляем уведомление для студентов
        $status_names = ['', 'Черновик', 'Активен', 'Завершен', 'Удален'];
        $status_name = $status_names[$status_id];
        
        // Получаем студентов курса
        $sql = "SELECT student_id FROM course_enrollments WHERE course_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$course_id]);
        $students = $stmt->fetchAll();
        
        foreach ($students as $student) {
            addNotification($student['student_id'], 
                          'Статус курса изменен', 
                          "Курс '{$course['title']}' переведен в статус '{$status_name}'", 
                          4);
        }
        
        $_SESSION['success'] = "Статус курса успешно изменен на '{$status_name}'";
    } else {
        $_SESSION['error'] = 'Курс не найден или нет доступа';
    }
}

header("Location: manage.php?id=$course_id");
exit();
?>