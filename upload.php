<?php
session_start(); // Начинаем сессию

// Проверяем, был ли отправлен файл
if (isset($_FILES['image']) && is_array($_FILES['image']['error'])) {
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif']; // Разрешенные типы файлов
    $maxSize = 2 * 1024 * 1024; // Максимальный размер файла в байтах (2 МБ)
    $success = true;
    $paths = []; // Массив для хранения путей к изображениям

    // Проходим по всем файлам
    foreach ($_FILES['image']['error'] as $key => $error) {
        if ($error == UPLOAD_ERR_OK) {
            $tmp_name = $_FILES['image']['tmp_name'][$key];
            $name = $_FILES['image']['name'][$key];
            $size = $_FILES['image']['size'][$key];
            $type = $_FILES['image']['type'][$key];

            // Проверяем тип файла
            if (!in_array($type, $allowedTypes)) {
                $_SESSION['message'] = "Ошибка: недопустимый тип файла. Разрешены только JPEG, PNG и GIF.";
                $success = false;
                break;
            }

            // Проверяем размер файла
            if ($size > $maxSize) {
                $_SESSION['message'] = "Ошибка: файл слишком большой. Максимальный размер файла - 2 МБ.";
                $success = false;
                break;
            }

            // Путь, куда будет сохранен файл
            $targetDir = "uploads/";
            // Генерируем уникальное имя файла
            $targetFile = $targetDir . uniqid() . basename($name);

            // Пытаемся переместить файл в указанное место
            if (!move_uploaded_file($tmp_name, $targetFile)) {
                $_SESSION['message'] = "Произошла ошибка при загрузке файла.";
                $success = false;
                break;
            }

            // Добавляем путь к изображению в массив
            $paths[] = $targetFile;
        }
    }

    if ($success) {
        // Сериализуем массив путей к изображениям
        $serializedPaths = serialize($paths);

        // Соединение с базой данных
        $db = new mysqli('localhost', 'username', 'password', 'database');
        if ($db->connect_errno) {
            die("Ошибка подключения к базе данных: " . $db->connect_error);
        }

        // Подготовка запроса для сохранения путей к изображениям в базе данных
        $stmt = $db->prepare("INSERT INTO users (name, email, phone, comment, image_paths) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $_POST['name'], $_POST['email'], $_POST['phone'], $_POST['comment'], $serializedPaths);
        $stmt->execute();
        $stmt->close();

        $_SESSION['message'] = "Файлы были успешно загружены.";
    }

    $db->close();
} else {
    $_SESSION['message'] = "Файл не был отправлен.";
}

header("Location: index.html"); // Перенаправляем обратно на страницу формы
exit;
?>