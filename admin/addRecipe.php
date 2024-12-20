<?php
include 'database.php';
session_start();
function isAdmin()
{
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] == 'admin';
}
if (!isAdmin()) {
    header("Location:index.php");
    exit();
}
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $content = $_POST['content'];
    $instructions = $_POST['instructions'];
    $country_id = $_POST['country_id'];
    $preparation_time = $_POST['preparation_time'];
    $category = $_POST['category'];
    $is_published = isset($_POST['is_published']) ? 1 : 0;
    $image_path = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $image_path = '../images/recipe/' . uniqid('recipe_') . '.' . pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        move_uploaded_file($_FILES['image']['tmp_name'], $image_path);
    }
    $stmt = $mysqli->prepare("
        INSERT INTO recipes (title, description, instructions, country_id, preparation_time, category, image_path, is_published)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("sssisssi", $title, $description, $content, $country_id, $preparation_time, $category, $image_path, $is_published);
    $stmt->execute();
    $recipe_id = $stmt->insert_id;
    if (isset($_POST['ingredients']) && isset($_POST['amounts']) && isset($_POST['units'])) {
        $ingredients = $_POST['ingredients'];
        $amounts = $_POST['amounts'];
        $units = $_POST['units'];
        $stmt = $mysqli->prepare("INSERT INTO recipe_ingredients (recipe_id, ingredient_id, amount, measurement_id) VALUES (?, ?, ?, ?)");
        foreach ($ingredients as $index => $ingredient_name) {
            $ingredient_stmt = $mysqli->prepare("SELECT id FROM ingredients WHERE name = ?");
            $ingredient_stmt->bind_param("s", $ingredient_name);
            $ingredient_stmt->execute();
            $ingredient_result = $ingredient_stmt->get_result();
            if ($ingredient_result->num_rows > 0) {
                $row = $ingredient_result->fetch_assoc();
                $ingredient_id = $row['id'];
            } else {
                $insert_ingredient_stmt = $mysqli->prepare("INSERT INTO ingredients (name) VALUES (?)");
                $insert_ingredient_stmt->bind_param("s", $ingredient_name);
                $insert_ingredient_stmt->execute();
                $ingredient_id = $insert_ingredient_stmt->insert_id;
            }
            $amount = $amounts[$index];
            $unit_id = $units[$index];
            $stmt->bind_param("iidi", $recipe_id, $ingredient_id, $amount, $unit_id);
            $stmt->execute();
        }
    }
    header("Location: succes.php");
    exit();
}
$countries = [];
$result = $mysqli->query("SELECT id, name FROM countries");
while ($row = $result->fetch_assoc()) {
    $countries[] = $row;
}
$measurements = [];
$result = $mysqli->query("SELECT id, unit FROM measurements");
while ($row = $result->fetch_assoc()) {
    $measurements[] = $row;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
<link rel="icon" type="image/png" href="/images/favicon.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tiny.cloud/1/lzb1aulm4iko1q7iot27s5uv8vmt1bhromrcvxmy7jox5y36/tinymce/7/tinymce.min.js" referrerpolicy="origin"></script>
    <title>Добавить рецепт</title>
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
            <h1>Добавить рецепт</h1>
            <form id="recipeForm" method="post" enctype="multipart/form-data">
                <div class="mb-3">
                    <label for="title" class="form-label">Название рецепта*:</label>
                    <input type="text" class="form-control" id="title" name="title" required>
                </div>
                <div class="mb-3">
                    <label for="description" class="form-label">Описание:</label>
                    <input class="form-control" id="description" name="description" rows="3" ></input>
                </div>
                <div class="mb-3">
                    <label for="instructions" class="form-label">Инструкции по приготовлению:</label>
                    <script>
                        tinymce.init({
                            selector: 'textarea[name="content"]', // Обратите внимание на селектор
                            plugins: [
                                'anchor', 'autolink', 'charmap', 'codesample', 'emoticons', 'image', 'link', 'lists',
                                'media', 'searchreplace', 'table', 'visualblocks', 'wordcount'
                            ],
                            toolbar: 'undo redo | blocks fontfamily fontsize | bold italic underline strikethrough | link image media table mergetags | addcomment showcomments | spellcheckdialog a11ycheck typography | align lineheight | checklist numlist bullist indent outdent | emoticons charmap | removeformat',
                            images_upload_url: 'postAcceptor.php',
                            automatic_uploads: true,
                        });
                    </script>
                    <textarea id="content" name="content"></textarea>
                </div>
                <div class="form-group mb-3">
                    <label for="country">Кухня*:</label>
                    <select class="form-control" id="country" name="country_id" required>
                        <?php foreach ($countries as $country): ?>
                            <option value="<?= $country['id'] ?>"><?= $country['name'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="image" class="form-label">Загрузить изображение*:</label>
                    <input type="file" class="form-control" id="image" name="image" accept="image/*" required>
                </div>
                <div class="mb-3">
                    <label for="preparation_time" class="form-label">Время приготовления (минуты)*:</label>
                    <input type="number" class="form-control" id="preparation_time" name="preparation_time" required>
                </div>
                <div class="mb-3">
                    <label for="category" class="form-label">Тип блюда*:</label>
                    <select class="form-select" id="category" name="category" required>
                        <option value="Закуски">Закуски</option>
                        <option value="Основные блюда">Основные блюда</option>
                        <option value="Десерты">Десерты</option>
                        <option value="Напитки">Напитки</option>
                    </select>
                </div>
                <h3>Ингредиенты*:</h3>
                <div id="ingredients-list">
                    <div class="ingredient-row">
                        <div class="row m-3">
                            <div class="col-md-4">
                                <input type="text" name="ingredients[]" class="form-control" placeholder="Ингредиент" required>
                            </div>
                            <div class="col-md-3">
                                <input type="number" name="amounts[]" class="form-control" placeholder="Количество" required>
                            </div>
                            <div class="col-md-3">
                                <select name="units[]" class="form-control" required>
                                    <?php foreach ($measurements as $measurement): ?>
                                        <option value="<?= $measurement['id'] ?>"><?= $measurement['unit'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <button type="button" class="btn btn-danger remove-ingredient">Удалить</button>
                            </div>
                        </div>
                    </div>
                </div>
                <button type="button" class="btn btn-success m-3" id="addIngredient">Добавить ингредиент</button>
                <div class="form-check mb-3">
                    <input type="checkbox" class="form-check-input" id="isPublished" name="is_published" value="1">
                    <label for="isPublished" class="form-check-label">Опубликовать рецепт</label>
                </div>
                <button type="submit" class="btn btn-primary mx-3 mb-3">Сохранить рецепт</button>
            </form>
        </div>
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script>
            $(document).ready(function() {
                $('#addIngredient').click(function() {
                    var newIngredient = `
            <div class="ingredient-row">
                <div class="row m-3">
                    <div class="col-md-4">
                        <input type="text" name="ingredients[]" class="form-control" placeholder="Ингредиент" required>
                    </div>
                    <div class="col-md-3">
                        <input type="number" name="amounts[]" class="form-control" placeholder="Количество" required>
                    </div>
                    <div class="col-md-3">
                        <select name="units[]" class="form-control" required>
                            <?php foreach ($measurements as $measurement): ?>
                                <option value="<?= $measurement['id'] ?>"><?= $measurement['unit'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="button" class="btn btn-danger remove-ingredient">Удалить</button>
                    </div>
                </div>
            </div>`;
                    $('#ingredients-list').append(newIngredient);
                });
                $(document).on('click', '.remove-ingredient', function() {
                    $(this).closest('.ingredient-row').remove();
                });
            });
        </script>
    </body>
</html>