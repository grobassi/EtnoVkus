<?php
include 'database.php'; 
session_start(); 

function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] == 'admin';
}
if (!isAdmin()) {
    header("Location:index.php");
    exit();
}

$recipes_per_page = 5;

$current_page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($current_page - 1) * $recipes_per_page;

$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';

$sql = "
    SELECT r.id, r.title, r.description, c.name AS country_name, r.is_published
    FROM recipes r
    LEFT JOIN countries c ON r.country_id = c.id
";

if (!empty($search_query)) {
    $sql .= " WHERE r.title LIKE ?";
}

$sql .= " LIMIT ? OFFSET ?";

$stmt = $mysqli->prepare($sql);

if (!empty($search_query)) {
    $like_query = "%" . $search_query . "%";
    $stmt->bind_param("sii", $like_query, $recipes_per_page, $offset);
} else {
    $stmt->bind_param("ii", $recipes_per_page, $offset);
}

$stmt->execute();
$result = $stmt->get_result();

$count_sql = "SELECT COUNT(*) AS total FROM recipes";
if (!empty($search_query)) {
    $count_sql .= " WHERE title LIKE ?";
}
$count_stmt = $mysqli->prepare($count_sql);
if (!empty($search_query)) {
    $count_stmt->bind_param("s", $like_query);
}
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$total_row = $count_result->fetch_assoc();
$total_recipes = $total_row['total'];
$total_pages = ceil($total_recipes / $recipes_per_page);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
<link rel="icon" type="image/png" href="/images/favicon.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Список рецептов</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/cover.css" rel="stylesheet">

</head>
<body>
<div class="d-flex h-100 text-center text-bg-dark">
    <div class="wrapper cover-container d-flex w-100 h-100 p-3 mx-auto flex-column">
        <?php include "../header.php"; ?>
    </div>
</div>
<div class="container mt-5">
    <h1>Список рецептов</h1>
    
    <a href="addRecipe.php" class="btn btn-primary">Добавить рецепт</a>

    <form class="my-4" method="GET">
        <div class="input-group">
            <input type="text" name="search" class="form-control" placeholder="Введите название рецепта" value="<?= htmlspecialchars($search_query) ?>">
            <button type="submit" class="btn btn-secondary">Найти</button>
        </div>
    </form>

    <table class="table table-striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Название рецепта</th>
                <th>Описание</th>
                <th>Страна</th>
                <th>Опубликован</th>
                <th>Действия</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $row['id']; ?></td>
                    <td>
                        <a href="full_recipe.php?id=<?php echo $row['id']; ?>">
                            <?php echo htmlspecialchars($row['title']); ?>
                        </a>
                    </td>
                    <td><?php echo htmlspecialchars($row['description']); ?></td>
                    <td><?php echo htmlspecialchars($row['country_name']); ?></td>
                    <td>
                        <?php if ($row['is_published']): ?>
                            <span class="badge bg-success">Да</span>
                        <?php else: ?>
                            <span class="badge bg-danger">Нет</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <a href="editRecipe.php?id=<?php echo $row['id']; ?>" class="btn btn-primary btn-sm">Редактировать</a>
                        <form action="delete_recipe.php" method="POST" style="display:inline;">
                            <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                            <button type="submit" class="btn btn-danger btn-sm">Удалить</button>
                        </form>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <div class="d-flex justify-content-center mt-4">
        <ul class="pagination">
            <?php if ($current_page > 1): ?>
                <li class="page-item"><a class="page-link" href="?page=1&search=<?= urlencode($search_query) ?>">Первая</a></li>
                <li class="page-item"><a class="page-link" href="?page=<?= $current_page - 1 ?>&search=<?= urlencode($search_query) ?>">Назад</a></li>
            <?php endif; ?>

            <?php for ($page = 1; $page <= $total_pages; $page++): ?>
                <li class="page-item <?= $page == $current_page ? 'active' : '' ?>">
                    <a class="page-link" href="?page=<?= $page ?>&search=<?= urlencode($search_query) ?>"><?= $page ?></a>
                </li>
            <?php endfor; ?>

            <?php if ($current_page < $total_pages): ?>
                <li class="page-item"><a class="page-link" href="?page=<?= $current_page + 1 ?>&search=<?= urlencode($search_query) ?>">Вперед</a></li>
                <li class="page-item"><a class="page-link" href="?page=<?= $total_pages ?>&search=<?= urlencode($search_query) ?>">Последняя</a></li>
            <?php endif; ?>
        </ul>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
