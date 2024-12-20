<?php
include 'admin/database.php';
session_start();

if (isset($_GET['id'])) {
    $recipe_id = (int)$_GET['id'];

    $stmt = $mysqli->prepare("
    SELECT r.title, r.description, r.instructions, r.image_path, r.preparation_time, c.name AS country_name 
    FROM recipes r 
    JOIN countries c ON r.country_id = c.id 
    WHERE r.id = ?
");

    $stmt->bind_param("i", $recipe_id);
    $stmt->execute();
    $stmt->store_result();



    if ($stmt->num_rows > 0) {
        $stmt->bind_result($title, $description, $instructions, $image_path, $preparation_time, $country_name);
        $stmt->fetch();
        $is_favorite = false;
        if (isset($_SESSION['user_id'])) {
            $user_id = $_SESSION['user_id'];
            $favorite_stmt = $mysqli->prepare("SELECT id FROM favorites WHERE user_id = ? AND recipe_id = ?");
            $favorite_stmt->bind_param("ii", $user_id, $recipe_id);
            $favorite_stmt->execute();
            $favorite_stmt->store_result();
            $is_favorite = $favorite_stmt->num_rows > 0;
        }

        $stmt = $mysqli->prepare("SELECT COUNT(*) FROM favorites WHERE recipe_id = ?");
        $stmt->bind_param("i", $recipe_id);
        $stmt->execute();
        $stmt->bind_result($rating);
        $stmt->fetch();
        $stmt->close();

        $stmt = $mysqli->prepare("SELECT i.name, ri.amount, m.unit FROM recipe_ingredients ri 
                                   JOIN ingredients i ON ri.ingredient_id = i.id 
                                   JOIN measurements m ON ri.measurement_id = m.id 
                                   WHERE ri.recipe_id = ?");
        $stmt->bind_param("i", $recipe_id);
        $stmt->execute();
        $result = $stmt->get_result();

        $ingredients = [];
        while ($row = $result->fetch_assoc()) {
            $ingredients[] = $row;
        }

        $comments = [];
        $stmt = $mysqli->prepare("
            SELECT c.content, c.created_at, u.username, u.profile_image 
            FROM comments c
            JOIN users u ON c.user_id = u.id
            WHERE c.recipe_id = ?
            ORDER BY c.created_at DESC
        ");
        $stmt->bind_param("i", $recipe_id);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $comments[] = $row;
        }
    } else {
        echo "Рецепт не найден.";
        exit();
    }
} else {
    echo "Некорректный запрос.";
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['content']) && isset($_SESSION['user_id'])) {
    $content = trim($_POST['content']);
    $user_id = $_SESSION['user_id'];

    if (!empty($content)) {
        $stmt = $mysqli->prepare("INSERT INTO comments (recipe_id, user_id, content) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $recipe_id, $user_id, $content);
        $stmt->execute();
        header("Location: fullRecipe.php?id=$recipe_id");
        exit();
    }
}
$content = $mysqli->prepare("SELECT instructions FROM recipes WHERE id = ?");
$content->bind_param("i", $recipe_id);
$content->execute();
$inst = $content->get_result();
$article = $inst->fetch_assoc();


?>

<!DOCTYPE html>
<html lang="ru">

<head>
    <link rel="icon" type="image/png" href="/images/favicon.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/cover.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .profile-img {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 50%;
        }

        .recipe-image {
            max-width: 100%;
            height: auto;
            border-radius: 15px;
        }

        .img {
            max-width: 100%;
        }
    </style>
</head>

<body>
    <div class="d-flex h-100 text-center text-bg-dark">
        <div class="wrapper cover-container d-flex w-100 h-100 p-3 mx-auto flex-column">
            <?php include "header.php"; ?>
        </div>
    </div>

    <div class="container mt-5 content-wrapper">
        <h1 class="text-center"><?= htmlspecialchars($title) ?></h1>
        <div class="row mt-4">
            <div class="col-md-7">
                <h5>Описание</h5>
                <p><?= htmlspecialchars($description) ?></p>

                <h5>Ингредиенты</h5>
                <ul class="list-group mb-3" id="ingredients-list">
                    <?php foreach ($ingredients as $ingredient): ?>
                        <li class="list-group-item ingredient-item" data-base-amount="<?= $ingredient['amount'] ?>">
                            <span class="ingredient-name"><?= htmlspecialchars($ingredient['name']) ?></span>
                            <span class="ingredient-amount"><?= htmlspecialchars($ingredient['amount']) ?></span>
                            <span class="ingredient-unit"><?= htmlspecialchars($ingredient['unit']) ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>

               

            </div>
            <div class="col-md-5 text-center">
                <img src="<?= htmlspecialchars($image_path) ?>" class="img-fluid recipe-image" alt="<?= htmlspecialchars($title) ?>">
                <h5>Национальная кухня</h5>
                <p><?= htmlspecialchars($country_name) ?></p>

                <h5>Время приготовления</h5>
                <p><?= htmlspecialchars($preparation_time) ?> минут</p>
            </div>
            <div> 
                <h5>Инструкции</h5>
                <?php
                if ($article) {
                    echo $article['instructions'];
                } else {
                    echo "Инструкций не обнаружено!";
                }
                ?>
            </div>

        </div>

        <div class="text-center my-4">
            <h5>Рейтинг рецепта: <?= $rating ?></h5>
        </div>

        <div class="text-center my-4">
            <?php if (isset($_SESSION['user_id'])): ?>
                <button id="favorite-toggle" class="btn btn-outline-warning">
                    <i class="bi <?= $is_favorite ? 'bi-heart-fill' : 'bi-heart'; ?>"></i>
                    <?= $is_favorite ? 'Удалить из избранного' : 'Добавить в избранное' ?>
                </button>
            <?php else: ?>
                <p><a href="login.php">Войдите</a>, чтобы добавлять рецепты в избранное.</p>
            <?php endif; ?>
        </div>

        <div class="mt-5">
            <h2>Комментарии</h2>

            <?php if (isset($_SESSION['user_id'])): ?>
                <form method="post" class="mb-4">
                    <div class="mb-3">
                        <textarea name="content" class="form-control" rows="3" placeholder="Оставьте комментарий..." required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Отправить</button>
                </form>
            <?php else: ?>
                <p><a href="login.php">Войдите</a>, чтобы оставить комментарий.</p>
            <?php endif; ?>
            <?php if (!empty($comments)): ?>
                <ul class="list-group">
                    <?php foreach ($comments as $comment): ?>
                        <li class="list-group-item">
                            <div class="d-flex align-items-center mb-2">
                                <img src="<?= htmlspecialchars($comment['profile_image'] ?: 'images/profile_images/default.png') ?>"
                                    class="profile-img me-3"
                                    alt="<?= htmlspecialchars($comment['username']) ?>">
                                <strong><?= htmlspecialchars($comment['username']) ?></strong>
                                <small class="ms-auto text-muted"><?= htmlspecialchars($comment['created_at']) ?></small>
                            </div>
                            <p><?= htmlspecialchars($comment['content']) ?></p>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p>Комментариев пока нет. Будьте первым!</p>
            <?php endif; ?>
        </div>
    </div>

    <script>
        document.querySelector('#favorite-toggle').addEventListener('click', function() {
            fetch('favorite_toggle.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        recipe_id: <?= $recipe_id ?>,
                    }),
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const icon = document.querySelector('#favorite-toggle i');
                        const button = document.querySelector('#favorite-toggle');
                        icon.classList.toggle('bi-heart');
                        icon.classList.toggle('bi-heart-fill');
                        button.lastChild.textContent = data.action === 'added' ? ' Удалить из избранного' : ' Добавить в избранное';
                    } else {
                        alert(data.message);
                    }
                })
                .catch(error => console.error('Ошибка:', error));
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>