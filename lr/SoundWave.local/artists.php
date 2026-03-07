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
$countries = isset($_GET['countries']) ? $_GET['countries'] : [];
$minYear = isset($_GET['min_year']) ? (int)$_GET['min_year'] : 1950;
$maxYear = isset($_GET['max_year']) ? (int)$_GET['max_year'] : 2025;

// Функция для правильного формирования пути к фото
function getArtistPhotoPath($photo) {
    if (empty($photo) || $photo == 'default-artist.jpg' || $photo == 'images/default-artist.jpg') {
        return 'images/default-artist.jpg';
    }
    
    if (strpos($photo, 'http') === 0) {
        return $photo;
    }
    
    if (strpos($photo, 'images/') === 0) {
        if (strpos($photo, 'images/images/') === 0) {
            return substr($photo, 7);
        }
        return $photo;
    }
    
    if ($photo == 'imagesdefault-artist.jpg') {
        return 'images/default-artist.jpg';
    }
    
    return 'images/' . $photo;
}

// Получаем общее количество артистов (с учетом фильтров)
$countQuery = "SELECT COUNT(*) as total FROM артисты WHERE 1=1";
$countParams = [];
$types = "";

if (!empty($search)) {
    $countQuery .= " AND имя LIKE ?";
    $countParams[] = "%$search%";
    $types .= "s";
}

if (!empty($countries)) {
    $placeholders = implode(',', array_fill(0, count($countries), '?'));
    $countQuery .= " AND страна IN ($placeholders)";
    foreach ($countries as $country) {
        $countParams[] = $country;
        $types .= "s";
    }
}

if ($minYear > 1950) {
    $countQuery .= " AND год_дебюта >= ?";
    $countParams[] = $minYear;
    $types .= "i";
}

if ($maxYear < 2025) {
    $countQuery .= " AND год_дебюта <= ?";
    $countParams[] = $maxYear;
    $types .= "i";
}

if (!empty($countParams)) {
    $countStmt = $conn->prepare($countQuery);
    $countStmt->bind_param($types, ...$countParams);
    $countStmt->execute();
    $totalArtists = $countStmt->get_result()->fetch_assoc()['total'];
} else {
    $totalArtistsResult = $conn->query("SELECT COUNT(*) as total FROM артисты");
    $totalArtists = $totalArtistsResult->fetch_assoc()['total'];
}

// Получаем количество уникальных стран
$countriesQuery = "SELECT COUNT(DISTINCT страна) as total FROM артисты WHERE страна IS NOT NULL AND страна != ''";
$countriesResult = $conn->query($countriesQuery);
$totalCountries = $countriesResult->fetch_assoc()['total'];

// Получаем средний год дебюта
$avgYearQuery = "SELECT AVG(год_дебюта) as avg FROM артисты WHERE год_дебюта IS NOT NULL";
$avgYearResult = $conn->query($avgYearQuery);
$avgYear = round($avgYearResult->fetch_assoc()['avg']);

// Получаем список всех жанров
$genresQuery = "SELECT название FROM жанры ORDER BY название";
$genresResult = $conn->query($genresQuery);
$genres = [];
if ($genresResult->num_rows > 0) {
    while($row = $genresResult->fetch_assoc()) {
        $genres[] = $row['название'];
    }
}

// Получаем список всех стран с количеством артистов
$allCountriesQuery = "SELECT страна, COUNT(*) as count FROM артисты WHERE страна IS NOT NULL AND страна != '' GROUP BY страна ORDER BY страна";
$allCountriesResult = $conn->query($allCountriesQuery);
$countriesList = [];
$countryCounts = [];
if ($allCountriesResult->num_rows > 0) {
    while($row = $allCountriesResult->fetch_assoc()) {
        $countriesList[] = $row['страна'];
        $countryCounts[$row['страна']] = $row['count'];
    }
}

// Получаем минимальный и максимальный год для слайдера
$yearRangeQuery = "SELECT MIN(год_дебюта) as min_year, MAX(год_дебюта) as max_year FROM артисты WHERE год_дебюта IS NOT NULL";
$yearRangeResult = $conn->query($yearRangeQuery);
$yearRange = $yearRangeResult->fetch_assoc();
$minYearDb = $yearRange['min_year'] ?? 1950;
$maxYearDb = $yearRange['max_year'] ?? 2025;

// Пагинация
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$artistsPerPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 20;
$offset = ($page - 1) * $artistsPerPage;

// Получаем артистов для текущей страницы с учетом фильтров
$artistsQuery = "SELECT * FROM артисты WHERE 1=1";
$artistsParams = [];
$types = "";

if (!empty($search)) {
    $artistsQuery .= " AND имя LIKE ?";
    $artistsParams[] = "%$search%";
    $types .= "s";
}

if (!empty($countries)) {
    $placeholders = implode(',', array_fill(0, count($countries), '?'));
    $artistsQuery .= " AND страна IN ($placeholders)";
    foreach ($countries as $country) {
        $artistsParams[] = $country;
        $types .= "s";
    }
}

if ($minYear > 1950) {
    $artistsQuery .= " AND год_дебюта >= ?";
    $artistsParams[] = $minYear;
    $types .= "i";
}

if ($maxYear < 2025) {
    $artistsQuery .= " AND год_дебюта <= ?";
    $artistsParams[] = $maxYear;
    $types .= "i";
}

$artistsQuery .= " ORDER BY id_артиста LIMIT ?, ?";
$artistsParams[] = $offset;
$artistsParams[] = $artistsPerPage;
$types .= "ii";

if (!empty($artistsParams)) {
    $artistsStmt = $conn->prepare($artistsQuery);
    $artistsStmt->bind_param($types, ...$artistsParams);
    $artistsStmt->execute();
    $artistsResult = $artistsStmt->get_result();
} else {
    $artistsResult = $conn->query("SELECT * FROM артисты ORDER BY id_артиста LIMIT $offset, $artistsPerPage");
}

$artists = [];
if ($artistsResult->num_rows > 0) {
    while($row = $artistsResult->fetch_assoc()) {
        $albumsCountQuery = "SELECT COUNT(*) as count FROM альбомы WHERE id_артиста = " . $row['id_артиста'];
        $albumsCountResult = $conn->query($albumsCountQuery);
        $albumsCount = $albumsCountResult->fetch_assoc()['count'];
        
        $row['albumsCount'] = $albumsCount;
        $artists[] = $row;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Артисты - SoundWave</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&family=Orbitron:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/particles.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/artists.css">
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
            <li><a href="artists.php" class="active">Артисты</a></li>
            <li><a href="albums.php">Альбомы</a></li>
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

    <section class="artists-hero-section">
        <div class="hero-bg"></div>
        <h1 class="hero-title fade-in">ИСПОЛНИТЕЛИ СО ВСЕГО МИРА</h1>
        <p class="hero-subtitle fade-in delay-1">Откройте для себя тысячи артистов — от легенд прошлого до новых звезд.</p>
        
        <div class="artists-stats fade-in delay-2">
            <div class="artist-stat">
                <span class="stat-number"><?php echo number_format($totalArtists); ?></span>
                <span class="stat-label">артистов</span>
            </div>
            <div class="artist-stat">
                <span class="stat-number"><?php echo $totalCountries; ?></span>
                <span class="stat-label">стран</span>
            </div>
            <div class="artist-stat">
                <span class="stat-number"><?php echo count($genres); ?></span>
                <span class="stat-label">жанра</span>
            </div>
            <div class="artist-stat">
                <span class="stat-number"><?php echo $avgYear; ?></span>
                <span class="stat-label">средний год</span>
            </div>
        </div>
    </section>

    <main class="artists-main-content">
        <div class="artists-container">
            <!-- Левая колонка - фильтры -->
            <aside class="music-sidebar">
                <div class="sidebar-header">
                    <h3><i class="fas fa-filter"></i> Фильтры</h3>
                    <button class="clear-filters" id="clearFilters">
                        <i class="fas fa-redo"></i> Сбросить
                    </button>
                </div>

                <form method="GET" action="artists.php" id="filterForm">
                    <input type="hidden" name="page" value="1">
                    <?php if (!empty($search)): ?>
                        <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                    <?php endif; ?>
                    
                    <div class="filter-accordion">
                        <!-- Фильтр по стране -->
                        <div class="filter-group">
                            <button class="filter-header active" data-filter="country">
                                <i class="fas fa-globe"></i>
                                <span>Страна</span>
                                <i class="fas fa-chevron-down"></i>
                            </button>
                            <div class="filter-content" style="display: block;">
                                <div class="filter-search">
                                    <input type="text" placeholder="Поиск страны..." id="countrySearch">
                                </div>
                                <div class="filter-options" id="countryOptions">
                                    <?php foreach ($countriesList as $country): ?>
                                    <label class="filter-option">
                                        <input type="checkbox" name="countries[]" value="<?php echo htmlspecialchars($country); ?>" <?php echo in_array($country, (array)$countries) ? 'checked' : ''; ?>>
                                        <span class="checkmark"></span>
                                        <span class="option-label"><?php echo htmlspecialchars($country); ?></span>
                                        <span class="option-count">(<?php echo $countryCounts[$country]; ?>)</span>
                                    </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Фильтр по году дебюта -->
                        <div class="filter-group">
                            <button class="filter-header" data-filter="debut">
                                <i class="fas fa-calendar-alt"></i>
                                <span>Год дебюта</span>
                                <i class="fas fa-chevron-down"></i>
                            </button>
                            <div class="filter-content">
                                <div class="year-range">
                                    <div class="range-values">
                                        <span id="minDebutYear"><?php echo $minYear; ?></span>
                                        <span> — </span>
                                        <span id="maxDebutYear"><?php echo $maxYear; ?></span>
                                    </div>
                                    <div class="range-slider">
                                        <input type="range" name="min_year" min="<?php echo $minYearDb; ?>" max="<?php echo $maxYearDb; ?>" value="<?php echo $minYear; ?>" class="range-min" id="debutYearMin">
                                        <input type="range" name="max_year" min="<?php echo $minYearDb; ?>" max="<?php echo $maxYearDb; ?>" value="<?php echo $maxYear; ?>" class="range-max" id="debutYearMax">
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

            <!-- Правая колонка - список артистов -->
            <section class="artists-content">
                <div class="content-header">
                    <div class="header-left">
                        <h2>Все артисты</h2>
                        <span class="artists-count">Найдено <?php echo number_format($totalArtists); ?> артистов</span>
                    </div>
                    <div class="header-right">
                        <form method="GET" action="artists.php" id="searchForm" style="display: flex; gap: 10px; width: 100%;">
                            <div class="artist-search" style="flex: 1;">
                                <input type="text" name="search" id="artistNameSearch" placeholder="Введите имя артиста..." value="<?php echo htmlspecialchars($search); ?>">
                                <i class="fas fa-search"></i>
                            </div>
                            
                            <?php if (!empty($countries)): ?>
                                <?php foreach ($countries as $country): ?>
                                    <input type="hidden" name="countries[]" value="<?php echo htmlspecialchars($country); ?>">
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

                <div class="artists-grid-container">
                    <div class="artists-grid">
                        <?php if (empty($artists)): ?>
                            <div class="no-results">
                                <i class="fas fa-search"></i>
                                <h3>Ничего не найдено</h3>
                                <p>Попробуйте изменить параметры поиска или сбросить фильтры</p>
                                <button class="btn" onclick="window.location.href='artists.php'">Сбросить фильтры</button>
                            </div>
                        <?php else: ?>
                            <?php foreach ($artists as $artist): ?>
                            <div class="artist-card fade-in">
                                <div class="artist-cover">
                                    <img src="<?php echo getArtistPhotoPath($artist['фото']); ?>" alt="<?php echo htmlspecialchars($artist['имя']); ?>" onerror="this.src='images/default-artist.jpg'">
                                    <div class="artist-overlay"></div>
                                </div>
                                <div class="artist-info">
                                    <h3 class="artist-name"><?php echo htmlspecialchars($artist['имя']); ?></h3>
                                    <div class="artist-details">
                                        <span><i class="fas fa-globe"></i> <?php echo htmlspecialchars($artist['страна'] ?? 'Не указана'); ?></span>
                                        <span><i class="fas fa-calendar"></i> <?php echo htmlspecialchars($artist['год_дебюта'] ?? 'Не указан'); ?></span>
                                    </div>
                                    <div class="artist-stats">
                                        <span><i class="fas fa-compact-disc"></i> <?php echo $artist['albumsCount']; ?> альбомов</span>
                                    </div>
                                    <button class="artist-button view-details" data-id="<?php echo $artist['id_артиста']; ?>" data-name="<?php echo htmlspecialchars($artist['имя']); ?>" data-bio="<?php echo htmlspecialchars($artist['биография'] ?? 'Биография отсутствует.'); ?>">
                                        <span>ПОДРОБНЕЕ</span>
                                        <i class="fas fa-arrow-right"></i>
                                    </button>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php if (!empty($artists)): ?>
                <div class="pagination-section">
                    <div class="pagination-info">
                        <span class="showing-info">Показано <span><?php echo $offset + 1; ?></span>-<span><?php echo min($offset + $artistsPerPage, $totalArtists); ?></span> из <span><?php echo number_format($totalArtists); ?></span> артистов</span>
                    </div>
                    <div class="pagination-controls">
                        <?php
                        $urlParams = [];
                        if (!empty($search)) $urlParams[] = "search=" . urlencode($search);
                        if (!empty($countries)) {
                            foreach ($countries as $c) {
                                $urlParams[] = "countries[]=" . urlencode($c);
                            }
                        }
                        if ($minYear > 1950) $urlParams[] = "min_year=$minYear";
                        if ($maxYear < 2025) $urlParams[] = "max_year=$maxYear";
                        $urlParams[] = "per_page=$artistsPerPage";
                        $queryString = !empty($urlParams) ? '&' . implode('&', $urlParams) : '';
                        ?>
                        <button class="page-btn prev-btn" <?php echo $page <= 1 ? 'disabled' : ''; ?> onclick="window.location.href='artists.php?page=<?php echo $page - 1; ?><?php echo $queryString; ?>'">
                            <i class="fas fa-chevron-left"></i>
                        </button>
                        <div class="page-numbers">
                            <?php
                            $totalPages = ceil($totalArtists / $artistsPerPage);
                            for ($i = 1; $i <= $totalPages; $i++):
                                if ($i >= $page - 2 && $i <= $page + 2):
                            ?>
                            <button class="page-number <?php echo $i == $page ? 'active' : ''; ?>" onclick="window.location.href='artists.php?page=<?php echo $i; ?><?php echo $queryString; ?>'">
                                <?php echo $i; ?>
                            </button>
                            <?php
                                endif;
                            endfor;
                            ?>
                        </div>
                        <button class="page-btn next-btn" <?php echo $page >= $totalPages ? 'disabled' : ''; ?> onclick="window.location.href='artists.php?page=<?php echo $page + 1; ?><?php echo $queryString; ?>'">
                            <i class="fas fa-chevron-right"></i>
                        </button>
                    </div>
                    <div class="pagination-per-page">
                        <span>На странице:</span>
                        <select id="artistsPerPage" onchange="window.location.href='artists.php?page=1&per_page='+this.value+'<?php echo !empty($urlParams) ? '&' . implode('&', $urlParams) : ''; ?>'">
                            <option value="20" <?php echo $artistsPerPage == 20 ? 'selected' : ''; ?>>20</option>
                            <option value="40" <?php echo $artistsPerPage == 40 ? 'selected' : ''; ?>>40</option>
                            <option value="60" <?php echo $artistsPerPage == 60 ? 'selected' : ''; ?>>60</option>
                            <option value="80" <?php echo $artistsPerPage == 80 ? 'selected' : ''; ?>>80</option>
                            <option value="100" <?php echo $artistsPerPage == 100 ? 'selected' : ''; ?>>100</option>
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

    <div class="artist-bio-modal" id="artistBioModal">
        <div class="artist-bio-content">
            <div class="artist-bio-header">
                <h2 id="modalArtistName">Название артиста</h2>
                <button class="artist-bio-close" id="closeBioModal">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="artist-bio-text">
                <p id="modalArtistBio">Загрузка...</p>
            </div>
        </div>
    </div>
    <script src="js/floating-notes.js"></script>
    <script src="js/artists.js"></script>
</body>
</html>