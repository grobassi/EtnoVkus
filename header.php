<header class="mb-auto">
    <div>
        <h3 class="float-md-start mb-0 text-white">
            <a href="/index.php" class="text-white text-decoration-none">
                <strong>ЭтноВкус</strong>
            </a>
        </h3>
        <nav class="nav nav-masthead justify-content-center float-md-end">
            <a class="nav-link fw-bold py-1 px-0 <?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>" aria-current="page" href="/index.php">Главная</a>

            <a class="nav-link fw-bold py-1 px-0 <?= basename($_SERVER['PHP_SELF']) == 'recipe.php' ? 'active' : ''; ?>" href="/recipe.php">Рецепты</a>

            <a class="nav-link fw-bold py-1 px-0 <?= basename($_SERVER['PHP_SELF']) == 'about.php' ? 'active' : ''; ?>" href="/about.php">О нас</a>

            <?php if (isset($_SESSION['user_id'])): ?>
                <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                    <a class="nav-link fw-bold py-1 px-0 <?= basename($_SERVER['PHP_SELF']) == 'adminPage.php' ? 'active' : ''; ?>" href="/admin/adminPage.php">Панель</a>
                <?php else: ?>
                    <a class="nav-link fw-bold py-1 px-0 <?= basename($_SERVER['PHP_SELF']) == 'userProfile.php' ? 'active' : ''; ?>" href="/userProfile.php">Профиль</a>
                <?php endif; ?>
                <a class="nav-link fw-bold py-1 px-0" href="/logout.php">Выйти</a>
            <?php else: ?>
                <a class="nav-link fw-bold py-1 px-0 <?= basename($_SERVER['PHP_SELF']) == 'login.php' ? 'active' : ''; ?>" href="/login.php">Вход</a>
            <?php endif; ?>
        </nav>
    </div>
</header>
