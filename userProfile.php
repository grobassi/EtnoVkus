<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

include 'admin/database.php';

$user_id = $_SESSION['user_id'];
$message = '';

$recipes_per_page = 5;

$current_page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($current_page - 1) * $recipes_per_page;

$total_query = "SELECT COUNT(*) AS total FROM favorites WHERE user_id = ?";
$stmt = $mysqli->prepare($total_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$total_result = $stmt->get_result();
$total_row = $total_result->fetch_assoc();
$total_favorites = $total_row['total'];
$total_pages = ceil($total_favorites / $recipes_per_page);

$favorites = [];
$query = "
    SELECT r.id, r.title, r.description, r.image_path 
    FROM recipes r
    JOIN favorites f ON r.id = f.recipe_id
    WHERE f.user_id = ?
    LIMIT ? OFFSET ?";
$stmt = $mysqli->prepare($query);
$stmt->bind_param("iii", $user_id, $recipes_per_page, $offset);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $favorites[] = $row;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['avatar'])) {
    $uploadDir = 'images/profile_images/';

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    if ($_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
        $errorMessages = [
            UPLOAD_ERR_INI_SIZE => 'Размер файла превышает допустимый.',
            UPLOAD_ERR_FORM_SIZE => 'Размер файла превышает ограничение формы.',
            UPLOAD_ERR_PARTIAL => 'Файл был загружен только частично.',
            UPLOAD_ERR_NO_FILE => 'Файл не был загружен.',
            UPLOAD_ERR_NO_TMP_DIR => 'Отсутствует временная папка.',
            UPLOAD_ERR_CANT_WRITE => 'Не удалось записать файл на диск.',
            UPLOAD_ERR_EXTENSION => 'Загрузка файла остановлена расширением PHP.',
        ];
        $message = $errorMessages[$_FILES['avatar']['error']] ?? 'Неизвестная ошибка.';
    } else {
        $extension = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
        $fileName = uniqid('profile_') . '.' . $extension;
        $targetPath = $uploadDir . $fileName;

        if (move_uploaded_file($_FILES['avatar']['tmp_name'], $targetPath)) {
            $stmt = $mysqli->prepare("UPDATE users SET profile_image = ? WHERE id = ?");
            $stmt->bind_param("si", $targetPath, $user_id);
            $stmt->execute();

            $_SESSION['profile_image'] = $targetPath;

            $message = 'Аватар успешно обновлен!';
        } else {
            $message = 'Ошибка загрузки аватара. Попробуйте снова.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
<link rel="icon" type="image/png" href="/images/favicon.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Личный кабинет</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/cover.css" rel="stylesheet">
</head>
<body>
<div class="d-flex h-100 text-center text-bg-dark">
    <div class="wrapper cover-container d-flex w-100 h-100 p-3 mx-auto flex-column">
        <?php include "header.php"; ?>
    </div>
</div>
<div class="container mt-5">
    <h1 class="text-center mb-4">Личный кабинет</h1>

    <?php if ($message): ?>
        <div class="alert alert-info text-center"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <div class="card mb-4">
        <div class="card-body text-center">
            <h3 class="card-title"><?= htmlspecialchars($_SESSION['username']) ?></h3>
            <div class="profile-image mb-3">
                <img src="<?= $_SESSION['profile_image'] ?: 'uploads/profile_images/default.png' ?>" 
                     alt="Аватар" 
                     class="img-thumbnail" 
                     style="width: 150px; height: 150px; object-fit: cover;">
            </div>
            <form method="post" enctype="multipart/form-data">
                <div class="mb-3">
                    <input type="file" name="avatar" class="form-control" accept="image/*">
                </div>
                <button type="submit" class="btn btn-primary">Обновить аватар</button>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <h3 class="card-title">Избранные рецепты</h3>
            <?php if (!empty($favorites)): ?>
                <ul class="list-group">
                    <?php foreach ($favorites as $recipe): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <a href="fullRecipe.php?id=<?= $recipe['id'] ?>" class="text-decoration-none">
                                <div>
                                    <h5><?= htmlspecialchars($recipe['title']) ?></h5>
                                    <p><?= htmlspecialchars(mb_strimwidth($recipe['description'], 0, 100, '...')) ?></p>
                                </div>
                            </a>
                            <img src="<?= $recipe['image_path'] ?>" 
                                 alt="<?= htmlspecialchars($recipe['title']) ?>" 
                                 style="width: 100px; height: 100px; object-fit: cover;">
                        </li>
                    <?php endforeach; ?>
                </ul>

                <div class="d-flex justify-content-center mt-4">
                    <ul class="pagination">
                        <?php if ($current_page > 1): ?>
                            <li class="page-item"><a class="page-link" href="?page=1">Первая</a></li>
                            <li class="page-item"><a class="page-link" href="?page=<?= $current_page - 1 ?>">Назад</a></li>
                        <?php endif; ?>

                        <?php for ($page = 1; $page <= $total_pages; $page++): ?>
                            <li class="page-item <?= $page == $current_page ? 'active' : '' ?>">
                                <a class="page-link" href="?page=<?= $page ?>"><?= $page ?></a>
                            </li>
                        <?php endfor; ?>

                        <?php if ($current_page < $total_pages): ?>
                            <li class="page-item"><a class="page-link" href="?page=<?= $current_page + 1 ?>">Вперед</a></li>
                            <li class="page-item"><a class="page-link" href="?page=<?= $total_pages ?>">Последняя</a></li>
                        <?php endif; ?>
                    </ul>
                </div>
            <?php else: ?>
                <p class="text-center">Вы еще не добавили рецепты в избранное.</p>
            <?php endif; ?>
        </div>
    </div>

</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
