<?php
session_start();

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

// Получаем параметры фильтрации
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$genres = isset($_GET['genres']) ? $_GET['genres'] : [];
$artists = isset($_GET['artists']) ? $_GET['artists'] : [];
$albums = isset($_GET['albums']) ? $_GET['albums'] : [];
$type = isset($_GET['type']) ? $_GET['type'] : ''; // single или album

// Получаем общее количество песен (с учетом фильтров)
$countQuery = "SELECT COUNT(*) as total FROM песни п WHERE 1=1";
$countParams = [];
$types = "";

if (!empty($search)) {
    $countQuery .= " AND п.название LIKE ?";
    $countParams[] = "%$search%";
    $types .= "s";
}

if (!empty($genres)) {
    $placeholders = implode(',', array_fill(0, count($genres), '?'));
    $countQuery .= " AND п.id_жанра IN ($placeholders)";
    foreach ($genres as $genreId) {
        $countParams[] = $genreId;
        $types .= "i";
    }
}

if (!empty($artists)) {
    $placeholders = implode(',', array_fill(0, count($artists), '?'));
    $countQuery .= " AND п.id_артиста IN ($placeholders)";
    foreach ($artists as $artistId) {
        $countParams[] = $artistId;
        $types .= "i";
    }
}

if (!empty($albums)) {
    $placeholders = implode(',', array_fill(0, count($albums), '?'));
    $countQuery .= " AND п.id_альбома IN ($placeholders)";
    foreach ($albums as $albumId) {
        $countParams[] = $albumId;
        $types .= "i";
    }
}

if ($type === 'single') {
    $countQuery .= " AND п.сингл = 1";
} elseif ($type === 'album') {
    $countQuery .= " AND п.сингл = 0";
}

if (!empty($countParams)) {
    $countStmt = $conn->prepare($countQuery);
    $countStmt->bind_param($types, ...$countParams);
    $countStmt->execute();
    $totalSongs = $countStmt->get_result()->fetch_assoc()['total'];
} else {
    $totalSongsResult = $conn->query("SELECT COUNT(*) as total FROM песни");
    $totalSongs = $totalSongsResult->fetch_assoc()['total'];
}

// Получаем общее количество артистов
$totalArtistsQuery = "SELECT COUNT(*) as total FROM артисты";
$totalArtistsResult = $conn->query($totalArtistsQuery);
$totalArtists = $totalArtistsResult->fetch_assoc()['total'];

// Получаем общее количество альбомов
$totalAlbumsQuery = "SELECT COUNT(*) as total FROM альбомы";
$totalAlbumsResult = $conn->query($totalAlbumsQuery);
$totalAlbums = $totalAlbumsResult->fetch_assoc()['total'];

// Получаем список всех жанров с количеством песен
$genresQuery = "SELECT ж.id_жанра, ж.название, COUNT(п.id_песни) as songs_count 
                FROM жанры ж
                LEFT JOIN песни п ON ж.id_жанра = п.id_жанра
                GROUP BY ж.id_жанра
                ORDER BY ж.название";
$genresResult = $conn->query($genresQuery);
$genresList = [];
$genreCounts = [];
if ($genresResult->num_rows > 0) {
    while($row = $genresResult->fetch_assoc()) {
        $genresList[] = $row;
        $genreCounts[$row['id_жанра']] = $row['songs_count'];
    }
}

// Получаем список всех артистов с количеством песен
$artistsListQuery = "SELECT a.id_артиста, a.имя, COUNT(п.id_песни) as songs_count 
                     FROM артисты a
                     LEFT JOIN песни п ON a.id_артиста = п.id_артиста
                     GROUP BY a.id_артиста
                     ORDER BY a.имя";
$artistsListResult = $conn->query($artistsListQuery);
$artistsList = [];
$artistCounts = [];
if ($artistsListResult->num_rows > 0) {
    while($row = $artistsListResult->fetch_assoc()) {
        $artistsList[] = $row;
        $artistCounts[$row['id_артиста']] = $row['songs_count'];
    }
}

// Получаем список всех альбомов с количеством песен
$albumsListQuery = "SELECT al.id_альбома, al.название, арт.имя as artist_name, COUNT(п.id_песни) as songs_count 
                    FROM альбомы al
                    LEFT JOIN артисты арт ON al.id_артиста = арт.id_артиста
                    LEFT JOIN песни п ON al.id_альбома = п.id_альбома
                    GROUP BY al.id_альбома
                    ORDER BY al.название";
$albumsListResult = $conn->query($albumsListQuery);
$albumsList = [];
$albumCounts = [];
if ($albumsListResult->num_rows > 0) {
    while($row = $albumsListResult->fetch_assoc()) {
        $albumsList[] = $row;
        $albumCounts[$row['id_альбома']] = $row['songs_count'];
    }
}

// Пагинация
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$songsPerPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 20;
$offset = ($page - 1) * $songsPerPage;

// Получаем песни для текущей страницы с учетом фильтров
$songsQuery = "SELECT п.*, 
                      арт.имя as artist_name, 
                      альб.название as album_name,
                      жанр.название as genre_name,
                      CASE WHEN п.сингл = 1 THEN 'Да' ELSE 'Нет' END as is_single
               FROM песни п
               LEFT JOIN артисты арт ON п.id_артиста = арт.id_артиста
               LEFT JOIN альбомы альб ON п.id_альбома = альб.id_альбома
               LEFT JOIN жанры жанр ON п.id_жанра = жанр.id_жанра
               WHERE 1=1";
$songsParams = [];
$types = "";

if (!empty($search)) {
    $songsQuery .= " AND п.название LIKE ?";
    $songsParams[] = "%$search%";
    $types .= "s";
}

if (!empty($genres)) {
    $placeholders = implode(',', array_fill(0, count($genres), '?'));
    $songsQuery .= " AND п.id_жанра IN ($placeholders)";
    foreach ($genres as $genreId) {
        $songsParams[] = $genreId;
        $types .= "i";
    }
}

if (!empty($artists)) {
    $placeholders = implode(',', array_fill(0, count($artists), '?'));
    $songsQuery .= " AND п.id_артиста IN ($placeholders)";
    foreach ($artists as $artistId) {
        $songsParams[] = $artistId;
        $types .= "i";
    }
}

if (!empty($albums)) {
    $placeholders = implode(',', array_fill(0, count($albums), '?'));
    $songsQuery .= " AND п.id_альбома IN ($placeholders)";
    foreach ($albums as $albumId) {
        $songsParams[] = $albumId;
        $types .= "i";
    }
}

if ($type === 'single') {
    $songsQuery .= " AND п.сингл = 1";
} elseif ($type === 'album') {
    $songsQuery .= " AND п.сингл = 0";
}

$songsQuery .= " ORDER BY п.id_песни DESC LIMIT ?, ?";
$songsParams[] = $offset;
$songsParams[] = $songsPerPage;
$types .= "ii";

if (!empty($songsParams)) {
    $songsStmt = $conn->prepare($songsQuery);
    $songsStmt->bind_param($types, ...$songsParams);
    $songsStmt->execute();
    $songsResult = $songsStmt->get_result();
} else {
    $songsResult = $conn->query("SELECT п.*, 
                                        арт.имя as artist_name, 
                                        альб.название as album_name,
                                        жанр.название as genre_name,
                                        CASE WHEN п.сингл = 1 THEN 'Да' ELSE 'Нет' END as is_single
                                 FROM песни п
                                 LEFT JOIN артисты арт ON п.id_артиста = арт.id_артиста
                                 LEFT JOIN альбомы альб ON п.id_альбома = альб.id_альбома
                                 LEFT JOIN жанры жанр ON п.id_жанра = жанр.id_жанра
                                 ORDER BY п.id_песни DESC 
                                 LIMIT $offset, $songsPerPage");
}

$songs = [];
if ($songsResult->num_rows > 0) {
    while($row = $songsResult->fetch_assoc()) {
        // Форматируем длительность
        if (!empty($row['длительность'])) {
            $timeStr = $row['длительность'];
            $parts = explode(':', $timeStr);
            if (count($parts) >= 2) {
                $minutes = ltrim($parts[0], '0') ?: '0';
                $seconds = $parts[1];
                $row['duration_formatted'] = $minutes . ':' . $seconds;
            } else {
                $row['duration_formatted'] = $timeStr;
            }
        } else {
            $row['duration_formatted'] = '--:--';
        }
        $songs[] = $row;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Музыка - SoundWave</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&family=Orbitron:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/particles.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/music1.css">
    <style>
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
    <div id="floatingNotes"></div>

    <header class="sticky-header" id="header">
        <a href="index.php" class="logo">SoundWave</a>
        
        <ul class="nav-menu">
            <li><a href="index.php">Главная</a></li>
            <li><a href="artists.php">Артисты</a></li>
            <li><a href="albums.php">Альбомы</a></li>
            <li><a href="music.php" class="active">Музыка</a></li>
        </ul>
        
        <div class="header-right">
            <?php if (isset($_SESSION['user_id'])): ?>
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
                    
                    <div class="user-dropdown">
                        <a href="profile.php"><i class="fas fa-user"></i> Мой профиль</a>
                        <div class="dropdown-divider"></div>
                        <a href="logout.php" onclick="event.preventDefault(); document.getElementById('logout-form').submit();"><i class="fas fa-sign-out-alt"></i> Выйти</a>
                    </div>
                    
                    <form id="logout-form" action="logout.php" method="POST" style="display: none;">
                        <input type="hidden" name="action" value="logout">
                    </form>
                </div>
            <?php else: ?>
                <a href="login.php"><button class="auth-btn">Войти / Регистрация</button></a>
            <?php endif; ?>
        </div>
    </header>

    <section class="music-hero-section">
        <div class="hero-bg"></div>
        <h1 class="hero-title fade-in">ВСЯ МУЗЫКА В ОДНОМ МЕСТЕ</h1>
        <p class="hero-subtitle fade-in delay-1">Тысячи песен от легендарных исполнителей. Слушайте, находите новое и наслаждайтесь.</p>
        
        <div class="music-stats fade-in delay-2">
            <div class="music-stat">
                <span class="stat-number"><?php echo number_format($totalSongs); ?></span>
                <span class="stat-label">песен</span>
            </div>
            <div class="music-stat">
                <span class="stat-number"><?php echo number_format($totalArtists); ?></span>
                <span class="stat-label">артистов</span>
            </div>
            <div class="music-stat">
                <span class="stat-number"><?php echo number_format($totalAlbums); ?></span>
                <span class="stat-label">альбомов</span>
            </div>
            <div class="music-stat">
                <span class="stat-number"><?php echo count($genresList); ?></span>
                <span class="stat-label">жанров</span>
            </div>
        </div>
    </section>

    <main class="music-main-content">
        <div class="music-container">
            <!-- Левая колонка - фильтры -->
            <aside class="music-sidebar">
                <div class="sidebar-header">
                    <h3><i class="fas fa-filter"></i> Фильтры</h3>
                    <button class="clear-filters" id="clearFilters">
                        <i class="fas fa-redo"></i> Сбросить
                    </button>
                </div>

                <form method="GET" action="music.php" id="filterForm">
                    <input type="hidden" name="page" value="1">
                    <?php if (!empty($search)): ?>
                        <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                    <?php endif; ?>
                    
                    <div class="filter-accordion">
                        <!-- Фильтр по жанрам -->
                        <div class="filter-group">
                            <button class="filter-header active" data-filter="genre">
                                <i class="fas fa-music"></i>
                                <span>Жанр</span>
                                <i class="fas fa-chevron-down"></i>
                            </button>
                            <div class="filter-content" style="display: block;">
                                <div class="filter-search">
                                    <input type="text" placeholder="Поиск жанра..." id="genreSearch">
                                </div>
                                <div class="filter-options" id="genreOptions">
                                    <?php foreach ($genresList as $genre): ?>
                                    <label class="filter-option">
                                        <input type="checkbox" name="genres[]" value="<?php echo $genre['id_жанра']; ?>" <?php echo in_array($genre['id_жанра'], (array)$genres) ? 'checked' : ''; ?>>
                                        <span class="checkmark"></span>
                                        <span class="option-label"><?php echo htmlspecialchars($genre['название']); ?></span>
                                        <span class="option-count">(<?php echo $genre['songs_count']; ?>)</span>
                                    </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Фильтр по артисту -->
                        <div class="filter-group">
                            <button class="filter-header" data-filter="artist">
                                <i class="fas fa-user"></i>
                                <span>Артист</span>
                                <i class="fas fa-chevron-down"></i>
                            </button>
                            <div class="filter-content">
                                <div class="filter-search">
                                    <input type="text" placeholder="Поиск артиста..." id="artistSearch">
                                </div>
                                <div class="filter-options" id="artistOptions">
                                    <?php foreach ($artistsList as $artist): ?>
                                    <label class="filter-option">
                                        <input type="checkbox" name="artists[]" value="<?php echo $artist['id_артиста']; ?>" <?php echo in_array($artist['id_артиста'], (array)$artists) ? 'checked' : ''; ?>>
                                        <span class="checkmark"></span>
                                        <span class="option-label"><?php echo htmlspecialchars($artist['имя']); ?></span>
                                        <span class="option-count">(<?php echo $artist['songs_count']; ?>)</span>
                                    </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Фильтр по альбому -->
                        <div class="filter-group">
                            <button class="filter-header" data-filter="album">
                                <i class="fas fa-compact-disc"></i>
                                <span>Альбом</span>
                                <i class="fas fa-chevron-down"></i>
                            </button>
                            <div class="filter-content">
                                <div class="filter-search">
                                    <input type="text" placeholder="Поиск альбома..." id="albumSearch">
                                </div>
                                <div class="filter-options" id="albumOptions">
                                    <?php foreach ($albumsList as $album): ?>
                                    <label class="filter-option">
                                        <input type="checkbox" name="albums[]" value="<?php echo $album['id_альбома']; ?>" <?php echo in_array($album['id_альбома'], (array)$albums) ? 'checked' : ''; ?>>
                                        <span class="checkmark"></span>
                                        <span class="option-label"><?php echo htmlspecialchars($album['название']); ?> (<?php echo htmlspecialchars($album['artist_name']); ?>)</span>
                                        <span class="option-count">(<?php echo $album['songs_count']; ?>)</span>
                                    </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Фильтр по типу (сингл/не сингл) -->
                        <div class="filter-group">
                            <button class="filter-header" data-filter="type">
                                <i class="fas fa-star"></i>
                                <span>Тип</span>
                                <i class="fas fa-chevron-down"></i>
                            </button>
                            <div class="filter-content">
                                <div class="type-options">
                                    <label class="filter-option">
                                        <input type="radio" name="type" value="single" <?php echo $type === 'single' ? 'checked' : ''; ?> onchange="submitFilters()">
                                        <span class="checkmark"></span>
                                        <span class="option-label">Только синглы</span>
                                    </label>
                                    <label class="filter-option">
                                        <input type="radio" name="type" value="album" <?php echo $type === 'album' ? 'checked' : ''; ?> onchange="submitFilters()">
                                        <span class="checkmark"></span>
                                        <span class="option-label">Из альбомов</span>
                                    </label>
                                    <label class="filter-option">
                                        <input type="radio" name="type" value="" <?php echo empty($type) ? 'checked' : ''; ?> onchange="submitFilters()">
                                        <span class="checkmark"></span>
                                        <span class="option-label">Все</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </aside>

            <!-- Правая колонка - список песен -->
            <section class="music-content">
                <div class="content-header">
                    <div class="header-left">
                        <h2>Все песни</h2>
                        <span class="songs-count">Найдено <?php echo number_format($totalSongs); ?> песен</span>
                    </div>
                    <div class="header-right">
                        <form method="GET" action="music.php" id="searchForm" style="display: flex; gap: 10px; width: 100%;">
                            <div class="song-search" style="flex: 1;">
                                <input type="text" name="search" id="songNameSearch" placeholder="Введите название песни..." value="<?php echo htmlspecialchars($search); ?>">
                                <i class="fas fa-search"></i>
                            </div>
                            
                            <?php if (!empty($genres)): ?>
                                <?php foreach ($genres as $genreId): ?>
                                    <input type="hidden" name="genres[]" value="<?php echo $genreId; ?>">
                                <?php endforeach; ?>
                            <?php endif; ?>
                            
                            <?php if (!empty($artists)): ?>
                                <?php foreach ($artists as $artistId): ?>
                                    <input type="hidden" name="artists[]" value="<?php echo $artistId; ?>">
                                <?php endforeach; ?>
                            <?php endif; ?>
                            
                            <?php if (!empty($albums)): ?>
                                <?php foreach ($albums as $albumId): ?>
                                    <input type="hidden" name="albums[]" value="<?php echo $albumId; ?>">
                                <?php endforeach; ?>
                            <?php endif; ?>
                            
                            <?php if (!empty($type)): ?>
                                <input type="hidden" name="type" value="<?php echo $type; ?>">
                            <?php endif; ?>
                            
                            <input type="hidden" name="page" value="1">
                            <button type="submit" class="btn btn-primary" style="padding: 10px 20px;">Найти</button>
                        </form>
                    </div>
                </div>

                <div class="songs-list-container">
                    <div class="songs-table">
                        <div class="table-header">
                            <div class="table-col number">#</div>
                            <div class="table-col title">НАЗВАНИЕ</div>
                            <div class="table-col artist">АРТИСТ</div>
                            <div class="table-col album">АЛЬБОМ</div>
                            <div class="table-col genre">ЖАНР</div>
                            <div class="table-col duration">ДЛИТ.</div>
                            <div class="table-col type">ТИП</div>
                            <div class="table-col actions">ДЕЙСТВИЯ</div>
                        </div>
                        <div class="table-body">
                            <?php if (empty($songs)): ?>
                                <div style="text-align: center; padding: 50px; color: #888;">
                                    <i class="fas fa-search" style="font-size: 3rem; margin-bottom: 20px;"></i>
                                    <h3>Ничего не найдено</h3>
                                    <p>Попробуйте изменить параметры поиска или сбросить фильтры</p>
                                    <button class="btn btn-primary" onclick="window.location.href='music.php'" style="margin-top: 20px;">Сбросить фильтры</button>
                                </div>
                            <?php else: ?>
                                <?php foreach ($songs as $index => $song): ?>
                                <div class="song-row fade-in">
                                    <div class="table-col number"><?php echo $offset + $index + 1; ?></div>
                                    <div class="table-col title">
                                        <span class="song-title"><?php echo htmlspecialchars($song['название']); ?></span>
                                    </div>
                                    <div class="table-col artist"><?php echo htmlspecialchars($song['artist_name'] ?? 'Неизвестный артист'); ?></div>
                                    <div class="table-col album"><?php echo htmlspecialchars($song['album_name'] ?? '—'); ?></div>
                                    <div class="table-col genre"><?php echo htmlspecialchars($song['genre_name'] ?? '—'); ?></div>
                                    <div class="table-col duration"><?php echo $song['duration_formatted']; ?></div>
                                    <div class="table-col type">
                                        <?php if ($song['сингл'] == 1): ?>
                                        <span class="single-badge">Сингл</span>
                                        <?php else: ?>
                                        <span class="album-badge">Альбомный</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="table-col actions">
                                        <button class="action-btn play-song" data-id="<?php echo $song['id_песни']; ?>" title="Слушать">
                                            <i class="fas fa-play"></i>
                                        </button>
                                        <button class="action-btn add-to-playlist" data-id="<?php echo $song['id_песни']; ?>" data-title="<?php echo htmlspecialchars($song['название']); ?>" data-artist="<?php echo htmlspecialchars($song['artist_name'] ?? 'Неизвестный артист'); ?>" title="Добавить в плейлист">
                                            <i class="fas fa-plus"></i>
                                        </button>
                                        <button class="action-btn view-lyrics" data-id="<?php echo $song['id_песни']; ?>" data-title="<?php echo htmlspecialchars($song['название']); ?>" data-artist="<?php echo htmlspecialchars($song['artist_name'] ?? 'Неизвестный артист'); ?>" data-lyrics="<?php echo htmlspecialchars($song['текст_песни'] ?? 'Текст песни отсутствует.'); ?>" title="Текст песни">
                                            <i class="fas fa-align-left"></i>
                                        </button>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <?php if (!empty($songs)): ?>
                <div class="pagination-section">
                    <div class="pagination-info">
                        <span class="showing-info">Показано <span><?php echo $offset + 1; ?></span>-<span><?php echo min($offset + $songsPerPage, $totalSongs); ?></span> из <span><?php echo number_format($totalSongs); ?></span> песен</span>
                    </div>
                    <div class="pagination-controls">
                        <?php
                        $urlParams = [];
                        if (!empty($search)) $urlParams[] = "search=" . urlencode($search);
                        if (!empty($genres)) {
                            foreach ($genres as $g) {
                                $urlParams[] = "genres[]=" . $g;
                            }
                        }
                        if (!empty($artists)) {
                            foreach ($artists as $a) {
                                $urlParams[] = "artists[]=" . $a;
                            }
                        }
                        if (!empty($albums)) {
                            foreach ($albums as $al) {
                                $urlParams[] = "albums[]=" . $al;
                            }
                        }
                        if (!empty($type)) $urlParams[] = "type=" . $type;
                        $urlParams[] = "per_page=$songsPerPage";
                        $queryString = !empty($urlParams) ? '&' . implode('&', $urlParams) : '';
                        ?>
                        <button class="page-btn prev-btn" <?php echo $page <= 1 ? 'disabled' : ''; ?> onclick="window.location.href='music.php?page=<?php echo $page - 1; ?><?php echo $queryString; ?>'">
                            <i class="fas fa-chevron-left"></i>
                        </button>
                        <div class="page-numbers">
                            <?php
                            $totalPages = ceil($totalSongs / $songsPerPage);
                            for ($i = 1; $i <= $totalPages; $i++):
                                if ($i >= $page - 2 && $i <= $page + 2):
                            ?>
                            <button class="page-number <?php echo $i == $page ? 'active' : ''; ?>" onclick="window.location.href='music.php?page=<?php echo $i; ?><?php echo $queryString; ?>'">
                                <?php echo $i; ?>
                            </button>
                            <?php
                                endif;
                            endfor;
                            ?>
                        </div>
                        <button class="page-btn next-btn" <?php echo $page >= $totalPages ? 'disabled' : ''; ?> onclick="window.location.href='music.php?page=<?php echo $page + 1; ?><?php echo $queryString; ?>'">
                            <i class="fas fa-chevron-right"></i>
                        </button>
                    </div>
                    <div class="pagination-per-page">
                        <span>На странице:</span>
                        <select id="songsPerPage" onchange="window.location.href='music.php?page=1&per_page='+this.value+'<?php echo !empty($urlParams) ? '&' . implode('&', $urlParams) : ''; ?>'">
                            <option value="20" <?php echo $songsPerPage == 20 ? 'selected' : ''; ?>>20</option>
                            <option value="40" <?php echo $songsPerPage == 40 ? 'selected' : ''; ?>>40</option>
                            <option value="60" <?php echo $songsPerPage == 60 ? 'selected' : ''; ?>>60</option>
                            <option value="80" <?php echo $songsPerPage == 80 ? 'selected' : ''; ?>>80</option>
                            <option value="100" <?php echo $songsPerPage == 100 ? 'selected' : ''; ?>>100</option>
                        </select>
                    </div>
                </div>
                <?php endif; ?>
            </section>
        </div>
    </main>

    <footer class="footer">
        <div class="footer-grid">
            <div class="footer-column">
                <h3>SoundWave</h3>
                <ul class="footer-links">
                    <li><a href="index.php">Главная</a></li>
                    <li><a href="artists.php">Артисты</a></li>
                    <li><a href="albums.php">Альбомы</a></li>
                    <li><a href="music.php">Музыка</a></li>
                </ul>
            </div>
            
        </div>
        <div class="copyright">
            &copy; 2026 SoundWave. Все права защищены.
        </div>
    </footer>

    <div class="lyrics-modal" id="lyricsModal">
        <div class="lyrics-content">
            <div class="lyrics-header">
                <h2 id="modalSongTitle">Название песни</h2>
                <h3 id="modalSongArtist">Исполнитель</h3>
                <button class="lyrics-close" id="closeLyricsModal">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="lyrics-body">
                <pre id="modalSongLyrics">Загрузка...</pre>
            </div>
        </div>
    </div>
    <script src="js/floating-notes.js"></script>
    <script>
        const songsData = <?php echo json_encode($songs); ?>;
        const totalSongs = <?php echo $totalSongs; ?>;
        const currentPage = <?php echo $page; ?>;
        const songsPerPage = <?php echo $songsPerPage; ?>;
        const artistsList = <?php echo json_encode($artistsList); ?>;
        const albumsList = <?php echo json_encode($albumsList); ?>;
        const genresList = <?php echo json_encode($genresList); ?>;
        const isUserLoggedIn = <?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?>;
        const currentFilters = {
            search: '<?php echo addslashes($search); ?>',
            genres: <?php echo json_encode($genres); ?>,
            artists: <?php echo json_encode($artists); ?>,
            albums: <?php echo json_encode($albums); ?>,
            type: '<?php echo $type; ?>'
        };
    </script>

    <?php if (isset($_SESSION['user_id'])): ?>
    <script>
        <?php
        $conn_playlists = new mysqli($servername, $username, $password, $dbname);
        $user_id = $_SESSION['user_id'];
        $playlistsQuery = "SELECT * FROM плейлисты WHERE id_пользователя = ? ORDER BY id_плейлиста DESC";
        $playlistsStmt = $conn_playlists->prepare($playlistsQuery);
        $playlistsStmt->bind_param("i", $user_id);
        $playlistsStmt->execute();
        $playlistsResult = $playlistsStmt->get_result();
        
        $userPlaylists = [];
        if ($playlistsResult->num_rows > 0) {
            while($row = $playlistsResult->fetch_assoc()) {
                $tracksCountQuery = "SELECT COUNT(*) as count FROM треки_плейлистов WHERE id_плейлиста = ?";
                $tracksCountStmt = $conn_playlists->prepare($tracksCountQuery);
                $tracksCountStmt->bind_param("i", $row['id_плейлиста']);
                $tracksCountStmt->execute();
                $tracksCountResult = $tracksCountStmt->get_result();
                $tracksCount = $tracksCountResult->fetch_assoc()['count'];
                
                $row['tracksCount'] = $tracksCount;
                $userPlaylists[] = $row;
            }
        }
        $conn_playlists->close();
        ?>
        const userPlaylists = <?php echo json_encode($userPlaylists); ?>;
    </script>
    <?php else: ?>
    <script>const userPlaylists = [];</script>
    <?php endif; ?>

    <script src="js/music1.js"></script>
</body>
</html>