<?php
$mysqli = include 'database.php';

session_start(); 

function isAdmin()
{
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] == 'admin';
}

if (!isAdmin()) {
    header("Location:index.php");
    exit();
}


if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("Ошибка: ID рецепта не указан.");
}

$recipeId = intval($_GET['id']);

$sql = "SELECT * FROM recipes WHERE id = ?";
$stmt = $mysqli->prepare($sql);

if (!$stmt) {
    die("Ошибка подготовки запроса: " . $mysqli->error);
}

$stmt->bind_param("i", $recipeId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Рецепт с таким ID не найден.");
}

$recipe = $result->fetch_assoc();
$stmt->close();

$ingredientsSql = "SELECT ri.*, i.name, m.unit, m.id AS measurement_id
                   FROM recipe_ingredients ri
                   JOIN ingredients i ON ri.ingredient_id = i.id
                   JOIN measurements m ON ri.measurement_id = m.id
                   WHERE ri.recipe_id = ?";
$ingredientsStmt = $mysqli->prepare($ingredientsSql);
$ingredientsStmt->bind_param("i", $recipeId);
$ingredientsStmt->execute();
$ingredientsResult = $ingredientsStmt->get_result();
$ingredients = $ingredientsResult->fetch_all(MYSQLI_ASSOC);
$ingredientsStmt->close();

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $instructions = $_POST['instructions'];
    $country_id = intval($_POST['country_id']);
    $preparation_time = intval($_POST['preparation_time']);
    $category = $_POST['category'];
    $is_published = isset($_POST['is_published']) ? 1 : 0;
    $image_path = $recipe['image_path'];

    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../images/recipe/';
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];

        if (in_array($_FILES['image']['type'], $allowedTypes)) {
            $newFileName = uniqid('recipe_') . '-' . basename($_FILES['image']['name']);
            $targetPath = $uploadDir . $newFileName;

            if (move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
                $image_path = $targetPath;
            } else {
                echo "<div class='alert alert-danger'>Ошибка загрузки файла.</div>";
            }
        } else {
            echo "<div class='alert alert-danger'>Недопустимый тип файла. Используйте JPEG, PNG или GIF.</div>";
        }
    }

    $updateSql = "UPDATE recipes SET 
                    title = ?, 
                    description = ?, 
                    instructions = ?, 
                    country_id = ?, 
                    preparation_time = ?, 
                    category = ?, 
                    image_path = ?, 
                    is_published = ? 
                  WHERE id = ?";
    $updateStmt = $mysqli->prepare($updateSql);

    if (!$updateStmt) {
        die("Ошибка подготовки запроса: " . $mysqli->error);
    }

    $updateStmt->bind_param(
        "sssissssi",
        $title,
        $description,
        $instructions,
        $country_id,
        $preparation_time,
        $category,
        $image_path,
        $is_published,
        $recipeId
    );


    if ($updateStmt->execute()) {
        echo "<div class='alert alert-success text-center'>Рецепт успешно обновлен! Вы будете перенаправлены через 3 секунды...</div>";
        echo "<script>
            setTimeout(function() {
                window.location.href = 'recipeList.php';
            }, 3000);
        </script>";
        exit();
    } else {
        echo "<div class='alert alert-danger'>Ошибка обновления рецепта: " . $mysqli->error . "</div>";
    }

    if (isset($_POST['ingredients']) && isset($_POST['amounts']) && isset($_POST['units'])) {
        $ingredients = $_POST['ingredients'];
        $amounts = $_POST['amounts'];
        $units = $_POST['units'];

        $deleteIngredientsSql = "DELETE FROM recipe_ingredients WHERE recipe_id = ?";
        $deleteIngredientsStmt = $mysqli->prepare($deleteIngredientsSql);
        $deleteIngredientsStmt->bind_param("i", $recipeId);
        $deleteIngredientsStmt->execute();

        foreach ($ingredients as $index => $ingredient) {
            $amount = $amounts[$index];
            $unit = $units[$index];
            $ingredientIdSql = "SELECT id FROM ingredients WHERE name = ?";
            $ingredientStmt = $mysqli->prepare($ingredientIdSql);
            $ingredientStmt->bind_param("s", $ingredient);
            $ingredientStmt->execute();
            $ingredientResult = $ingredientStmt->get_result();
            $ingredientData = $ingredientResult->fetch_assoc();

            if (!$ingredientData) {
                $insertNewIngredientSql = "INSERT INTO ingredients (name) VALUES (?)";
                $insertNewIngredientStmt = $mysqli->prepare($insertNewIngredientSql);
                $insertNewIngredientStmt->bind_param("s", $ingredient);
                if ($insertNewIngredientStmt->execute()) {
                    $ingredientId = $insertNewIngredientStmt->insert_id;
                } else {
                    echo "<div class='alert alert-danger'>Ошибка добавления нового ингредиента: " . $mysqli->error . "</div>";
                    continue;
                }
                $insertNewIngredientStmt->close();
            } else {
                $ingredientId = $ingredientData['id'];
            }

            $insertIngredientSql = "INSERT INTO recipe_ingredients (recipe_id, ingredient_id, amount, measurement_id) 
                                     VALUES (?, ?, ?, ?)";
            $insertIngredientStmt = $mysqli->prepare($insertIngredientSql);
            $insertIngredientStmt->bind_param("iidi", $recipeId, $ingredientId, $amount, $unit);
            $insertIngredientStmt->execute();
        }
    }

    $updateStmt->close();
}

$countriesResult = $mysqli->query("SELECT id, name FROM countries");
$countries = $countriesResult->fetch_all(MYSQLI_ASSOC);

$measurementsResult = $mysqli->query("SELECT id, unit FROM measurements");
$measurements = $measurementsResult->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="ru">

<head>
<link rel="icon" type="image/png" href="/images/favicon.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Редактирование рецепта</title>
    <script src="https://cdn.tiny.cloud/1/lzb1aulm4iko1q7iot27s5uv8vmt1bhromrcvxmy7jox5y36/tinymce/7/tinymce.min.js" referrerpolicy="origin"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/cover.css" rel="stylesheet">
</head>

<body>
    <div class="d-flex h-100 text-center text-bg-dark">
        <div class="wrapper cover-container d-flex w-100 h-100 p-3 mx-auto flex-column">
            <?php include "../header.php"; ?>
        </div>
    </div>

    <body>
        <div class="container mt-5">
            <div class="card shadow-lg p-4">
                <h1 class="text-center mb-4">Редактирование рецепта: <?= htmlspecialchars($recipe['title']) ?></h1>
                <form method="post" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="form-label">Название*:</label>
                        <input type="text" class="form-control" name="title" value="<?= htmlspecialchars($recipe['title']) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Описание:</label>
                        <textarea class="form-control" name="description"><?= htmlspecialchars($recipe['description']) ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Инструкции:</label>
                        <textarea class="form-control" name="instructions" ><?= htmlspecialchars($recipe['instructions']) ?></textarea>
                        <script>
                            tinymce.init({
                                selector: 'textarea[name="instructions"]', 
                                plugins: [
                                    'anchor', 'autolink', 'charmap', 'codesample', 'emoticons', 'image', 'link', 'lists',
                                    'media', 'searchreplace', 'table', 'visualblocks', 'wordcount'
                                ],
                                toolbar: 'undo redo | blocks fontfamily fontsize | bold italic underline strikethrough | link image media table mergetags | addcomment showcomments | spellcheckdialog a11ycheck typography | align lineheight | checklist numlist bullist indent outdent | emoticons charmap | removeformat',
                                images_upload_url: 'postAcceptor.php',
                                automatic_uploads: true,
                            });
                        </script>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Кухня*:</label>
                        <select class="form-control" name="country_id" required>
                            <?php foreach ($countries as $country): ?>
                                <option value="<?= $country['id'] ?>" <?= $country['id'] == $recipe['country_id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($country['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Время приготовления (минуты)*:</label>
                        <input type="number" class="form-control" name="preparation_time" value="<?= $recipe['preparation_time'] ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Тип блюда*:</label>
                        <select class="form-control" name="category" required>
                            <option value="Закуски" <?= $recipe['category'] == 'Закуски' ? 'selected' : '' ?>>Закуски</option>
                            <option value="Основные блюда" <?= $recipe['category'] == 'Основные блюда' ? 'selected' : '' ?>>Основные блюда</option>
                            <option value="Десерты" <?= $recipe['category'] == 'Десерты' ? 'selected' : '' ?>>Десерты</option>
                            <option value="Напитки" <?= $recipe['category'] == 'Напитки' ? 'selected' : '' ?>>Напитки</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Изображение*:</label>
                        <input type="file" class="form-control" name="image" onchange="previewImage(event)">
                        <img id="imagePreview" src="<?= $recipe['image_path'] ?>" alt="Предпросмотр" style="max-width: 200px; margin-top: 10px;">
                    </div>

                    <h2 class="mt-4">Ингредиенты*:</h2>
                    <div id="ingredientsContainer">
                        <?php foreach ($ingredients as $ingredient): ?>
                            <div class="ingredient d-flex mb-3">
                                <input type="text" class="form-control me-2" name="ingredients[]" value="<?= htmlspecialchars($ingredient['name']) ?>" required>
                                <input type="number" class="form-control me-2" name="amounts[]" value="<?= $ingredient['amount'] ?>" required>
                                <select class="form-control me-2" name="units[]" required>
                                    <?php foreach ($measurements as $measurement): ?>
                                        <option value="<?= htmlspecialchars($measurement['id']) ?>" <?= isset($ingredient['measurement_id']) && $measurement['id'] == $ingredient['measurement_id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($measurement['unit']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="button" class="btn btn-danger btn-sm" onclick="removeIngredient(this)">Удалить</button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <button type="button" class="btn btn-primary" onclick="addIngredient()">Добавить ингредиент</button>

                    <div class="form-check mb-3">
                        <input type="checkbox" class="form-check-input" id="isPublished" name="is_published" value="1" <?= $recipe['is_published'] ? 'checked' : '' ?>>
                        <label class="form-check-label" for="isPublished">Опубликовать рецепт</label>
                    </div>

                    <button type="submit" class="btn btn-success mt-3">Сохранить изменения</button>
                </form>
            </div>
        </div>



        <script>
            function addIngredient() {
                const container = document.getElementById('ingredientsContainer');
                const ingredientHTML = `
        <div class="ingredient d-flex mb-3">
            <input type="text" class="form-control me-2" name="ingredients[]" required>
            <input type="number" class="form-control me-2" name="amounts[]" required>
            <select class="form-control me-2" name="units[]" required>
                <?php foreach ($measurements as $measurement): ?>
                    <option value="<?= htmlspecialchars($measurement['id']) ?>"><?= htmlspecialchars($measurement['unit']) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="button" class="btn btn-danger btn-sm" onclick="removeIngredient(this)">Удалить</button>
        </div>
    `;
                container.insertAdjacentHTML('beforeend', ingredientHTML);
            }


            function removeIngredient(button) {
                button.closest('.ingredient').remove();
            }

            function previewImage(event) {
                const reader = new FileReader();
                reader.onload = function() {
                    const preview = document.getElementById('imagePreview');
                    preview.src = reader.result;
                };
                reader.readAsDataURL(event.target.files[0]);
            }
        </script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    </body>

</html>