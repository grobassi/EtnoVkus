<?php

if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
    
 
    $tmp_name = $_FILES['file']['tmp_name'];
    $name = basename($_FILES['file']['name']); 
    
   
    $uploadDir = '../images/recipe/'; 
    $uniqueName = uniqid('img_', true) . '-' . $name; 
    $uploadPath = $uploadDir . $uniqueName; 

    $mimeType = mime_content_type($tmp_name);
    if (strpos($mimeType, 'image') === false) {
        echo json_encode(['error' => 'Файл не является изображением']);
        exit;
    }

    $maxFileSize = 5 * 1024 * 1024; 
    if ($_FILES['file']['size'] > $maxFileSize) {
        echo json_encode(['error' => 'Файл слишком большой']);
        exit;
    }

    if (move_uploaded_file($tmp_name, $uploadPath)) {
        echo json_encode(['location' => '/' . $uploadPath]);
    } else {
        echo json_encode(['error' => 'Ошибка при загрузке файла']);
    }
} else {
    echo json_encode(['error' => 'Ошибка при загрузке изображения']);
}
?>