<?php
session_start();

// Проверяем, авторизован ли пользователь
// if (!isset($_SESSION['user_id'])) {
//     header('Location: login.php');
//     exit;
// }

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

// Получаем данные пользователя
$user_id = $_SESSION['user_id'];
$userQuery = "SELECT * FROM пользователи WHERE id_пользователя = ?";
$userStmt = $conn->prepare($userQuery);
$userStmt->bind_param("i", $user_id);
$userStmt->execute();
$userResult = $userStmt->get_result();
$user = $userResult->fetch_assoc();

// Получаем плейлисты пользователя
$playlistsQuery = "SELECT * FROM плейлисты WHERE id_пользователя = ? ORDER BY id_плейлиста DESC";
$playlistsStmt = $conn->prepare($playlistsQuery);
$playlistsStmt->bind_param("i", $user_id);
$playlistsStmt->execute();
$playlistsResult = $playlistsStmt->get_result();

$playlists = [];
if ($playlistsResult->num_rows > 0) {
    while($row = $playlistsResult->fetch_assoc()) {
        // Получаем количество треков в плейлисте
        $tracksCountQuery = "SELECT COUNT(*) as count FROM треки_плейлистов WHERE id_плейлиста = ?";
        $tracksCountStmt = $conn->prepare($tracksCountQuery);
        $tracksCountStmt->bind_param("i", $row['id_плейлиста']);
        $tracksCountStmt->execute();
        $tracksCountResult = $tracksCountStmt->get_result();
        $tracksCount = $tracksCountResult->fetch_assoc()['count'];
        
        $row['tracksCount'] = $tracksCount;
        $row['tracks'] = []; // Для простоты оставляем пустым, в реальном проекте нужно загружать треки
        
        // Форматируем обложку
        if (empty($row['обложка']) || $row['обложка'] == 'default-playlist.jpg') {
            $row['обложка'] = 'images/default-playlist.jpg';
        } else {
            if (strpos($row['обложка'], '/') !== 0 && strpos($row['обложка'], 'http') !== 0) {
                $row['обложка'] = 'images/' . $row['обложка'];
            }
        }
        
        $playlists[] = $row;
    }
}

// Получаем недавно прослушанные треки (для демо используем последние добавленные песни)
$recentTracksQuery = "SELECT п.*, арт.имя as artist_name 
                      FROM песни п
                      LEFT JOIN артисты арт ON п.id_артиста = арт.id_артиста
                      ORDER BY п.id_песни DESC 
                      LIMIT 5";
$recentTracksResult = $conn->query($recentTracksQuery);
$recentTracks = [];
if ($recentTracksResult->num_rows > 0) {
    while($row = $recentTracksResult->fetch_assoc()) {
        $recentTracks[] = $row;
    }
}

$conn->close();

// Форматируем дату регистрации
$registrationDate = new DateTime($user['дата_регистрации']);
$formattedDate = $registrationDate->format('d F Y');
$months = [
    'January' => 'января', 'February' => 'февраля', 'March' => 'марта',
    'April' => 'апреля', 'May' => 'мая', 'June' => 'июня',
    'July' => 'июля', 'August' => 'августа', 'September' => 'сентября',
    'October' => 'октября', 'November' => 'ноября', 'December' => 'декабря'
];
foreach ($months as $en => $ru) {
    $formattedDate = str_replace($en, $ru, $formattedDate);
}


// Функция для правильного формирования пути к обложке
function getPlaylistCover($cover) {
    if (empty($cover) || $cover == 'default-playlist.jpg') {
        return 'images/default-playlist.jpg';
    }
    if (strpos($cover, 'http') === 0) {
        return $cover;
    }
    // Убираем возможное дублирование images/
    if (strpos($cover, 'images/') === 0) {
        return $cover;
    }
    return 'images/' . $cover;
}




?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Личный кабинет - SoundWave</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&family=Orbitron:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/particles.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/profile.css">
</head>
<body>
    <!-- Плавающие музыкальные ноты -->
    <div id="floatingNotes"></div>

    <!-- Закрепленная шапка (авторизованный пользователь) -->
    <header class="sticky-header" id="header">
        <a href="index.php" class="logo">SoundWave</a>
        
        <ul class="nav-menu">
            <li><a href="index.php">Главная</a></li>
            <li><a href="artists.php">Артисты</a></li>
            <li><a href="albums.php">Альбомы</a></li>
            <li><a href="music.php">Музыка</a></li>
        </ul>
        
        <div class="header-right">         
            <!-- Профиль пользователя в шапке -->
            <div class="user-profile-menu">
                <div class="user-avatar-small">
                    <img src="<?php 
    if (!empty($user['аватар']) && $user['аватар'] != 'default-avatar.png') {
        // Если аватар загружен пользователем
        if (strpos($user['аватар'], 'http') === 0) {
            echo htmlspecialchars($user['аватар']);
        } else {
            echo 'images/' . htmlspecialchars($user['аватар']);
        }
    } else {
        // Аватар по умолчанию
        echo 'images/default-avatar.png';
    }
?>" alt="Аватар">
</div>
                <span class="user-name"><?php echo htmlspecialchars($user['имя_пользователя']); ?></span>
                <i class="fas fa-chevron-down"></i>
                
                <!-- Выпадающее меню -->
                <div class="user-dropdown">
    <a href="profile.php" class="active"><i class="fas fa-user"></i> Мой профиль</a>
    <div class="dropdown-divider"></div>
    <a href="#" id="logoutBtn" onclick="event.preventDefault(); document.getElementById('logout-form').submit();"><i class="fas fa-sign-out-alt"></i> Выйти</a>
</div>

<!-- Скрытая форма для выхода -->
<form id="logout-form" action="logout.php" method="POST" style="display: none;">
    <input type="hidden" name="action" value="logout">
</form>
            </div>
        </div>
    </header>

    <!-- Герой-секция профиля -->
    <section class="profile-hero">
        <div class="hero-bg"></div>
        <div class="profile-hero-content">
            <div class="profile-avatar-wrapper">
                <div class="profile-avatar">
                    <img src="<?php 
    if (!empty($user['аватар']) && $user['аватар'] != 'default-avatar.png') {
        // Если аватар загружен пользователем
        if (strpos($user['аватар'], 'http') === 0) {
            echo htmlspecialchars($user['аватар']);
        } else {
            echo 'images/' . htmlspecialchars($user['аватар']);
        }
    } else {
        // Аватар по умолчанию
        echo 'images/default-avatar.png';
    }
?>" alt="Аватар">
<button class="avatar-edit-btn" id="editAvatarBtn">
                        <i class="fas fa-camera"></i>
                    </button>
                </div>
            </div>
            <div class="profile-title-info">
                <h1 class="profile-name"><?php echo htmlspecialchars($user['имя_пользователя']); ?></h1>
                <p class="profile-username">@<?php echo htmlspecialchars($user['имя_пользователя']); ?></p>
                
                <div class="profile-stats-mini">
    <div class="stat-item">
        <span class="stat-value"><?php echo count($playlists); ?></span>
        <span class="stat-label">плейлиста</span>
    </div>
    <div class="stat-item">
        <?php
        // Подсчитываем общее количество песен во всех плейлистах пользователя
        $totalSongsInPlaylists = 0;
        foreach ($playlists as $playlist) {
            $totalSongsInPlaylists += $playlist['tracksCount'];
        }
        ?>
        <span class="stat-value"><?php echo $totalSongsInPlaylists; ?></span>
        <span class="stat-label">песен в плейлистах</span>
    </div>
</div>
            </div>
        </div>
    </section>

    <!-- Основной контент профиля -->
    <main class="profile-main-content">
        <div class="profile-container">
            <!-- Левая колонка - информация о пользователе -->
            <aside class="profile-sidebar">
                <!-- Карточка с информацией -->
                <div class="profile-info-card">
                    <h3><i class="fas fa-info-circle"></i> Информация</h3>
                    
                    <div class="info-item">
                        <i class="fas fa-envelope"></i>
                        <div class="info-content">
                            <span class="info-label">Email</span>
                            <span class="info-value"><?php echo htmlspecialchars($user['email']); ?></span>
                        </div>
                    </div>
                    
                    <div class="info-item">
                        <i class="fas fa-calendar-alt"></i>
                        <div class="info-content">
                            <span class="info-label">Дата регистрации</span>
                            <span class="info-value"><?php echo $formattedDate; ?></span>
                        </div>
                    </div>
                    
                </div>
            </aside>

            <!-- Правая колонка - управление плейлистами -->
            <section class="profile-playlists-section">
                <div class="playlists-header">
                    <h2><i class="fas fa-list-music"></i> Мои плейлисты</h2>
                    <button class="btn btn-primary" id="createPlaylistBtn">
                        <i class="fas fa-plus"></i> Создать плейлист
                    </button>
                </div>
            
                <!-- Список плейлистов -->
<div class="playlists-grid" id="playlistsGrid">
    <!-- Карточка нового плейлиста (добавление) -->
    <div class="playlist-card add-playlist-card" id="addPlaylistCard">
        <div class="add-playlist-icon">
            <i class="fas fa-plus-circle"></i>
            <span>Создать плейлист</span>
        </div>
    </div>
    
    <?php foreach ($playlists as $playlist): ?>
    <div class="playlist-card" data-id="<?php echo $playlist['id_плейлиста']; ?>">
        <div class="playlist-cover">
            <img src="<?php echo getPlaylistCover($playlist['обложка']); ?>" 
                 alt="<?php echo htmlspecialchars($playlist['название']); ?>" 
                 onerror="this.src='images/default-playlist.jpg'">
            <div class="playlist-overlay">
                <div class="playlist-actions">
                    <button class="playlist-action-btn play-playlist" title="Слушать">
                        <i class="fas fa-play"></i>
                    </button>
                    <button class="playlist-action-btn edit-playlist" title="Редактировать">
                        <i class="fas fa-edit"></i>
                    </button>
                </div>
            </div>
        </div>
        <div class="playlist-info">
            <div class="playlist-title"><?php echo htmlspecialchars($playlist['название']); ?></div>
            <div class="playlist-meta">
                <span><i class="fas fa-lock-open"></i></span>
                <span><i class="fas fa-music"></i> <?php echo $playlist['tracksCount']; ?></span>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
                
                <!-- Активный плейлист (детальный просмотр и редактирование) -->
                <div class="active-playlist-panel" id="activePlaylistPanel" style="display: none;">
                    <div class="active-playlist-header">
                        <div class="playlist-cover-wrapper">
                            <img id="activePlaylistCover" src="<?php echo getPlaylistCover($playlist['обложка'] ?? ''); ?>" alt="Обложка" onerror="this.src='images/default-playlist.jpg'">
                            <button class="cover-edit-btn" id="editPlaylistCoverBtn">
                                <i class="fas fa-camera"></i>
                            </button>
                        </div>
                        
                        <div class="playlist-info-wrapper">
                            <div class="playlist-type-badge">
                                
                            </div>
                            <h3 id="activePlaylistTitle"></h3>
                            <p id="activePlaylistDescription"></p>
                            
                            <div class="playlist-meta">
                                <span><i class="fas fa-music"></i> <span id="activePlaylistTracksCount">0</span> треков</span>
                                <span><i class="far fa-clock"></i> <span id="activePlaylistDuration">0:00</span></span>
                            </div>
                            
                            <div class="playlist-actions">
                                <button class="btn btn-primary play-playlist-btn" id="playPlaylistBtn">
                                    <i class="fas fa-play"></i> Слушать
                                </button>
                                <button class="btn btn-outline" id="editPlaylistBtn">
                                    <i class="fas fa-edit"></i> Редактировать
                                </button>
                                <button class="btn btn-outline delete-btn" id="deletePlaylistBtn">
                                    <i class="fas fa-trash"></i> Удалить
                                </button>
                            </div>
                        </div>
                    </div>
                    <!-- Список песен в плейлисте с drag & drop -->
                    <div class="playlist-tracks-header">
                        <h4>Треки в плейлисте</h4>
                        <span class="drag-hint"><i class="fas fa-arrows-alt"></i> Перетаскивайте для изменения порядка</span>
                    </div>
                    
                    <div class="playlist-tracks-container" id="playlistTracksContainer">
                        <div class="playlist-tracks-list sortable-list" id="playlistTracksList">
                            <!-- Песни будут добавлены через JavaScript -->
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </main>

  <!-- Модальное окно создания/редактирования плейлиста -->
<div class="modal" id="playlistModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modalTitle">Создать новый плейлист</h3>
            <button class="modal-close"><i class="fas fa-times"></i></button>
        </div>
        
        <div class="modal-body">
            <input type="hidden" id="recordId" value="">
            
            <div class="form-group">
                <label for="playlistName">Название плейлиста *</label>
                <input type="text" id="playlistName" placeholder="Например: Тренировка, Утренний кофе..." required>
            </div>
            
            <div class="form-group">
                <label for="playlistDescription">Описание (необязательно)</label>
                <textarea id="playlistDescription" rows="3" placeholder="Добавьте описание вашего плейлиста"></textarea>
            </div>
            
            <div class="form-group">
                <label>Обложка плейлиста</label>
                
                <!-- Контейнер для предпросмотра -->
                <div style="display: flex; gap: 20px; align-items: flex-start; margin-bottom: 15px;">
                    <div style="width: 100px; height: 100px; border-radius: 10px; overflow: hidden; border: 2px solid #9333ea; background: #1a1a2a;">
                        <img id="coverPreview" src="images/default-playlist.jpg" alt="Preview" style="width: 100%; height: 100%; object-fit: cover;">
                    </div>
                    
                    <div style="flex: 1;">
                        <div style="display: flex; gap: 10px; margin-bottom: 10px;">
                            <button type="button" class="btn btn-outline" style="padding: 8px 15px;" onclick="document.getElementById('coverFile').click();">
                                <i class="fas fa-upload"></i> Выбрать файл
                            </button>
                            <span style="color: #888; align-self: center;">или</span>
                        </div>
                        
                        <input type="file" id="coverFile" accept="image/jpeg,image/png,image/gif,image/webp" style="display: none;" onchange="previewCover(this)">
                        
                        <input type="text" id="coverUrl" placeholder="Вставьте ссылку на изображение" style="width: 100%; padding: 10px; background: rgba(25,25,35,0.8); border: 1px solid #9333ea; border-radius: 8px; color: white;">
                    </div>
                </div>
                
                <small style="color: #888; display: block;">
                    Можно загрузить файл (JPG, PNG, GIF, WEBP до 5 МБ) или указать ссылку на изображение
                </small>
            </div>
        </div>
        
        <div class="modal-footer">
            <button class="btn btn-outline" id="cancelModalBtn">Отмена</button>
            <button class="btn btn-primary" id="savePlaylistBtn">Сохранить плейлист</button>
        </div>
    </div>
</div>

    <!-- Модальное окно подтверждения удаления -->
    <div class="modal" id="deleteConfirmModal">
        <div class="modal-content" style="max-width: 450px;">
            <div class="modal-header">
                <h3>Удалить плейлист?</h3>
                <button class="modal-close"><i class="fas fa-times"></i></button>
            </div>
            
            <div class="modal-body">
                <div class="delete-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    <p>Вы уверены, что хотите удалить плейлист <strong id="deletePlaylistName">""</strong>? Это действие нельзя отменить.</p>
                </div>
            </div>
            
            <div class="modal-footer">
                <button class="btn btn-outline" id="cancelDeleteBtn">Отмена</button>
                <button class="btn btn-primary delete-confirm-btn" id="confirmDeleteBtn">Удалить</button>
            </div>
        </div>
    </div>

    <!-- Футер -->
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
            <div class="footer-column">
                <h3>Мой аккаунт</h3>
                <ul class="footer-links">
                    <li><a href="profile.php">Профиль</a></li>
                    <li><a href="#">Плейлисты</a></li>
                </ul>
            </div>
        </div>
        <div class="copyright">
            &copy; 2026 SoundWave. Все права защищены.
        </div>
    </footer>
\
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    <script src="js/floating-notes.js"></script>
    <script>
        // Передаем данные из PHP в JavaScript
        const userPlaylists = <?php echo json_encode($playlists); ?>;
        const currentUser = {
            id: <?php echo $user['id_пользователя']; ?>,
            username: '<?php echo addslashes($user['имя_пользователя']); ?>',
            email: '<?php echo addslashes($user['email']); ?>'
        };
    </script>
    
    <script src="js/profile.js"></script>


    <script>
document.getElementById('logoutBtn').addEventListener('click', function(e) {
    e.preventDefault();
    
    // Отправляем запрос на выход
    fetch('logout.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=logout'
    }).then(() => {
        // После выхода принудительно перезагружаем страницу с очисткой кэша
        window.location.href = 'index.php?nocache=' + new Date().getTime();
    });
});
</script>
</body>
</html>