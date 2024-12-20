<?php
include 'admin/database.php'; 
session_start();

$message = ''; 
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $mysqli->prepare("SELECT id, username, password, profile_image FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['profile_image'] = $user['profile_image'];

            header('Location: userProfile.php');
            exit();
        }
    }

    $message = 'Неверный логин или пароль.';
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
<link rel="icon" type="image/png" href="/images/favicon.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход</title>
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
    <h1 class="text-center mb-4">Вход</h1>
    <?php if ($message): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    <form method="post" class="card p-4 shadow">
        <div class="mb-3">
            <label for="email" class="form-label">Электронная почта</label>
            <input type="email" class="form-control" id="email" name="email" required>
        </div>
        <div class="mb-3">
            <label for="password" class="form-label">Пароль</label>
            <input type="password" class="form-control" id="password" name="password" required>
        </div>
        <button type="submit" class="btn btn-primary w-100">Войти</button>
    </form>
    <p class="text-center mt-3">
        Нет аккаунта? <a href="register.php">Зарегистрируйтесь</a>
    </p>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
