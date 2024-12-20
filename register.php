<?php
include 'admin/database.php'; 

$message = ''; 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $profile_image = null;

    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'images/profile_images/'; 

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true); 
        }

        $extension = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
        $fileName = uniqid('profile_') . '.' . $extension;
        $targetPath = $uploadDir . $fileName;

        if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $targetPath)) {
            $profile_image = $targetPath; 
        } else {
            $message = 'Ошибка загрузки изображения. Попробуйте снова.';
        }
    }

    $stmt = $mysqli->prepare("INSERT INTO users (username, email, password, profile_image) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $username, $email, $password, $profile_image);

    if ($stmt->execute()) {
        header('Location: login.php');
        exit();
    } else {
        $message = 'Ошибка регистрации. Возможно, пользователь уже существует.';
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
<link rel="icon" type="image/png" href="/images/favicon.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Регистрация</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/cover.css" rel="stylesheet">
    <style>
        .login-card {
            width: 400px; 
            margin: 0 auto; 
        }
    </style>
</head>
<body>
<div class="d-flex h-100 text-center text-bg-dark">
    <div class="wrapper cover-container d-flex w-100 h-100 p-3 mx-auto flex-column">
        <?php include "header.php"; ?> 
    </div>
</div>
<div class="container mt-5">
    
<div class="card p-4 shadow login-card">
    <h1 class="text-center mb-4">Регистрация</h1>
    <?php if ($message): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    <form method="post" enctype="multipart/form-data" class="card p-4 shadow">
        <div class="mb-3">
            <label for="username" class="form-label">Имя пользователя</label>
            <input type="text" class="form-control" id="username" name="username" required>
        </div>
        <div class="mb-3">
            <label for="email" class="form-label">Электронная почта</label>
            <input type="email" class="form-control" id="email" name="email" required>
        </div>
        <div class="mb-3">
            <label for="password" class="form-label">Пароль</label>
            <input type="password" class="form-control" id="password" name="password" required>
        </div>
        <div class="mb-3">
            <label for="profile_image" class="form-label">Фото профиля (опционально)</label>
            <input type="file" class="form-control" id="profile_image" name="profile_image" accept="image/*">
        </div>
        <button type="submit" class="btn btn-primary w-100">Зарегистрироваться</button>
    </form>
</div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
