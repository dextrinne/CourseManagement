# ПП для организации учебного процесса по дисциплине.


## Общая структура проекта
├── 📁 assets/                            # СТАТИЧЕСКИЕ РЕСУРСЫ<br>
│   ├── 📁 css/<br>
│   │   ├── styleReports.css<br>
│   │   ├── styleNotifications.css<br>
│   │   ├── styleCourses.css<br>
│   │   └── style.css                    # Основные стили (серые тона)<br>
│<br>
├── 📁 config/                          # КОНФИГУРАЦИОННЫЕ ФАЙЛЫ<br>
│   ├── database.php                    # Настройки подключения к БД<br>
│   ├── db_course.sql                   # SQL-структура базы данных<br>
│   ├── test_data.php                   # Тестовые данные для заполнения<br>
│<br>
├── 📁 includes/                        # ВКЛЮЧАЕМЫЕ ФАЙЛЫ<br>
│   ├── header.php                      # Шапка сайта (HTML + PHP)<br>
│   ├── footer.php                      # Подвал сайта<br>
│   ├── session.php                     # Управление сессиями<br>
│   ├── functions.php                   # Общие вспомогательные функции<br>
│   ├── init.php                        # Инициализация приложения<br>
│<br>
├── 📁 pages/                           # ОСНОВНЫЕ СТРАНИЦЫ ПРИЛОЖЕНИЯ<br>
│   │<br>
│   ├── 📁 courses/                     # УПРАВЛЕНИЕ КУРСАМИ<br>
│   │   ├── create.php                  # Создание курса (для преподавателей)<br>
│   │   ├── view.php                    # Просмотр курса<br>
│   │   ├── manage.php                  # Управление курсом <br>
│   │   ├── update_status.php           # Обновление статуса курса<br>
│   │<br>
│   ├── 📁 assignments/                 # ЗАДАНИЯ И РАБОТЫ<br>
│   │   ├── create.php                  # Создание задания<br>
│   │   ├── edit.php                    # Редактирование задания<br>
│   │   ├── submit.php                  # Сдача работы студентом<br>
│   │   ├── grade.php                   # Проверка работы преподавателем<br>
│   │<br>
│   ├── 📁 materials/                   # УЧЕБНЫЕ МАТЕРИАЛЫ<br>
│   │   ├── upload.php                  # Загрузка материала<br>
│   │<br>
│   ├── dashboard.php                   # ЛИЧНЫЙ КАБИНЕТ (главная)<br>
│   ├── login.php                     <br>
│   ├── logout.php      <br>
│   ├── notifications.php               # УВЕДОМЛЕНИЯ<br>
│   ├── reports.php                     # ОТЧЕТЫ (для преподавателей)<br>
│<br>
├── 📁 api/                             # API ENDPOINTS<br>
│   ├── enroll.php                      # Запись на курсы<br>
│<br>
├── index.php                           # ГЛАВНАЯ СТРАНИЦА (редирект)<br>
