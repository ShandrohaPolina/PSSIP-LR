<?php
session_start();

// Если есть параметр выхода, принудительно очищаем сессию
if (isset($_GET['logout'])) {
    $_SESSION = array();
    session_destroy();
    // Перенаправляем без параметра, чтобы убрать его из URL
    header('Location: index.php');
    exit;
}

// Подключение к базе данных
$servername = "localhost";
$username = "root";
$password = "yaustala17";
$dbname = "soundwave";

// Создаем соединение
$conn = new mysqli($servername, $username, $password, $dbname);

// Проверяем соединение
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Устанавливаем кодировку
$conn->set_charset("utf8mb4");

// Получаем общее количество артистов
$artistsQuery = "SELECT COUNT(*) as total FROM артисты";
$artistsResult = $conn->query($artistsQuery);
$totalArtists = $artistsResult->fetch_assoc()['total'];

// Получаем общее количество альбомов
$albumsQuery = "SELECT COUNT(*) as total FROM альбомы";
$albumsResult = $conn->query($albumsQuery);
$totalAlbums = $albumsResult->fetch_assoc()['total'];

// Получаем общее количество песен
$songsQuery = "SELECT COUNT(*) as total FROM песни";
$songsResult = $conn->query($songsQuery);
$totalSongs = $songsResult->fetch_assoc()['total'];

// Получаем общее количество пользователей
$usersQuery = "SELECT COUNT(*) as total FROM пользователи";
$usersResult = $conn->query($usersQuery);
$totalUsers = $usersResult->fetch_assoc()['total'];

// Получаем список всех жанров для карусели
$genresQuery = "SELECT * FROM жанры ORDER BY название";
$genresResult = $conn->query($genresQuery);
$genres = [];
if ($genresResult->num_rows > 0) {
    while($row = $genresResult->fetch_assoc()) {
        $genres[] = $row;
    }
}

// Получаем популярные альбомы для секции "СЕЙЧАС В ТРЕНДЕ"
$trendingQuery = "SELECT a.*, арт.имя as artist_name 
                  FROM альбомы a
                  LEFT JOIN артисты арт ON a.id_артиста = арт.id_артиста
                  ORDER BY RAND()
                  LIMIT 6";
$trendingResult = $conn->query($trendingQuery);
$trendingAlbums = [];
if ($trendingResult->num_rows > 0) {
    while($row = $trendingResult->fetch_assoc()) {
        // Форматируем обложку
        if (empty($row['обложка'])) {
            $row['обложка'] = 'images/default-album.jpg';
        } else {
            if (strpos($row['обложка'], '/') !== 0 && strpos($row['обложка'], 'http') !== 0) {
                $row['обложка'] = 'images/' . $row['обложка'];
            }
        }
        $trendingAlbums[] = $row;
    }
}

// Получаем последние 5 песен для чартов
$chartsQuery = "SELECT п.*, арт.имя as artist_name, альб.название as album_name
                FROM песни п
                LEFT JOIN артисты арт ON п.id_артиста = арт.id_артиста
                LEFT JOIN альбомы альб ON п.id_альбома = альб.id_альбома
                ORDER BY п.id_песни DESC 
                LIMIT 5";
$chartsResult = $conn->query($chartsQuery);
$chartsSongs = [];
if ($chartsResult->num_rows > 0) {
    while($row = $chartsResult->fetch_assoc()) {
        // Форматируем длительность
        if (!empty($row['длительность'])) {
            $timeParts = explode(':', $row['длительность']);
            if (count($timeParts) >= 2) {
                $minutes = ltrim($timeParts[0], '0') ?: '0';
                $minutes = intval($minutes);
                $seconds = $timeParts[1];
                $row['duration_formatted'] = $minutes . ':' . $seconds;
            } else {
                $row['duration_formatted'] = $row['длительность'];
            }
        } else {
            $row['duration_formatted'] = '--:--';
        }
        $chartsSongs[] = $row;
    }
}

// Получаем последние 4 альбома для слайдера новых релизов
$newReleasesQuery = "SELECT a.*, арт.имя as artist_name 
                     FROM альбомы a
                     LEFT JOIN артисты арт ON a.id_артиста = арт.id_артиста
                     ORDER BY a.дата_релиза DESC 
                     LIMIT 4";
$newReleasesResult = $conn->query($newReleasesQuery);
$newReleases = [];
if ($newReleasesResult->num_rows > 0) {
    while($row = $newReleasesResult->fetch_assoc()) {
        // Форматируем дату
        if (!empty($row['дата_релиза'])) {
            $date = new DateTime($row['дата_релиза']);
            $row['year'] = $date->format('Y');
            $row['date_formatted'] = $date->format('d.m.Y');
        } else {
            $row['year'] = 'Неизвестно';
            $row['date_formatted'] = 'Неизвестно';
        }
        
        // Форматируем обложку
        if (empty($row['обложка'])) {
            $row['обложка'] = 'images/default-album.jpg';
        } else {
            if (strpos($row['обложка'], '/') !== 0 && strpos($row['обложка'], 'http') !== 0) {
                $row['обложка'] = 'images/' . $row['обложка'];
            }
        }
        
        // Получаем количество треков в альбоме
        $tracksCountQuery = "SELECT COUNT(*) as count FROM песни WHERE id_альбома = " . $row['id_альбома'];
        $tracksCountResult = $conn->query($tracksCountQuery);
        $tracksCount = $tracksCountResult->fetch_assoc()['count'];
        $row['tracks_count'] = $tracksCount;
        
        $newReleases[] = $row;
    }
}

// Иконки для жанров
$genreIcons = [
    'fa-guitar', 'fa-microphone-alt', 'fa-headphones', 'fa-sliders-h',
    'fa-saxophone', 'fa-music', 'fa-record-vinyl', 'fa-volume-up',
    'fa-drum', 'fa-piano', 'fa-radio', 'fa-head-side-headphones'
];

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SoundWave</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&family=Orbitron:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/particles.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        /* Стили для профиля в шапке */
        .user-profile-menu {
            position: relative;
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 5px 12px 5px 5px;
            background: rgba(147, 51, 234, 0.1);
            border-radius: 40px;
            cursor: pointer;
            transition: all 0.3s ease;
            border: 1px solid rgba(147, 51, 234, 0.2);
        }
        
        .user-profile-menu:hover {
            background: rgba(147, 51, 234, 0.2);
            border-color: #9333ea;
        }
        
        .user-avatar-small {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            overflow: hidden;
            border: 2px solid #fbbf24;
        }
        
        .user-avatar-small img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .user-name {
            color: white;
            font-weight: 600;
            font-size: 0.9rem;
            max-width: 100px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        .user-profile-menu i {
            color: #fbbf24;
            font-size: 0.8rem;
        }
        
        /* Выпадающее меню пользователя */
        .user-dropdown {
            position: absolute;
            top: 120%;
            right: 0;
            width: 220px;
            background: rgba(20, 20, 30, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(147, 51, 234, 0.3);
            border-radius: 15px;
            padding: 8px 0;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.3s ease;
            z-index: 1001;
        }
        
        .user-profile-menu:hover .user-dropdown {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }
        
        .user-dropdown a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 20px;
            color: #e0e0e0;
            text-decoration: none;
            transition: all 0.3s ease;
            font-size: 0.95rem;
        }
        
        .user-dropdown a:hover {
            background: rgba(147, 51, 234, 0.2);
            color: #fbbf24;
        }
        
        .user-dropdown a i {
            width: 20px;
            color: #fbbf24;
        }
        
        .dropdown-divider {
            height: 1px;
            background: rgba(147, 51, 234, 0.3);
            margin: 8px 0;
        }
        
        @media (max-width: 768px) {
            .user-name {
                display: none;
            }
            
            .user-profile-menu {
                padding: 5px;
            }
        }
    </style>
</head>
<body>
    <!-- Плавающие музыкальные ноты -->
    <div id="floatingNotes"></div>

    <!-- Закрепленная шапка -->
    <header class="sticky-header" id="header">
        <a href="index.php" class="logo">SoundWave</a>
        <ul class="nav-menu">
            <li><a href="index.php" class="active">Главная</a></li>
            <li><a href="artists.php">Артисты</a></li>
            <li><a href="albums.php">Альбомы</a></li>
            <li><a href="music.php">Музыка</a></li>
        </ul>
        <div class="header-right">
            
            <?php if (isset($_SESSION['user_id'])): ?>
                <!-- Профиль пользователя в шапке для авторизованных -->
                <div class="user-profile-menu">
                    <div class="user-avatar-small">
                        <?php
                        $avatar = '';
                        if (isset($_SESSION['avatar']) && !empty($_SESSION['avatar']) && $_SESSION['avatar'] != 'default-avatar.png') {
                            if (strpos($_SESSION['avatar'], 'http') === 0) {
                                $avatar = $_SESSION['avatar'];
                            } else {
                                $avatar = 'images/' . $_SESSION['avatar'];
                            }
                        } else {
                            $avatar = 'https://ui-avatars.com/api/?name=' . urlencode($_SESSION['username'] ?? 'User') . '&background=9333ea&color=fff&size=40';
                        }
                        ?>
                        <img src="<?php echo $avatar; ?>" alt="Аватар">
                    </div>
                    <span class="user-name"><?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?></span>
                    <i class="fas fa-chevron-down"></i>
                    
                    <!-- Выпадающее меню -->
                    <div class="user-dropdown">
                        <a href="profile.php"><i class="fas fa-user"></i> Мой профиль</a>
                        <div class="dropdown-divider"></div>
                        <a href="logout.php" id="logoutBtn"><i class="fas fa-sign-out-alt"></i> Выйти</a>
                    </div>
                </div>
            <?php else: ?>
                <!-- Кнопка входа для неавторизованных -->
                <a href="login.php"><button class="auth-btn">Войти / Регистрация</button></a>
            <?php endif; ?>
        </div>
    </header>

    <!-- Герой-секция -->
    <section class="hero-section">
        <div class="hero-bg"></div>
        <h1 class="hero-title fade-in">НАЙДИ СВОЮ МУЗЫКАЛЬНУЮ ТЕМУ</h1>
        <p class="hero-subtitle fade-in delay-1">Ваша персональная коллекция из бесчисленного количества треков. От
            классики до современности, от неизвестных инди-исполнителей до мировых звезд.</p>
    </section>

    <!-- Секция статистики -->
    <section class="stats-section">
        <h2 class="section-title">МУЗЫКАЛЬНАЯ ВСЕЛЕННАЯ В ЦИФРАХ</h2>
        <div class="stats-container">
            <div class="stat-card fade-in">
                <div class="stat-icon"><i class="fas fa-microphone"></i></div>
                <div class="stat-count" id="artistsCount"><?php echo $totalArtists; ?></div>
                <div class="stat-label">Артистов</div>
            </div>
            <div class="stat-card fade-in delay-1">
                <div class="stat-icon"><i class="fas fa-compact-disc"></i></div>
                <div class="stat-count" id="albumsCount"><?php echo $totalAlbums; ?></div>
                <div class="stat-label">Альбомов</div>
            </div>
            <div class="stat-card fade-in delay-2">
                <div class="stat-icon"><i class="fas fa-music"></i></div>
                <div class="stat-count" id="songsCount"><?php echo $totalSongs; ?></div>
                <div class="stat-label">Треков</div>
            </div>
            <div class="stat-card fade-in delay-3">
                <div class="stat-icon"><i class="fas fa-users"></i></div>
                <div class="stat-count" id="usersCount"><?php echo $totalUsers; ?></div>
                <div class="stat-label">Слушателей</div>
            </div>
        </div>
    </section>

    <!-- Слайдер новых релизов -->
    <?php if (!empty($newReleases)): ?>
    <section class="new-releases-section">
        <h2 class="section-title">НОВЫЕ РЕЛИЗЫ</h2>
        <div class="slider-container" id="newReleasesSlider">
            <div class="slider-track" id="sliderTrack">
                <?php foreach ($newReleases as $release): ?>
                <div class="slider-slide">
                    <div class="slide-image">
                        <img src="<?php echo htmlspecialchars($release['обложка']); ?>" alt="<?php echo htmlspecialchars($release['название']); ?>" onerror="this.src='images/default-album.jpg'">
                    </div>
                    <div class="slide-content">
                        <span class="slide-badge">НОВЫЙ РЕЛИЗ</span>
                        <h3 class="slide-title"><?php echo htmlspecialchars($release['название']); ?></h3>
                        <p class="slide-artist"><?php echo htmlspecialchars($release['artist_name'] ?? 'Неизвестный артист'); ?></p>
                        <p class="slide-description"><?php echo htmlspecialchars($release['описание'] ?? 'Описание отсутствует.'); ?></p>
                        <div class="slide-meta">
                            <span><i class="far fa-calendar"></i> <?php echo $release['year']; ?></span>
                            <span><i class="fas fa-music"></i> <?php echo $release['tracks_count']; ?> треков</span>
                        </div>
                        <div class="hero-buttons">
                            <button class="btn btn-primary" onclick="playAlbum(<?php echo $release['id_альбома']; ?>, '<?php echo htmlspecialchars($release['название']); ?>')">
                                <i class="fas fa-play"></i> СЛУШАТЬ
                            </button>
                            <button class="btn btn-secondary" onclick="addToPlaylist(<?php echo $release['id_альбома']; ?>, '<?php echo htmlspecialchars($release['название']); ?>')">
                                <i class="fas fa-plus"></i> В ПЛЕЙЛИСТ
                            </button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="slider-controls">
                <button class="slider-btn" id="prevSlide">
                    <i class="fas fa-chevron-left"></i>
                </button>
                <div class="slider-dots" id="sliderDots"></div>
                <button class="slider-btn" id="nextSlide">
                    <i class="fas fa-chevron-right"></i>
                </button>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Карусель жанров - ТОЛЬКО ИЗ ТАБЛИЦЫ ЖАНРЫ -->
    <!-- <?php if (!empty($genres)): ?>
    <section class="genres-section">
        <h2 class="section-title">ИССЛЕДУЙТЕ ПО ЖАНРАМ</h2>
        <div class="genres-carousel" id="genresCarousel">
            <?php 
            $iconIndex = 0;
            foreach ($genres as $genre): 
            ?>
            <div class="genre-card fade-in" onclick="window.location.href='music.php?genre=<?php echo urlencode($genre['название']); ?>'">
                <div class="genre-icon">
                    <i class="fas <?php echo $genreIcons[$iconIndex % count($genreIcons)]; ?>"></i>
                </div>
                <h4 class="genre-title"><?php echo htmlspecialchars($genre['название']); ?></h4>
                <div class="genre-count"><?php echo rand(50, 500); ?> артистов</div>
            </div>
            <?php 
            $iconIndex++;
            endforeach; 
            ?>
        </div>
    </section>
    <?php endif; ?> -->

    <!-- Секция популярных альбомов - ТОЛЬКО РЕАЛЬНЫЕ АЛЬБОМЫ -->
    <?php if (!empty($trendingAlbums)): ?>
    <section class="albums-section">
        <h2 class="section-title">СЕЙЧАС В ТРЕНДЕ</h2>
        <div class="albums-grid" id="trendingAlbums">
            <?php foreach ($trendingAlbums as $album): ?>
            <div class="album-card fade-in">
                <img src="<?php echo htmlspecialchars($album['обложка']); ?>" alt="<?php echo htmlspecialchars($album['название']); ?>" class="album-cover" onerror="this.src='images/default-album.jpg'">
                <div class="album-info">
                    <h3 class="album-title"><?php echo htmlspecialchars($album['название']); ?></h3>
                    <p class="album-artist"><?php echo htmlspecialchars($album['artist_name'] ?? 'Неизвестный артист'); ?></p>
                    <div class="album-meta">
                        <span><i class="far fa-calendar"></i> <?php echo date('Y', strtotime($album['дата_релиза'] ?? 'now')); ?></span>
                    </div>
                    <div class="album-actions">
                        <button class="action-btn play-btn" onclick="playAlbum(<?php echo $album['id_альбома']; ?>, '<?php echo htmlspecialchars($album['название']); ?>')">
                            <i class="fas fa-play"></i>
                        </button>
                        <button class="action-btn" onclick="likeAlbum(<?php echo $album['id_альбома']; ?>, '<?php echo htmlspecialchars($album['название']); ?>')">
                            <i class="far fa-heart"></i>
                        </button>
                        <button class="action-btn" onclick="addAlbumToPlaylist(<?php echo $album['id_альбома']; ?>, '<?php echo htmlspecialchars($album['название']); ?>')">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- Секция топ-чартов - ТОЛЬКО РЕАЛЬНЫЕ ПЕСНИ -->
    <?php if (!empty($chartsSongs)): ?>
    <section class="charts-section">
        <h2 class="section-title">ТОП ТРЕКОВ НЕДЕЛИ</h2>
        <div class="charts-container" id="topCharts">
            <?php foreach ($chartsSongs as $index => $song): ?>
            <div class="chart-item fade-in">
                <div class="chart-position"><?php echo $index + 1; ?></div>
                <div class="chart-info">
                    <div class="chart-song"><?php echo htmlspecialchars($song['название']); ?></div>
                    <div class="chart-artist"><?php echo htmlspecialchars($song['artist_name'] ?? 'Неизвестный артист'); ?></div>
                </div>
                <div class="chart-meta">
                    <span><?php echo $song['duration_formatted']; ?></span>
                    <button class="action-btn" onclick="playSong(<?php echo $song['id_песни']; ?>, '<?php echo htmlspecialchars($song['название']); ?>')"><i class="fas fa-play"></i></button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- Футер -->
    <footer class="footer">
        <div class="footer-grid">
            <div class="footer-column">
                <h3>SoundWave</h3>
                
            </div>
                    </div>
        <div class="copyright">
            &copy; 2026 SoundWave. Все права защищены. Все названия треков и альбомов являются собственностью их
            правообладателей.
        </div>
    </footer>
    <script src="js/floating-notes.js"></script>
    <script>
        // Передаем данные из PHP в JavaScript для счетчиков
        const statsData = {
            artists: <?php echo $totalArtists; ?>,
            albums: <?php echo $totalAlbums; ?>,
            songs: <?php echo $totalSongs; ?>,
            users: <?php echo $totalUsers; ?>
        };
        
        // Количество слайдов для точек
        const slideCount = <?php echo count($newReleases); ?>;
    </script>
    <script src="js/main.js"></script>
</body>
</html>