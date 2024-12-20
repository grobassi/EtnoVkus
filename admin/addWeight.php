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

$measurements_per_page = 5;

$current_page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($current_page - 1) * $measurements_per_page;

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['new_measurement'])) {
    $new_measurement = $_POST['new_measurement'];
    $stmt = $mysqli->prepare("INSERT INTO measurements (unit) VALUES (?)");
    $stmt->bind_param("s", $new_measurement);
    $stmt->execute();
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['delete_measurement'])) {
    $measurement_id = $_POST['measurement_id'];
    $stmt = $mysqli->prepare("DELETE FROM measurements WHERE id = ?");
    $stmt->bind_param("i", $measurement_id);
    $stmt->execute();
}

$measurements = [];
$result = $mysqli->query("
    SELECT id, unit 
    FROM measurements 
    LIMIT $measurements_per_page OFFSET $offset
");
while ($row = $result->fetch_assoc()) {
    $measurements[] = $row;
}

$total_result = $mysqli->query("SELECT COUNT(*) AS total FROM measurements");
$total_row = $total_result->fetch_assoc();
$total_measurements = $total_row['total'];
$total_pages = ceil($total_measurements / $measurements_per_page);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    
<link rel="icon" type="image/png" href="/images/favicon.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Список граммовок</title>
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
    <h1 class="text-center mb-4">Список граммовок</h1>
    
    <div class="card mb-4">
        <div class="card-body">
            <h3 class="card-title">Добавить новую граммовку</h3>
            <form method="post">
                <div class="mb-3">
                    <input type="text" name="new_measurement" class="form-control" placeholder="Название граммовки" required>
                </div>
                <button type="submit" class="btn btn-primary">Добавить</button>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <h3 class="card-title">Управление граммовками</h3>
            <?php if (!empty($measurements)): ?>
                <ul class="list-group">
                    <?php foreach ($measurements as $measurement): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <?= htmlspecialchars($measurement['unit']) ?>
                            <form method="post" class="m-0">
                                <input type="hidden" name="measurement_id" value="<?= $measurement['id'] ?>">
                                <button type="submit" name="delete_measurement" class="btn btn-danger btn-sm">Удалить</button>
                            </form>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p class="text-center">Список пуст.</p>
            <?php endif; ?>
        </div>
    </div>

    <div class="d-flex justify-content-center mt-4">
        <ul class="pagination">
            <?php if ($current_page > 1): ?>
                <li class="page-item">
                    <a class="page-link" href="?page=1">Первая</a>
                </li>
                <li class="page-item">
                    <a class="page-link" href="?page=<?= $current_page - 1 ?>">Назад</a>
                </li>
            <?php endif; ?>

            <?php for ($page = 1; $page <= $total_pages; $page++): ?>
                <li class="page-item <?= $page == $current_page ? 'active' : '' ?>">
                    <a class="page-link" href="?page=<?= $page ?>"><?= $page ?></a>
                </li>
            <?php endfor; ?>

            <?php if ($current_page < $total_pages): ?>
                <li class="page-item">
                    <a class="page-link" href="?page=<?= $current_page + 1 ?>">Вперед</a>
                </li>
                <li class="page-item">
                    <a class="page-link" href="?page=<?= $total_pages ?>">Последняя</a>
                </li>
            <?php endif; ?>
        </ul>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
