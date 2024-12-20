<?php
 session_start();
?>
<!doctype html>
<html lang="ru" class="h-100">
<link rel="icon" type="image/png" href="/images/favicon.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Все рецепты</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/cover.css" rel="stylesheet">
    <style>
        .card-img-top {
            height: 200px; 
            object-fit: cover; 
        }
        .card {
            height: 100%; 
        }
        body {
            background-color: #343a40; 
            color: white;
        }

        .lead {
            margin-bottom: 20px;
        }

        .vertical-photo {
            max-height: 600px; 
            width: auto; 
            border-radius: 15px;
            margin-top: 20px; 
        }

        .btn-custom {
            color: black; 
            background-color: white; 
        }
    </style>
</head>

<body>
<div class="d-flex h-100 text-center text-bg-dark">
        <div class="wrapper cover-container d-flex w-100 h-100 p-3 mx-auto flex-column">
        <?php include "header.php" ?>
        </div>
    </div>
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h1 class="display-4">О нас</h1>
                <p class="lead">Добро пожаловать в наш мир кулинарных традиций! Мы создали этот сайт, чтобы делиться с вами рецептами национальных блюд со всех уголков света. Наша миссия — вдохновлять на кулинарные путешествия, не покидая домашней кухни, и помогать вам открывать культуру разных стран через вкус и ароматы их блюд.</p>
                <p class="lead">Мы тщательно отбираем рецепты, чтобы каждый из них мог передать подлинный характер кухни — будь то богатство индийских специй, уют шведской выпечки или изысканность французских десертов. Наши пошаговые инструкции и фотографии помогут вам легко освоить новые техники и ингредиенты, чтобы даже самые сложные блюда стали доступными.</p>
                <p class="lead">
                    <a href="recipe.php" class="btn btn-lg btn-custom fw-bold">К рецептам</a>
                </p>
            </div>
            <div class="col-md-6 text-center">
                <img src="images/about/Sofa.jpg" alt="София Аббаси" class="img-fluid vertical-photo">
            </div>
        </div>
    </div>

    <footer class="text-center mt-4">
        <p>Создано командой <strong>ЭтноВкус</strong>. <br> Связь: <a href="mailto:sonyamay74@gmail.com" class="text-white">sonyamay74@gmail.com</a>.</p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
