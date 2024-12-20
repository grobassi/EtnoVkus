<?php
include 'database.php'; 

if (isset($_POST['id'])) {
    $recipe_id = (int)$_POST['id'];

    $stmt = $mysqli->prepare("DELETE FROM recipe_ingredients WHERE recipe_id = ?");
    $stmt->bind_param("i", $recipe_id);
    $stmt->execute();

    $stmt = $mysqli->prepare("DELETE FROM recipes WHERE id = ?");
    $stmt->bind_param("i", $recipe_id);

    if ($stmt->execute()) {
        header("Location: recipeList.php"); 
        exit();
    } else {
        echo "Ошибка при удалении рецепта: " . $stmt->error;
    }
} else {
    echo "ID рецепта не передан.";
}
?>
