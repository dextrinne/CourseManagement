<?php
//localhost/lms/config/test_data.php

// Настройки отображения ошибок
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Подключение к базе данных
$host = "localhost";
$dbname = "course_management";
$username = "root";
$password = "";

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Успешное подключение к базе данных<br>";
} catch(PDOException $e) {
    die("Ошибка подключения: " . $e->getMessage());
}

// Функция для хэширования пароля
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

// Массив тестовых данных
$test_data = [
    'users' => [
        [
            'username' => 'teacher1',
            'email' => 'teacher1@university.ru',
            'password' => '123456',
            'role_id' => 1,
            'first_name' => 'Иван',
            'last_name' => 'Петров'
        ],
        [
            'username' => 'teacher2',
            'email' => 'teacher2@university.ru',
            'password' => '123456',
            'role_id' => 1,
            'first_name' => 'Мария',
            'last_name' => 'Сидорова'
        ],
        [
            'username' => 'student1',
            'email' => 'student1@university.ru',
            'password' => '123456',
            'role_id' => 2,
            'first_name' => 'Алексей',
            'last_name' => 'Иванов'
        ],
        [
            'username' => 'student2',
            'email' => 'student2@university.ru',
            'password' => '123456',
            'role_id' => 2,
            'first_name' => 'Екатерина',
            'last_name' => 'Смирнова'
        ],
        [
            'username' => 'student3',
            'email' => 'student3@university.ru',
            'password' => '123456',
            'role_id' => 2,
            'first_name' => 'Дмитрий',
            'last_name' => 'Кузнецов'
        ]
    ],
    'courses' => [
        [
            'title' => 'Программная инженерия',
            'description' => 'Курс по основам программной инженерии, методологиям разработки ПО и управлению проектами.',
            'teacher_id' => 1,
            'course_status_id' => 2
        ],
        [
            'title' => 'Базы данных',
            'description' => 'Изучение реляционных баз данных, SQL, проектирования и оптимизации запросов.',
            'teacher_id' => 1,
            'course_status_id' => 2
        ],
        [
            'title' => 'Веб-разработка',
            'description' => 'Современные технологии веб-разработки: HTML5, CSS3, JavaScript, PHP, фреймворки.',
            'teacher_id' => 2,
            'course_status_id' => 2
        ],
        [
            'title' => 'Черновик курса',
            'description' => 'Этот курс находится в стадии разработки и еще не доступен студентам.',
            'teacher_id' => 2,
            'course_status_id' => 1
        ]
    ],
    'assignments' => [
        [
            'course_id' => 1,
            'title' => 'Лабораторная работа 1: Моделирование требований',
            'description' => 'Разработать диаграмму вариантов использования для системы управления библиотекой.',
            'deadline' => '2025-02-15 23:59:00',
            'max_score' => 100.00,
            'created_by' => 1
        ],
        [
            'course_id' => 1,
            'title' => 'Лабораторная работа 2: Проектирование архитектуры',
            'description' => 'Спроектировать архитектуру ПО и построить диаграммы классов.',
            'deadline' => '2025-02-28 23:59:00',
            'max_score' => 100.00,
            'created_by' => 1
        ],
        [
            'course_id' => 2,
            'title' => 'Проектирование базы данных',
            'description' => 'Спроектировать схему БД для интернет-магазина и написать основные запросы.',
            'deadline' => '2025-02-20 23:59:00',
            'max_score' => 100.00,
            'created_by' => 1
        ]
    ]
];

echo "<h2>Добавление тестовых данных</h2>";
echo "<div style='font-family: Arial, sans-serif; line-height: 1.6;'>";

// Добавление пользователей
echo "<h3>Добавление пользователей:</h3>";
foreach ($test_data['users'] as $user) {
    try {
        // Хэшируем пароль
        $hashed_password = hashPassword($user['password']);
        
        $sql = "INSERT INTO users (username, email, password_hash, role_id, first_name, last_name) 
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            $user['username'],
            $user['email'],
            $hashed_password,
            $user['role_id'],
            $user['first_name'],
            $user['last_name']
        ]);
        
        $role_name = $user['role_id'] == 1 ? 'Преподаватель' : 'Студент';
        echo "Добавлен {$role_name}: {$user['first_name']} {$user['last_name']} ({$user['username']})<br>";
        
    } catch(PDOException $e) {
        if ($e->getCode() == 23000) { // Ошибка дублирования
            echo "Пользователь {$user['username']} уже существует<br>";
        } else {
            echo "Ошибка при добавлении пользователя {$user['username']}: " . $e->getMessage() . "<br>";
        }
    }
}

// Добавление курсов
echo "<h3>Добавление курсов:</h3>";
foreach ($test_data['courses'] as $course) {
    try {
        $sql = "INSERT INTO courses (title, description, teacher_id, course_status_id) 
                VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            $course['title'],
            $course['description'],
            $course['teacher_id'],
            $course['course_status_id']
        ]);
        
        $course_id = $conn->lastInsertId();
        echo "Добавлен курс: {$course['title']} (ID: {$course_id})<br>";
        
    } catch(PDOException $e) {
        echo "Ошибка при добавлении курса {$course['title']}: " . $e->getMessage() . "<br>";
    }
}

// Запись студентов на курсы
echo "<h3>Запись студентов на курсы:</h3>";
$enrollments = [
    [1, 3], [1, 4], [1, 5], // Курс 1: все студенты
    [2, 3], [2, 4],         // Курс 2: студенты 1 и 2
    [3, 3], [3, 5]          // Курс 3: студенты 1 и 3
];

foreach ($enrollments as $enrollment) {
    try {
        $sql = "INSERT INTO course_enrollments (course_id, student_id) VALUES (?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$enrollment[0], $enrollment[1]]);
        
        echo "Студент ID {$enrollment[1]} записан на курс ID {$enrollment[0]}<br>";
        
    } catch(PDOException $e) {
        if ($e->getCode() == 23000) {
            echo "Студент ID {$enrollment[1]} уже записан на курс ID {$enrollment[0]}<br>";
        } else {
            echo "Ошибка при записи: " . $e->getMessage() . "<br>";
        }
    }
}

// Добавление заданий
echo "<h3>Добавление заданий:</h3>";
foreach ($test_data['assignments'] as $assignment) {
    try {
        $sql = "INSERT INTO assignments (course_id, title, description, deadline, max_score, created_by) 
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            $assignment['course_id'],
            $assignment['title'],
            $assignment['description'],
            $assignment['deadline'],
            $assignment['max_score'],
            $assignment['created_by']
        ]);
        
        $assignment_id = $conn->lastInsertId();
        echo "Добавлено задание: {$assignment['title']} (ID: {$assignment_id})<br>";
        
    } catch(PDOException $e) {
        echo "Ошибка при добавлении задания {$assignment['title']}: " . $e->getMessage() . "<br>";
    }
}

// Добавление тестовых работ студентов
echo "<h3>Добавление студенческих работ:</h3>";
$submissions = [
    [1, 3, 'lab1_student1.pdf'], // Студент 1 сдал задание 1
    [1, 4, 'lab1_student2.docx'], // Студент 2 сдал задание 1
    [2, 3, 'lab2_student1.zip'],  // Студент 1 сдал задание 2
];

foreach ($submissions as $submission) {
    try {
        $sql = "INSERT INTO student_submissions (assignment_id, student_id, file_path, submission_status_id) 
                VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            $submission[0],
            $submission[1],
            $submission[2],
            1 // Статус "Отправлено"
        ]);
        
        echo "Добавлена работа: задание ID {$submission[0]}, студент ID {$submission[1]}<br>";
        
    } catch(PDOException $e) {
        echo "Ошибка при добавлении работы: " . $e->getMessage() . "<br>";
    }
}

// Добавление оценок
echo "<h3>Добавление оценок:</h3>";
$grades = [
    [1, 85.5, 'Хорошая работа, но есть замечания по оформлению'], // Оценка для работы 1
    [2, 92.0, 'Отличная работа!'], // Оценка для работы 2
];

foreach ($grades as $grade) {
    try {
        $sql = "INSERT INTO grades (submission_id, score, feedback, graded_by) 
                VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            $grade[0],
            $grade[1],
            $grade[2],
            1 // Проверил преподаватель ID 1
        ]);
        
        // Обновляем статус работы на "Оценено"
        $update_sql = "UPDATE student_submissions SET submission_status_id = 2 WHERE submission_id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->execute([$grade[0]]);
        
        echo "Добавлена оценка {$grade[1]} для работы ID {$grade[0]}<br>";
        
    } catch(PDOException $e) {
        echo "Ошибка при добавлении оценки: " . $e->getMessage() . "<br>";
    }
}

// Добавление уведомлений
echo "<h3>Добавление уведомлений:</h3>";
$notifications = [
    [3, 'Новое задание', 'Добавлено новое задание: Лабораторная работа 1', 1],
    [4, 'Новое задание', 'Добавлено новое задание: Лабораторная работа 1', 1],
    [3, 'Оценка выставлена', 'Ваша работа проверена. Оценка: 85.5', 3],
];

foreach ($notifications as $notification) {
    try {
        $sql = "INSERT INTO notifications (user_id, title, message, notification_type_id) 
                VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            $notification[0],
            $notification[1],
            $notification[2],
            $notification[3]
        ]);
        
        echo "Добавлено уведомление для пользователя ID {$notification[0]}<br>";
        
    } catch(PDOException $e) {
        echo "Ошибка при добавлении уведомления: " . $e->getMessage() . "<br>";
    }
}

echo "<hr>";
echo "<h3>Тестовые данные успешно добавлены!</h3>";
echo "<p><strong>Данные для входа:</strong></p>";
echo "<ul>";
echo "<li><strong>Преподаватель 1:</strong> teacher1 / 123456</li>";
echo "<li><strong>Преподаватель 2:</strong> teacher2 / 123456</li>";
echo "<li><strong>Студент 1:</strong> student1 / 123456</li>";
echo "<li><strong>Студент 2:</strong> student2 / 123456</li>";
echo "<li><strong>Студент 3:</strong> student3 / 123456</li>";
echo "</ul>";

echo "<p><a href='../views/auth/login.php' style='color: blue;'>Перейти к странице входа</a></p>";

echo "</div>";
?>