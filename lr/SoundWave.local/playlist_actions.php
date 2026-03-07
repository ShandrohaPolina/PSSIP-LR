<?php
session_start();

$servername = "localhost";
$username = "root";
$password = "yaustala17";
$dbname = "soundwave";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Ошибка подключения к БД']);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Не авторизован']);
    exit;
}

$user_id = $_SESSION['user_id'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

header('Content-Type: application/json');

switch ($action) {
    case 'create':
        createPlaylist($conn, $user_id);
        break;
    case 'edit':
        editPlaylist($conn, $user_id);
        break;
    case 'delete':
        deletePlaylist($conn, $user_id);
        break;
    case 'get':
        getPlaylist($conn, $user_id);
        break;
    case 'add_song':
        addSongToPlaylist($conn, $user_id);
        break;
    case 'get_tracks':
        getPlaylistTracks($conn, $user_id);
        break;
    case 'remove_track':
        removeTrackFromPlaylist($conn, $user_id);
        break;
    default:
        echo json_encode(['success' => false, 'error' => 'Неизвестное действие']);
}

function createPlaylist($conn, $user_id) {
    $name = $_POST['name'] ?? '';
    $description = $_POST['description'] ?? '';
    
    if (empty($name)) {
        echo json_encode(['success' => false, 'error' => 'Введите название плейлиста']);
        return;
    }
    
    $cover = 'default-playlist.jpg';
    $upload_dir = 'images/';
    
    if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);
    
    if (isset($_FILES['cover']) && $_FILES['cover']['error'] === UPLOAD_ERR_OK) {
        $file_name = time() . '_' . basename($_FILES['cover']['name']);
        $target_path = $upload_dir . $file_name;
        $imageFileType = strtolower(pathinfo($target_path, PATHINFO_EXTENSION));
        $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        if (in_array($imageFileType, $allowed_types) && $_FILES['cover']['size'] <= 5 * 1024 * 1024) {
            if (move_uploaded_file($_FILES['cover']['tmp_name'], $target_path)) {
                $cover = $file_name;
            }
        }
    } elseif (!empty($_POST['cover_url'])) {
        $cover = $_POST['cover_url'];
    }
    
    $stmt = $conn->prepare("INSERT INTO плейлисты (id_пользователя, название, описание, обложка) VALUES (?, ?, ?, ?)");
    $stmt->execute([$user_id, $name, $description, $cover]);
    $playlist_id = $conn->lastInsertId();
    
    $cover_path = $cover;
    if (strpos($cover, 'http') !== 0 && $cover != 'default-playlist.jpg') {
        $cover_path = 'images/' . $cover;
    } elseif ($cover == 'default-playlist.jpg') {
        $cover_path = 'images/default-playlist.jpg';
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Плейлист создан',
        'playlist' => [
            'id_плейлиста' => $playlist_id,
            'название' => $name,
            'описание' => $description,
            'обложка' => $cover_path,
            'tracksCount' => 0
        ]
    ]);
}

function editPlaylist($conn, $user_id) {
    $playlist_id = $_POST['playlist_id'] ?? 0;
    $name = $_POST['name'] ?? '';
    $description = $_POST['description'] ?? '';
    
    if (empty($playlist_id) || empty($name)) {
        echo json_encode(['success' => false, 'error' => 'Не все данные заполнены']);
        return;
    }
    
    $checkStmt = $conn->prepare("SELECT обложка FROM плейлисты WHERE id_плейлиста = ? AND id_пользователя = ?");
    $checkStmt->execute([$playlist_id, $user_id]);
    $playlist = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$playlist) {
        echo json_encode(['success' => false, 'error' => 'Плейлист не найден']);
        return;
    }
    
    $cover = $playlist['обложка'];
    $upload_dir = 'images/';
    
    if (isset($_FILES['cover']) && $_FILES['cover']['error'] === UPLOAD_ERR_OK) {
        $file_name = time() . '_' . basename($_FILES['cover']['name']);
        $target_path = $upload_dir . $file_name;
        $imageFileType = strtolower(pathinfo($target_path, PATHINFO_EXTENSION));
        $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        if (in_array($imageFileType, $allowed_types) && $_FILES['cover']['size'] <= 5 * 1024 * 1024) {
            if (move_uploaded_file($_FILES['cover']['tmp_name'], $target_path)) {
                $cover = $file_name;
            }
        }
    } elseif (!empty($_POST['cover_url'])) {
        $cover = $_POST['cover_url'];
    }
    
    $stmt = $conn->prepare("UPDATE плейлисты SET название = ?, описание = ?, обложка = ? WHERE id_плейлиста = ? AND id_пользователя = ?");
    $stmt->execute([$name, $description, $cover, $playlist_id, $user_id]);
    
    $cover_path = $cover;
    if (strpos($cover, 'http') !== 0 && $cover != 'default-playlist.jpg') {
        $cover_path = 'images/' . $cover;
    } elseif ($cover == 'default-playlist.jpg') {
        $cover_path = 'images/default-playlist.jpg';
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Плейлист обновлен',
        'playlist' => [
            'id_плейлиста' => $playlist_id,
            'название' => $name,
            'описание' => $description,
            'обложка' => $cover_path
        ]
    ]);
}

function deletePlaylist($conn, $user_id) {
    $playlist_id = $_POST['playlist_id'] ?? 0;
    
    if (empty($playlist_id)) {
        echo json_encode(['success' => false, 'error' => 'Не указан ID плейлиста']);
        return;
    }
    
    $checkStmt = $conn->prepare("SELECT id_плейлиста FROM плейлисты WHERE id_плейлиста = ? AND id_пользователя = ?");
    $checkStmt->execute([$playlist_id, $user_id]);
    
    if ($checkStmt->rowCount() == 0) {
        echo json_encode(['success' => false, 'error' => 'Плейлист не найден']);
        return;
    }
    
    $conn->prepare("DELETE FROM треки_плейлистов WHERE id_плейлиста = ?")->execute([$playlist_id]);
    $conn->prepare("DELETE FROM плейлисты WHERE id_плейлиста = ? AND id_пользователя = ?")->execute([$playlist_id, $user_id]);
    
    echo json_encode(['success' => true, 'message' => 'Плейлист удален']);
}

function getPlaylist($conn, $user_id) {
    $playlist_id = $_GET['id'] ?? 0;
    
    if (empty($playlist_id)) {
        echo json_encode(['success' => false, 'error' => 'Не указан ID плейлиста']);
        return;
    }
    
    $stmt = $conn->prepare("SELECT * FROM плейлисты WHERE id_плейлиста = ? AND id_пользователя = ?");
    $stmt->execute([$playlist_id, $user_id]);
    $playlist = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($playlist) {
        echo json_encode(['success' => true, 'playlist' => $playlist]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Плейлист не найден']);
    }
}

function addSongToPlaylist($conn, $user_id) {
    $playlist_id = $_POST['playlist_id'] ?? 0;
    $song_id = $_POST['song_id'] ?? 0;
    
    if (empty($playlist_id) || empty($song_id)) {
        echo json_encode(['success' => false, 'error' => 'Не все данные заполнены']);
        return;
    }
    
    $checkStmt = $conn->prepare("SELECT id_плейлиста FROM плейлисты WHERE id_плейлиста = ? AND id_пользователя = ?");
    $checkStmt->execute([$playlist_id, $user_id]);
    
    if ($checkStmt->rowCount() == 0) {
        echo json_encode(['success' => false, 'error' => 'Плейлист не найден']);
        return;
    }
    
    $checkTrackStmt = $conn->prepare("SELECT id_записи FROM треки_плейлистов WHERE id_плейлиста = ? AND id_песни = ?");
    $checkTrackStmt->execute([$playlist_id, $song_id]);
    
    if ($checkTrackStmt->rowCount() > 0) {
        echo json_encode(['success' => false, 'error' => 'Трек уже есть в плейлисте']);
        return;
    }
    
    $stmt = $conn->prepare("INSERT INTO треки_плейлистов (id_плейлиста, id_песни) VALUES (?, ?)");
    $stmt->execute([$playlist_id, $song_id]);
    
    echo json_encode(['success' => true, 'message' => 'Трек добавлен в плейлист']);
}

function getPlaylistTracks($conn, $user_id) {
    $playlist_id = $_GET['playlist_id'] ?? 0;
    
    if (empty($playlist_id)) {
        echo json_encode(['success' => false, 'error' => 'Не указан ID плейлиста']);
        return;
    }
    
    $checkStmt = $conn->prepare("SELECT id_плейлиста FROM плейлисты WHERE id_плейлиста = ? AND id_пользователя = ?");
    $checkStmt->execute([$playlist_id, $user_id]);
    
    if ($checkStmt->rowCount() == 0) {
        echo json_encode(['success' => false, 'error' => 'Плейлист не найден']);
        return;
    }
    
    $stmt = $conn->prepare("
        SELECT 
            p.id_песни,
            p.название,
            p.длительность,
            a.имя as artist_name
        FROM треки_плейлистов tp
        JOIN песни p ON tp.id_песни = p.id_песни
        LEFT JOIN артисты a ON p.id_артиста = a.id_артиста
        WHERE tp.id_плейлиста = ?
        ORDER BY tp.id_записи
    ");
    $stmt->execute([$playlist_id]);
    $tracks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($tracks as &$track) {
        if (!empty($track['длительность'])) {
            $parts = explode(':', $track['длительность']);
            $minutes = ltrim($parts[0], '0') ?: '0';
            $minutes = intval($minutes);
            $seconds = $parts[1];
            $track['duration_formatted'] = $minutes . ':' . $seconds;
        } else {
            $track['duration_formatted'] = '0:00';
        }
    }
    
    echo json_encode([
        'success' => true,
        'tracks' => $tracks,
        'tracksCount' => count($tracks)
    ]);
}

function removeTrackFromPlaylist($conn, $user_id) {
    $playlist_id = $_POST['playlist_id'] ?? 0;
    $track_id = $_POST['track_id'] ?? 0;
    
    if (empty($playlist_id) || empty($track_id)) {
        echo json_encode(['success' => false, 'error' => 'Не все данные заполнены']);
        return;
    }
    
    $checkStmt = $conn->prepare("SELECT id_плейлиста FROM плейлисты WHERE id_плейлиста = ? AND id_пользователя = ?");
    $checkStmt->execute([$playlist_id, $user_id]);
    
    if ($checkStmt->rowCount() == 0) {
        echo json_encode(['success' => false, 'error' => 'Плейлист не найден']);
        return;
    }
    
    $stmt = $conn->prepare("DELETE FROM треки_плейлистов WHERE id_плейлиста = ? AND id_песни = ?");
    $stmt->execute([$playlist_id, $track_id]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Трек удален из плейлиста']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Трек не найден в плейлисте']);
    }
}
?>