<?php
// Получаем список таблиц
$tables = getTables($pdo);

// Названия таблиц для отображения
$tableNames = [
    'артисты' => 'Артисты',
    'альбомы' => 'Альбомы',
    'песни' => 'Песни',
    'жанры' => 'Жанры',
    'пользователи' => 'Пользователи'
    // ,
    // 'плейлисты' => 'Плейлисты',
    // 'треки_плейлистов' => 'Треки плейлистов'
];

// Иконки для таблиц
$tableIcons = [
    'артисты' => 'fa-microphone',
    'альбомы' => 'fa-compact-disc',
    'песни' => 'fa-music',
    'жанры' => 'fa-guitar',
    'пользователи' => 'fa-users'
    // ,
    // 'плейлисты' => 'fa-list-music',
    // 'треки_плейлистов' => 'fa-list'
];
?>

<aside class="admin-sidebar">
    <div class="sidebar-header">
        <div class="logo">SoundWave</div>
        <div class="admin-badge">
            <i class="fas fa-shield-alt"></i> Admin
        </div>
    </div>
    
    <div class="admin-info">
        <div class="admin-avatar">
            <i class="fa-solid fa-crown"></i>
        </div>
        <div class="admin-details">
            <span class="admin-name"><?php echo htmlspecialchars($_SESSION['admin_username']); ?></span>
            <span class="admin-role">Главный администратор</span>
        </div>
    </div>
    
    <nav class="sidebar-nav">
        <div class="nav-section">
            <div class="nav-section-title">
                <i class="fas fa-database"></i>
                <span>Управление БД</span>
            </div>
            <ul class="nav-menu">
                <?php foreach ($tables as $table): ?>
                    <?php if (isset($tableNames[$table])): ?>
                        <li class="nav-item <?php echo (isset($_GET['table']) && $_GET['table'] === $table) ? 'active' : ''; ?>">
                            <a href="index.php?table=<?php echo urlencode($table); ?>">
                                <i class="fas <?php echo $tableIcons[$table] ?? 'fa-table'; ?>"></i>
                                <span><?php echo $tableNames[$table]; ?></span>
                                <?php
                                // Получаем количество записей
                                $countStmt = $pdo->query("SELECT COUNT(*) FROM `$table`");
                                $count = $countStmt->fetchColumn();
                                ?>
                                <span class="item-badge"><?php echo $count; ?></span>
                            </a>
                        </li>
                    <?php endif; ?>
                <?php endforeach; ?>
            </ul>
        </div>
        
        <div class="nav-section">
            <ul class="nav-menu">
                <li class="nav-item">
            <a href="reports.php">
                <i class="fas fa-chart-line"></i>
                <span>Отчёты</span>
            </a>
        </li>
                <li class="nav-item">
                    <a href="../index.php" target="_blank">
                        <i class="fas fa-external-link-alt"></i>
                        <span>Перейти на сайт</span>
                    </a>
                </li>
                <li class="nav-item logout">
                    <a href="logout.php">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Выйти</span>
                    </a>
                </li>
            </ul>
        </div>
    </nav>
    
    <div class="sidebar-footer">
        <div class="system-info">
            <i class="fas fa-clock"></i>
            <span><?php echo date('d.m.Y H:i'); ?></span>
        </div>
    </div>
</aside>

<main class="admin-main">