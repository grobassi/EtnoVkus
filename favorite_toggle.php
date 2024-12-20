<?php
include 'admin/database.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $recipe_id = $input['recipe_id'] ?? null;

    if (isset($_SESSION['user_id']) && $recipe_id) {
        $user_id = $_SESSION['user_id'];

        $stmt = $mysqli->prepare("SELECT id FROM favorites WHERE user_id = ? AND recipe_id = ?");
        $stmt->bind_param("ii", $user_id, $recipe_id);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows === 0) {
            $stmt = $mysqli->prepare("INSERT INTO favorites (user_id, recipe_id) VALUES (?, ?)");
            $stmt->bind_param("ii", $user_id, $recipe_id);
            $stmt->execute();
            echo json_encode(['success' => true, 'action' => 'added']);
        } else {
            $stmt = $mysqli->prepare("DELETE FROM favorites WHERE user_id = ? AND recipe_id = ?");
            $stmt->bind_param("ii", $user_id, $recipe_id);
            $stmt->execute();
            echo json_encode(['success' => true, 'action' => 'removed']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Авторизуйтесь для изменения избранного.']);
    }
}
?>
