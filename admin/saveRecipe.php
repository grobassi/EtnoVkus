<?php
include 'database.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $instructions = $_POST['instructions'];
    $country_id = $_POST['country_id'];
    $ingredients = $_POST['ingredients'];
    $amounts = $_POST['amounts'];
    $units = $_POST['units'];

    $image_path = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploads_dir = '../images/recipe'; 

        $tmp_name = $_FILES['image']['tmp_name'];
        $name = basename($_FILES['image']['name']);
        $unique_name = uniqid('recipe_', true) . '_' . $original_name; 
        $image_path = "$uploads_dir/$unique_name";

        move_uploaded_file($tmp_name, $image_path);
    }

    $stmt = $mysqli->prepare("INSERT INTO recipes (title, description, instructions, country_id, image_path) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssis", $title, $description, $instructions, $country_id, $image_path);
    $stmt->execute();
    
    $recipe_id = $mysqli->insert_id;

    for ($i = 0; $i < count($ingredients); $i++) {
        $ingredient_name = $ingredients[$i];
        $amount = $amounts[$i];
        $measurement_id = $units[$i]; 

        $stmt = $mysqli->prepare("SELECT id FROM ingredients WHERE name = ?");
        $stmt->bind_param("s", $ingredient_name);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->bind_result($ingredient_id);
            $stmt->fetch();
        } else {
            $stmt = $mysqli->prepare("INSERT INTO ingredients (name) VALUES (?)");
            $stmt->bind_param("s", $ingredient_name);
            $stmt->execute();
            $ingredient_id = $mysqli->insert_id;
        }

        $stmt = $mysqli->prepare("INSERT INTO recipe_ingredients (recipe_id, ingredient_id, amount, measurement_id) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiis", $recipe_id, $ingredient_id, $amount, $measurement_id);
        $stmt->execute();
    }

  
    header("Location: succes.php");
    exit();
}
?>
