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
$artists = isset($_GET['artists']) ? $_GET['artists'] : [];
$minYear = isset($_GET['min_year']) ? (int)$_GET['min_year'] : 1950;
$maxYear = isset($_GET['max_year']) ? (int)$_GET['max_year'] : 2025;

// Функция для правильного формирования пути к обложке
function getAlbumCoverPath($cover) {
    if (empty($cover) || $cover == 'default-album.jpg' || $cover == 'images/default-album.jpg') {
        return 'images/default-album.jpg';
    }
    
    if (strpos($cover, 'http') === 0) {
        return $cover;
    }
    
    if (strpos($cover, 'images/') === 0) {
        if (strpos($cover, 'images/images/') === 0) {
            return substr($cover, 7);
        }
        return $cover;
    }
    
    if ($cover == 'imagesdefault-album.jpg') {
        return 'images/default-album.jpg';
    }
    
    return 'images/' . $cover;
}

// Получаем общее количество альбомов (с учетом фильтров)
$countQuery = "SELECT COUNT(*) as total FROM альбомы a WHERE 1=1";
$countParams = [];
$types = "";

if (!empty($search)) {
    $countQuery .= " AND a.название LIKE ?";
    $countParams[] = "%$search%";
    $types .= "s";
}

if (!empty($artists)) {
    $placeholders = implode(',', array_fill(0, count($artists), '?'));
    $countQuery .= " AND a.id_артиста IN ($placeholders)";
    foreach ($artists as $artistId) {
        $countParams[] = $artistId;
        $types .= "i";
    }
}

if ($minYear > 1950) {
    $countQuery .= " AND YEAR(a.дата_релиза) >= ?";
    $countParams[] = $minYear;
    $types .= "i";
}

if ($maxYear < 2025) {
    $countQuery .= " AND YEAR(a.дата_релиза) <= ?";
    $countParams[] = $maxYear;
    $types .= "i";
}

if (!empty($countParams)) {
    $countStmt = $conn->prepare($countQuery);
    $countStmt->bind_param($types, ...$countParams);
    $countStmt->execute();
    $totalAlbums = $countStmt->get_result()->fetch_assoc()['total'];
} else {
    $totalAlbumsResult = $conn->query("SELECT COUNT(*) as total FROM альбомы");
    $totalAlbums = $totalAlbumsResult->fetch_assoc()['total'];
}

// Получаем общее количество артистов
$totalArtistsQuery = "SELECT COUNT(*) as total FROM артисты";
$totalArtistsResult = $conn->query($totalArtistsQuery);
$totalArtists = $totalArtistsResult->fetch_assoc()['total'];

// Получаем средний год релиза
$avgYearQuery = "SELECT AVG(YEAR(дата_релиза)) as avg FROM альбомы WHERE дата_релиза IS NOT NULL";
$avgYearResult = $conn->query($avgYearQuery);
$avgYear = round($avgYearResult->fetch_assoc()['avg']);

// Получаем список всех жанров (для статистики)
$genresQuery = "SELECT название FROM жанры ORDER BY название";
$genresResult = $conn->query($genresQuery);
$genres = [];
if ($genresResult->num_rows > 0) {
    while($row = $genresResult->fetch_assoc()) {
        $genres[] = $row['название'];
    }
}

// Получаем список всех артистов для фильтра с количеством альбомов
$artistsListQuery = "SELECT a.id_артиста, a.имя, COUNT(alb.id_альбома) as albums_count 
                     FROM артисты a
                     LEFT JOIN альбомы alb ON a.id_артиста = alb.id_артиста
                     GROUP BY a.id_артиста
                     ORDER BY a.имя";
$artistsListResult = $conn->query($artistsListQuery);
$artistsList = [];
$artistCounts = [];
if ($artistsListResult->num_rows > 0) {
    while($row = $artistsListResult->fetch_assoc()) {
        $artistsList[] = $row;
        $artistCounts[$row['id_артиста']] = $row['albums_count'];
    }
}

// Получаем минимальный и максимальный год для слайдера
$yearRangeQuery = "SELECT MIN(YEAR(дата_релиза)) as min_year, MAX(YEAR(дата_релиза)) as max_year FROM альбомы WHERE дата_релиза IS NOT NULL";
$yearRangeResult = $conn->query($yearRangeQuery);
$yearRange = $yearRangeResult->fetch_assoc();
$minYearDb = $yearRange['min_year'] ?? 1950;
$maxYearDb = $yearRange['max_year'] ?? 2025;

// Пагинация
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$albumsPerPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 20;
$offset = ($page - 1) * $albumsPerPage;

// Получаем альбомы для текущей страницы с учетом фильтров
$albumsQuery = "SELECT a.*, арт.имя as artist_name 
                FROM альбомы a
                LEFT JOIN артисты арт ON a.id_артиста = арт.id_артиста
                WHERE 1=1";
$albumsParams = [];
$types = "";

if (!empty($search)) {
    $albumsQuery .= " AND a.название LIKE ?";
    $albumsParams[] = "%$search%";
    $types .= "s";
}

if (!empty($artists)) {
    $placeholders = implode(',', array_fill(0, count($artists), '?'));
    $albumsQuery .= " AND a.id_артиста IN ($placeholders)";
    foreach ($artists as $artistId) {
        $albumsParams[] = $artistId;
        $types .= "i";
    }
}

if ($minYear > 1950) {
    $albumsQuery .= " AND YEAR(a.дата_релиза) >= ?";
    $albumsParams[] = $minYear;
    $types .= "i";
}

if ($maxYear < 2025) {
    $albumsQuery .= " AND YEAR(a.дата_релиза) <= ?";
    $albumsParams[] = $maxYear;
    $types .= "i";
}

$albumsQuery .= " ORDER BY a.дата_релиза DESC LIMIT ?, ?";
$albumsParams[] = $offset;
$albumsParams[] = $albumsPerPage;
$types .= "ii";

if (!empty($albumsParams)) {
    $albumsStmt = $conn->prepare($albumsQuery);
    $albumsStmt->bind_param($types, ...$albumsParams);
    $albumsStmt->execute();
    $albumsResult = $albumsStmt->get_result();
} else {
    $albumsResult = $conn->query("SELECT a.*, арт.имя as artist_name 
                                   FROM альбомы a
                                   LEFT JOIN артисты арт ON a.id_артиста = арт.id_артиста
                                   ORDER BY a.дата_релиза DESC 
                                   LIMIT $offset, $albumsPerPage");
}

$albums = [];
if ($albumsResult->num_rows > 0) {
    while($row = $albumsResult->fetch_assoc()) {
        // Форматируем дату
        if (!empty($row['дата_релиза'])) {
            $date = new DateTime($row['дата_релиза']);
            $row['год'] = $date->format('Y');
            $row['дата_формат'] = $date->format('d.m.Y');
        } else {
            $row['год'] = 'Не указан';
            $row['дата_формат'] = 'Не указана';
        }
        
        $albums[] = $row;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Альбомы - SoundWave</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&family=Orbitron:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/particles.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/albums.css">
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
            <li><a href="albums.php" class="active">Альбомы</a></li>
            <li><a href="music.php">Музыка</a></li>
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

    <section class="albums-hero-section">
        <div class="hero-bg"></div>
        <h1 class="hero-title fade-in">КОЛЛЕКЦИЯ АЛЬБОМОВ</h1>
        <p class="hero-subtitle fade-in delay-1">Исследуйте тысячи альбомов — от классических винилов до современных релизов.</p>
        
        <div class="albums-stats fade-in delay-2">
            <div class="album-stat">
                <span class="stat-number"><?php echo number_format($totalAlbums); ?></span>
                <span class="stat-label">альбомов</span>
            </div>
            <div class="album-stat">
                <span class="stat-number"><?php echo number_format($totalArtists); ?></span>
                <span class="stat-label">артистов</span>
            </div>
            <div class="album-stat">
                <span class="stat-number"><?php echo count($genres); ?></span>
                <span class="stat-label">жанров</span>
            </div>
            <div class="album-stat">
                <span class="stat-number"><?php echo $avgYear; ?></span>
                <span class="stat-label">средний год</span>
            </div>
        </div>
    </section>

    <main class="albums-main-content">
        <div class="albums-container">
            <!-- Левая колонка - фильтры -->
            <aside class="music-sidebar">
                <div class="sidebar-header">
                    <h3><i class="fas fa-filter"></i> Фильтры</h3>
                    <button class="clear-filters" id="clearFilters">
                        <i class="fas fa-redo"></i> Сбросить
                    </button>
                </div>

                <form method="GET" action="albums.php" id="filterForm">
                    <input type="hidden" name="page" value="1">
                    <?php if (!empty($search)): ?>
                        <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                    <?php endif; ?>
                    
                    <div class="filter-accordion">
                        <!-- Фильтр по артисту -->
                        <div class="filter-group">
                            <button class="filter-header active" data-filter="artist">
                                <i class="fas fa-user"></i>
                                <span>Артист</span>
                                <i class="fas fa-chevron-down"></i>
                            </button>
                            <div class="filter-content" style="display: block;">
                                <div class="filter-search">
                                    <input type="text" placeholder="Поиск артиста..." id="artistSearch">
                                </div>
                                <div class="filter-options" id="artistOptions">
                                    <?php foreach ($artistsList as $artist): ?>
                                    <label class="filter-option">
                                        <input type="checkbox" name="artists[]" value="<?php echo $artist['id_артиста']; ?>" <?php echo in_array($artist['id_артиста'], (array)$artists) ? 'checked' : ''; ?>>
                                        <span class="checkmark"></span>
                                        <span class="option-label"><?php echo htmlspecialchars($artist['имя']); ?></span>
                                        <span class="option-count">(<?php echo $artist['albums_count']; ?>)</span>
                                    </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Фильтр по году выпуска -->
                        <div class="filter-group">
                            <button class="filter-header" data-filter="year">
                                <i class="fas fa-calendar-alt"></i>
                                <span>Год выпуска</span>
                                <i class="fas fa-chevron-down"></i>
                            </button>
                            <div class="filter-content">
                                <div class="year-range">
                                    <div class="range-values">
                                        <span id="minYear"><?php echo $minYear; ?></span>
                                        <span> — </span>
                                        <span id="maxYear"><?php echo $maxYear; ?></span>
                                    </div>
                                    <div class="range-slider">
                                        <input type="range" name="min_year" min="<?php echo $minYearDb; ?>" max="<?php echo $maxYearDb; ?>" value="<?php echo $minYear; ?>" class="range-min" id="yearMin">
                                        <input type="range" name="max_year" min="<?php echo $minYearDb; ?>" max="<?php echo $maxYearDb; ?>" value="<?php echo $maxYear; ?>" class="range-max" id="yearMax">
                                    </div>
                                </div>
                                <div class="year-quick-filters">
                                    <button type="button" class="year-quick-btn" data-year="2020">2020-е</button>
                                    <button type="button" class="year-quick-btn" data-year="2010">2010-е</button>
                                    <button type="button" class="year-quick-btn" data-year="2000">2000-е</button>
                                    <button type="button" class="year-quick-btn" data-year="1990">90-е</button>
                                    <button type="button" class="year-quick-btn" data-year="1980">80-е</button>
                                    <button type="button" class="year-quick-btn" data-year="1970">70-е</button>
                                    <button type="button" class="year-quick-btn" data-year="1960">60-е</button>
                                    <button type="button" class="year-quick-btn" data-year="1950">50-е</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </aside>

            <!-- Правая колонка - список альбомов -->
            <section class="albums-content">
                <div class="content-header">
                    <div class="header-left">
                        <h2>Все альбомы</h2>
                        <span class="albums-count">Найдено <?php echo number_format($totalAlbums); ?> альбомов</span>
                    </div>
                    <div class="header-right">
                        <form method="GET" action="albums.php" id="searchForm" style="display: flex; gap: 10px; width: 100%;">
                            <div class="album-search" style="flex: 1;">
                                <input type="text" name="search" id="albumNameSearch" placeholder="Введите название альбома..." value="<?php echo htmlspecialchars($search); ?>">
                                <i class="fas fa-search"></i>
                            </div>
                            
                            <?php if (!empty($artists)): ?>
                                <?php foreach ($artists as $artistId): ?>
                                    <input type="hidden" name="artists[]" value="<?php echo $artistId; ?>">
                                <?php endforeach; ?>
                            <?php endif; ?>
                            
                            <?php if ($minYear > 1950): ?>
                                <input type="hidden" name="min_year" value="<?php echo $minYear; ?>">
                            <?php endif; ?>
                            
                            <?php if ($maxYear < 2025): ?>
                                <input type="hidden" name="max_year" value="<?php echo $maxYear; ?>">
                            <?php endif; ?>
                            
                            <input type="hidden" name="page" value="1">
                            <button type="submit" class="btn btn-primary" style="padding: 10px 20px;">Найти</button>
                        </form>
                    </div>
                </div>

                <div class="albums-grid-container">
                    <div class="albums-grid">
                        <?php if (empty($albums)): ?>
                            <div class="no-results">
                                <i class="fas fa-search"></i>
                                <h3>Ничего не найдено</h3>
                                <p>Попробуйте изменить параметры поиска или сбросить фильтры</p>
                                <button class="btn" onclick="window.location.href='albums.php'">Сбросить фильтры</button>
                            </div>
                        <?php else: ?>
                            <?php foreach ($albums as $album): ?>
                            <div class="album-card fade-in">
                                <div class="album-cover">
                                    <img src="<?php echo getAlbumCoverPath($album['обложка']); ?>" alt="<?php echo htmlspecialchars($album['название']); ?>" onerror="this.src='images/default-album.jpg'">
                                    <div class="album-overlay"></div>
                                </div>
                                <div class="album-info">
                                    <h3 class="album-name"><?php echo htmlspecialchars($album['название']); ?></h3>
                                    <div class="album-artist"><?php echo htmlspecialchars($album['artist_name'] ?? 'Неизвестный артист'); ?></div>
                                    <div class="album-details">
                                        <span><i class="fas fa-calendar"></i> <?php echo $album['год']; ?></span>
                                    </div>
                                    <button class="album-button view-details" 
                                            data-id="<?php echo $album['id_альбома']; ?>"
                                            data-name="<?php echo htmlspecialchars($album['название']); ?>" 
                                            data-artist="<?php echo htmlspecialchars($album['artist_name'] ?? 'Неизвестный артист'); ?>"
                                            data-year="<?php echo $album['год']; ?>"
                                            data-date="<?php echo $album['дата_формат']; ?>"
                                            data-description="<?php echo htmlspecialchars($album['описание'] ?? 'Описание отсутствует.'); ?>">
                                        <span>ПОДРОБНЕЕ</span>
                                        <i class="fas fa-arrow-right"></i>
                                    </button>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php if (!empty($albums)): ?>
                <div class="pagination-section">
                    <div class="pagination-info">
                        <span class="showing-info">Показано <span><?php echo $offset + 1; ?></span>-<span><?php echo min($offset + $albumsPerPage, $totalAlbums); ?></span> из <span><?php echo number_format($totalAlbums); ?></span> альбомов</span>
                    </div>
                    <div class="pagination-controls">
                        <?php
                        $urlParams = [];
                        if (!empty($search)) $urlParams[] = "search=" . urlencode($search);
                        if (!empty($artists)) {
                            foreach ($artists as $a) {
                                $urlParams[] = "artists[]=" . $a;
                            }
                        }
                        if ($minYear > 1950) $urlParams[] = "min_year=$minYear";
                        if ($maxYear < 2025) $urlParams[] = "max_year=$maxYear";
                        $urlParams[] = "per_page=$albumsPerPage";
                        $queryString = !empty($urlParams) ? '&' . implode('&', $urlParams) : '';
                        ?>
                        <button class="page-btn prev-btn" <?php echo $page <= 1 ? 'disabled' : ''; ?> onclick="window.location.href='albums.php?page=<?php echo $page - 1; ?><?php echo $queryString; ?>'">
                            <i class="fas fa-chevron-left"></i>
                        </button>
                        <div class="page-numbers">
                            <?php
                            $totalPages = ceil($totalAlbums / $albumsPerPage);
                            for ($i = 1; $i <= $totalPages; $i++):
                                if ($i >= $page - 2 && $i <= $page + 2):
                            ?>
                            <button class="page-number <?php echo $i == $page ? 'active' : ''; ?>" onclick="window.location.href='albums.php?page=<?php echo $i; ?><?php echo $queryString; ?>'">
                                <?php echo $i; ?>
                            </button>
                            <?php
                                endif;
                            endfor;
                            ?>
                        </div>
                        <button class="page-btn next-btn" <?php echo $page >= $totalPages ? 'disabled' : ''; ?> onclick="window.location.href='albums.php?page=<?php echo $page + 1; ?><?php echo $queryString; ?>'">
                            <i class="fas fa-chevron-right"></i>
                        </button>
                    </div>
                    <div class="pagination-per-page">
                        <span>На странице:</span>
                        <select id="albumsPerPage" onchange="window.location.href='albums.php?page=1&per_page='+this.value+'<?php echo !empty($urlParams) ? '&' . implode('&', $urlParams) : ''; ?>'">
                            <option value="20" <?php echo $albumsPerPage == 20 ? 'selected' : ''; ?>>20</option>
                            <option value="40" <?php echo $albumsPerPage == 40 ? 'selected' : ''; ?>>40</option>
                            <option value="60" <?php echo $albumsPerPage == 60 ? 'selected' : ''; ?>>60</option>
                            <option value="80" <?php echo $albumsPerPage == 80 ? 'selected' : ''; ?>>80</option>
                            <option value="100" <?php echo $albumsPerPage == 100 ? 'selected' : ''; ?>>100</option>
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

    <div class="album-bio-modal" id="albumBioModal">
        <div class="album-bio-content">
            <div class="album-bio-header">
                <h2 id="modalAlbumName">Название альбома</h2>
                <button class="album-bio-close" id="closeBioModal">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="album-bio-info">
                <p><i class="fas fa-user"></i> <span id="modalAlbumArtist"></span></p>
                <p><i class="fas fa-calendar"></i> <span id="modalAlbumDate"></span></p>
            </div>
            <div class="album-bio-text">
                <h4>Описание</h4>
                <p id="modalAlbumDescription">Загрузка...</p>
            </div>
        </div>
    </div>
    <script src="js/floating-notes.js"></script>
    <script>
        const albumsData = <?php echo json_encode($albums); ?>;
        const totalAlbums = <?php echo $totalAlbums; ?>;
        const currentPage = <?php echo $page; ?>;
        const albumsPerPage = <?php echo $albumsPerPage; ?>;
        const artistsList = <?php echo json_encode($artistsList); ?>;
        const currentFilters = {
            search: '<?php echo addslashes($search); ?>',
            minYear: <?php echo $minYear; ?>,
            maxYear: <?php echo $maxYear; ?>,
            artists: <?php echo json_encode($artists); ?>
        };
    </script>
    <script src="js/albums.js"></script>
</body>
</html>