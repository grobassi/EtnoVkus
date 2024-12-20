<?php
session_start();
include 'admin/database.php';

$recipes_per_page = 6;

$current_page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($current_page - 1) * $recipes_per_page;

$countries = [];
$result = $mysqli->query("SELECT * FROM countries");
while ($row = $result->fetch_assoc()) {
    $countries[] = $row;
}

$where_clauses = ["r.is_published = 1"]; 

if (!empty($_GET['title'])) {
    $title = "%" . $mysqli->real_escape_string($_GET['title']) . "%";
    $where_clauses[] = "r.title LIKE '$title'";
}
if (!empty($_GET['country_id'])) {
    $country_id = intval($_GET['country_id']);
    $where_clauses[] = "r.country_id = $country_id";
}
if (!empty($_GET['ingredients'])) {
    $ingredients = explode(',', $_GET['ingredients']);
    $ingredients = array_map('trim', $ingredients);
    $ingredients = array_map([$mysqli, 'real_escape_string'], $ingredients);
    $ingredients_filter = "'" . implode("','", $ingredients) . "'";
    $ingredients_count = count($ingredients);
    $where_clauses[] = "
        r.id IN (
            SELECT ri.recipe_id
            FROM recipe_ingredients ri
            INNER JOIN ingredients i ON ri.ingredient_id = i.id
            WHERE i.name IN ($ingredients_filter)
            GROUP BY ri.recipe_id
            HAVING COUNT(DISTINCT i.id) = $ingredients_count
        )
    ";
}
if (!empty($_GET['category'])) {
    $category = $mysqli->real_escape_string($_GET['category']);
    $where_clauses[] = "r.category = '$category'";
}

if (!empty($_GET['preparation_time'])) {
    $prep_time = intval($_GET['preparation_time']);
    $where_clauses[] = "r.preparation_time <= $prep_time";
}
$where_sql = implode(' AND ', $where_clauses);

$query = "
    SELECT r.id, r.title, r.description, r.image_path, c.name AS country_name,
           (SELECT COUNT(*) FROM favorites f WHERE f.recipe_id = r.id) AS rating
    FROM recipes r
    JOIN countries c ON r.country_id = c.id
    LEFT JOIN recipe_ingredients ri ON r.id = ri.recipe_id
    LEFT JOIN ingredients i ON ri.ingredient_id = i.id
    WHERE $where_sql
    GROUP BY r.id
";
if (!empty($_GET['sort_by'])) {
    if ($_GET['sort_by'] == 'rating') {
        $query .= " ORDER BY rating DESC";
    } elseif ($_GET['sort_by'] == 'time') {
        $query .= " ORDER BY r.created_at DESC";
    }
} else {
    $query .= " ORDER BY r.id DESC"; 
}

$query .= " LIMIT $recipes_per_page OFFSET $offset";

$result = $mysqli->query($query);
$recipes = [];
while ($row = $result->fetch_assoc()) {
    $recipes[] = $row;
}

$total_query = "SELECT COUNT(DISTINCT r.id) AS total FROM recipes r 
                LEFT JOIN recipe_ingredients ri ON r.id = ri.recipe_id
                LEFT JOIN ingredients i ON ri.ingredient_id = i.id
                WHERE $where_sql";
$total_result = $mysqli->query($total_query);
$total_row = $total_result->fetch_assoc();
$total_recipes = $total_row['total'];
$total_pages = ceil($total_recipes / $recipes_per_page);
?>
<!DOCTYPE html>
<html lang="ru">

<head>
    <link rel="icon" type="image/png" href="/images/favicon.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Все рецепты</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/cover.css" rel="stylesheet">
    <style>
        .card-img-top {
            height: 200px;
            object-fit: cover;
        }

        .card {
            height: 100%;
        }

        .heart-icon {
            width: 16px;
            height: 16px;
            vertical-align: middle;
            margin-right: 5px;
        }
    </style>
</head>

<body>
    <div class="d-flex h-100 text-center text-bg-dark">
        <div class="wrapper cover-container d-flex w-100 h-100 p-3 mx-auto flex-column">
            <?php include "header.php" ?>
        </div>
    </div>
    <div class="container mt-5">
        <h1 class="text-center mb-4">Все рецепты</h1>

        <form method="GET" class="mb-4">
            <div class="row g-3">
                <div class="col-md-4">
                    <input type="text" name="title" class="form-control" placeholder="Название рецепта" value="<?= htmlspecialchars($_GET['title'] ?? '') ?>">
                </div>

                <div class="col-md-4">
                    <select name="country_id" class="form-select">
                        <option value="">Любая кухня</option>
                        <?php foreach ($countries as $country): ?>
                            <option value="<?= $country['id'] ?>" <?= isset($_GET['country_id']) && $_GET['country_id'] == $country['id'] ? 'selected' : '' ?>>
                                <?= $country['name'] ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-4">
                    <input type="text" name="ingredients" class="form-control" placeholder="Ингредиенты (через запятую)" value="<?= htmlspecialchars($_GET['ingredients'] ?? '') ?>">
                </div>


                <div class="col-md-4">
                    <select name="category" class="form-select">
                        <option value="">Тип блюда</option>
                        <option value="Закуски" <?= isset($_GET['category']) && $_GET['category'] == 'Закуски' ? 'selected' : '' ?>>Закуски</option>
                        <option value="Основные блюда" <?= isset($_GET['category']) && $_GET['category'] == 'Основные блюда' ? 'selected' : '' ?>>Основные блюда</option>
                        <option value="Десерты" <?= isset($_GET['category']) && $_GET['category'] == 'Десерты' ? 'selected' : '' ?>>Десерты</option>
                        <option value="Напитки" <?= isset($_GET['category']) && $_GET['category'] == 'Напитки' ? 'selected' : '' ?>>Напитки</option>
                    </select>
                </div>

                <div class="col-md-4">
                    <select name="preparation_time" class="form-select">
                        <option value="">Время приготовления</option>
                        <option value="30" <?= isset($_GET['preparation_time']) && $_GET['preparation_time'] == '30' ? 'selected' : '' ?>>До 30 минут</option>
                        <option value="60" <?= isset($_GET['preparation_time']) && $_GET['preparation_time'] == '60' ? 'selected' : '' ?>>До 60 минут</option>
                        <option value="120" <?= isset($_GET['preparation_time']) && $_GET['preparation_time'] == '120' ? 'selected' : '' ?>>До 120 минут</option>
                    </select>
                </div>

                <div class="col-md-4">
                    <select name="sort_by" class="form-select">
                        <option value="">Сортировка</option>
                        <option value="rating" <?= isset($_GET['sort_by']) && $_GET['sort_by'] == 'rating' ? 'selected' : '' ?>>По рейтингу</option>
                        <option value="time" <?= isset($_GET['sort_by']) && $_GET['sort_by'] == 'time' ? 'selected' : '' ?>>По времени добавления</option>
                    </select>
                </div>
            </div>

            <div class="mt-3">
                <button type="submit" class="btn btn-primary">Искать</button>
                <a href="recipe.php" class="btn btn-secondary">Сбросить</a>
            </div>
        </form>

        <div class="row">
            <?php if (!empty($recipes)): ?>
                <?php foreach ($recipes as $recipe): ?>
                    <div class="col-md-4 mb-4">
                        <div class="card">
                            <img src="<?= $recipe['image_path'] ?>" class="card-img-top" alt="<?= htmlspecialchars($recipe['title']) ?>">
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title"><?= htmlspecialchars($recipe['title']) ?></h5>
                                <p class="card-text"><?= mb_strimwidth($recipe['description'], 0, 100, '...') ?></p>
                                <p class="card-text"><small class="text-muted"><?= htmlspecialchars($recipe['country_name']) ?></small></p>
                                <p class="card-text">
                                    <img src="images/heart.png" alt="heart" class="heart-icon">
                                    <span class="text-muted"><?= htmlspecialchars($recipe['rating']) ?></span>
                                </p>
                                <div class="mt-auto">
                                    <a href="fullRecipe.php?id=<?= $recipe['id'] ?>" class="btn btn-primary">Открыть рецепт</a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="text-center">Рецепты не найдены.</p>
            <?php endif; ?>
        </div>

        <nav>
            <ul class="pagination justify-content-center">
                <?php if ($current_page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => 1])) ?>">Первая</a>
                    </li>
                    <li class="page-item">
                        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $current_page - 1])) ?>">Назад</a>
                    </li>
                <?php endif; ?>

                <?php for ($page = 1; $page <= $total_pages; $page++): ?>
                    <li class="page-item <?= $page == $current_page ? 'active' : '' ?>">
                        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page])) ?>"><?= $page ?></a>
                    </li>
                <?php endfor; ?>

                <?php if ($current_page < $total_pages): ?>
                    <li class="page-item">
                        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $current_page + 1])) ?>">Вперед</a>
                    </li>
                    <li class="page-item">
                        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $total_pages])) ?>">Последняя</a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>

    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>