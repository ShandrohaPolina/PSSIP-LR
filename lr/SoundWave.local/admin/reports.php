<?php
require_once 'includes/config.php';
checkAdminAuth();

// Получаем параметры периода
$period = $_GET['period'] ?? 'month';
$date_from = $_GET['date_from'] ?? date('Y-m-d', strtotime('-30 days'));
$date_to = $_GET['date_to'] ?? date('Y-m-d');
$export = $_GET['export'] ?? '';
$export_type = $_GET['export_type'] ?? '';

// Если выбран предопределённый период
switch ($period) {
    case 'today':
        $date_from = date('Y-m-d');
        $date_to = date('Y-m-d');
        break;
    case 'week':
        $date_from = date('Y-m-d', strtotime('-7 days'));
        $date_to = date('Y-m-d');
        break;
    case 'month':
        $date_from = date('Y-m-d', strtotime('-30 days'));
        $date_to = date('Y-m-d');
        break;
    case 'quarter':
        $date_from = date('Y-m-d', strtotime('-90 days'));
        $date_to = date('Y-m-d');
        break;
    case 'year':
        $date_from = date('Y-m-d', strtotime('-365 days'));
        $date_to = date('Y-m-d');
        break;
}

// ============================================
// ЭКСПОРТ В EXCEL
// ============================================
if ($export && $export_type) {
    // Устанавливаем заголовки для скачивания CSV файла
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="report_' . $export_type . '_' . date('Y-m-d') . '.csv"');
    
    // Создаём поток вывода
    $output = fopen('php://output', 'w');
    
    // UTF-8 BOM для Excel
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Разделитель - точка с запятой для русского Excel
    $delimiter = ';';
    
    switch ($export_type) {
        case 'artists':
            // Отчёт по артистам
            fputcsv($output, ['ID', 'Имя', 'Страна', 'Год дебюта', 'Количество альбомов', 'Количество песен'], $delimiter);
            
            $stmt = $pdo->query("
                SELECT 
                    a.id_артиста,
                    a.имя,
                    a.страна,
                    a.год_дебюта,
                    (SELECT COUNT(*) FROM альбомы WHERE id_артиста = a.id_артиста) as albums_count,
                    (SELECT COUNT(*) FROM песни WHERE id_артиста = a.id_артиста) as songs_count
                FROM артисты a
                ORDER BY a.id_артиста
            ");
            $artists = $stmt->fetchAll();
            
            foreach ($artists as $row) {
                fputcsv($output, [
                    $row['id_артиста'],
                    $row['имя'],
                    $row['страна'] ?? '—',
                    $row['год_дебюта'] ?? '—',
                    $row['albums_count'],
                    $row['songs_count']
                ], $delimiter);
            }
            break;
            
        case 'albums':
            // Отчёт по альбомам
            fputcsv($output, ['ID', 'Название', 'Артист', 'Дата релиза', 'Год', 'Количество песен'], $delimiter);
            
            $stmt = $pdo->query("
                SELECT 
                    al.id_альбома,
                    al.название,
                    ar.имя as artist_name,
                    al.дата_релиза,
                    YEAR(al.дата_релиза) as год,
                    (SELECT COUNT(*) FROM песни WHERE id_альбома = al.id_альбома) as songs_count
                FROM альбомы al
                LEFT JOIN артисты ar ON al.id_артиста = ar.id_артиста
                ORDER BY al.дата_релиза DESC
            ");
            $albums = $stmt->fetchAll();
            
            foreach ($albums as $row) {
                fputcsv($output, [
                    $row['id_альбома'],
                    $row['название'],
                    $row['artist_name'] ?? '—',
                    $row['дата_релиза'] ?? '—',
                    $row['год'] ?? '—',
                    $row['songs_count']
                ], $delimiter);
            }
            break;
            
        case 'songs':
            // Отчёт по песням
            fputcsv($output, ['ID', 'Название', 'Артист', 'Альбом', 'Жанр', 'Длительность', 'Сингл', 'Формат'], $delimiter);
            
            $stmt = $pdo->query("
                SELECT 
                    п.id_песни,
                    п.название,
                    арт.имя as artist_name,
                    альб.название as album_name,
                    жанр.название as genre_name,
                    п.длительность,
                    CASE WHEN п.сингл = 1 THEN 'Да' ELSE 'Нет' END as is_single,
                    п.формат_аудио
                FROM песни п
                LEFT JOIN артисты арт ON п.id_артиста = арт.id_артиста
                LEFT JOIN альбомы альб ON п.id_альбома = альб.id_альбома
                LEFT JOIN жанры жанр ON п.id_жанра = жанр.id_жанра
                ORDER BY п.id_песни DESC
            ");
            $songs = $stmt->fetchAll();
            
            foreach ($songs as $row) {
                // Форматируем длительность
                $duration = '';
                if (!empty($row['длительность'])) {
                    $parts = explode(':', $row['длительность']);
                    if (count($parts) >= 2) {
                        $minutes = ltrim($parts[0], '0') ?: '0';
                        $seconds = $parts[1];
                        $duration = $minutes . ':' . $seconds;
                    }
                }
                
                fputcsv($output, [
                    $row['id_песни'],
                    $row['название'],
                    $row['artist_name'] ?? '—',
                    $row['album_name'] ?? '—',
                    $row['genre_name'] ?? '—',
                    $duration,
                    $row['is_single'],
                    $row['формат_аудио'] ?? 'MP3'
                ], $delimiter);
            }
            break;
            
        case 'users':
            // Отчёт по пользователям
            fputcsv($output, ['ID', 'Имя пользователя', 'Email', 'Дата регистрации'], $delimiter);
            
            $stmt = $pdo->query("
                SELECT 
                    id_пользователя,
                    имя_пользователя,
                    email,
                    дата_регистрации
                FROM пользователи
                ORDER BY дата_регистрации DESC
            ");
            $users = $stmt->fetchAll();
            
            foreach ($users as $row) {
                fputcsv($output, [
                    $row['id_пользователя'],
                    $row['имя_пользователя'],
                    $row['email'],
                    date('d.m.Y H:i', strtotime($row['дата_регистрации']))
                ], $delimiter);
            }
            break;
            
        case 'playlists':
            // Отчёт по плейлистам
            fputcsv($output, ['ID', 'Название', 'Пользователь', 'Количество треков'], $delimiter);
            
            $stmt = $pdo->query("
                SELECT 
                    пл.id_плейлиста,
                    пл.название,
                    пол.имя_пользователя,
                    (SELECT COUNT(*) FROM треки_плейлистов WHERE id_плейлиста = пл.id_плейлиста) as tracks_count
                FROM плейлисты пл
                LEFT JOIN пользователи пол ON пл.id_пользователя = пол.id_пользователя
                ORDER BY пл.id_плейлиста DESC
            ");
            $playlists = $stmt->fetchAll();
            
            foreach ($playlists as $row) {
                fputcsv($output, [
                    $row['id_плейлиста'],
                    $row['название'],
                    $row['имя_пользователя'] ?? '—',
                    $row['tracks_count']
                ], $delimiter);
            }
            break;
            
        case 'stats':
            // Статистика по сайту
            fputcsv($output, ['Показатель', 'Значение'], $delimiter);
            
            // Общая статистика
            $stats = [
                'Всего артистов' => $pdo->query("SELECT COUNT(*) FROM артисты")->fetchColumn(),
                'Всего альбомов' => $pdo->query("SELECT COUNT(*) FROM альбомы")->fetchColumn(),
                'Всего песен' => $pdo->query("SELECT COUNT(*) FROM песни")->fetchColumn(),
                'Всего пользователей' => $pdo->query("SELECT COUNT(*) FROM пользователи")->fetchColumn(),
                'Всего плейлистов' => $pdo->query("SELECT COUNT(*) FROM плейлисты")->fetchColumn(),
                'Всего треков в плейлистах' => $pdo->query("SELECT COUNT(*) FROM треки_плейлистов")->fetchColumn(),
                'Среднее количество песен на альбом' => round($pdo->query("SELECT AVG(song_count) FROM (SELECT COUNT(*) as song_count FROM песни GROUP BY id_альбома) as t")->fetchColumn(), 2),
                'Среднее количество альбомов на артиста' => round($pdo->query("SELECT AVG(album_count) FROM (SELECT COUNT(*) as album_count FROM альбомы GROUP BY id_артиста) as t")->fetchColumn(), 2),
            ];
            
            foreach ($stats as $key => $value) {
                fputcsv($output, [$key, $value], $delimiter);
            }
            break;
    }
    
    fclose($output);
    exit;
}

// ============================================
// ПОЛУЧАЕМ ДАННЫЕ ДЛЯ ОТОБРАЖЕНИЯ
// ============================================

// Общая статистика
$totalArtists = $pdo->query("SELECT COUNT(*) FROM артисты")->fetchColumn();
$totalAlbums = $pdo->query("SELECT COUNT(*) FROM альбомы")->fetchColumn();
$totalSongs = $pdo->query("SELECT COUNT(*) FROM песни")->fetchColumn();
$totalUsers = $pdo->query("SELECT COUNT(*) FROM пользователи")->fetchColumn();
$totalPlaylists = $pdo->query("SELECT COUNT(*) FROM плейлисты")->fetchColumn();

// Статистика за период (новые записи)
$newArtists = $pdo->prepare("SELECT COUNT(*) FROM артисты WHERE DATE(?) <= (SELECT MAX(id_артиста) FROM артисты)");
$newArtists->execute([$date_from]);
$newArtists = $newArtists->fetchColumn();

$newAlbums = $pdo->prepare("SELECT COUNT(*) FROM альбомы WHERE DATE(?) <= (SELECT MAX(id_альбома) FROM альбомы)");
$newAlbums->execute([$date_from]);
$newAlbums = $newAlbums->fetchColumn();

$newSongs = $pdo->prepare("SELECT COUNT(*) FROM песни WHERE DATE(?) <= (SELECT MAX(id_песни) FROM песни)");
$newSongs->execute([$date_from]);
$newSongs = $newSongs->fetchColumn();

$newUsers = $pdo->prepare("SELECT COUNT(*) FROM пользователи WHERE дата_регистрации >= ?");
$newUsers->execute([$date_from . ' 00:00:00']);
$newUsers = $newUsers->fetchColumn();

// Топ-10 артистов по количеству песен
$topArtists = $pdo->query("
    SELECT 
        a.id_артиста,
        a.имя,
        a.страна,
        COUNT(п.id_песни) as songs_count,
        COUNT(DISTINCT альб.id_альбома) as albums_count
    FROM артисты a
    LEFT JOIN песни п ON a.id_артиста = п.id_артиста
    LEFT JOIN альбомы альб ON a.id_артиста = альб.id_артиста
    GROUP BY a.id_артиста
    ORDER BY songs_count DESC
    LIMIT 10
")->fetchAll();

// Топ-10 альбомов по количеству песен
$topAlbums = $pdo->query("
    SELECT 
        альб.id_альбома,
        альб.название,
        арт.имя as artist_name,
        COUNT(п.id_песни) as songs_count,
        альб.дата_релиза
    FROM альбомы альб
    LEFT JOIN песни п ON альб.id_альбома = п.id_альбома
    LEFT JOIN артисты арт ON альб.id_артиста = арт.id_артиста
    GROUP BY альб.id_альбома
    ORDER BY songs_count DESC
    LIMIT 10
")->fetchAll();

// Топ-10 жанров
$topGenres = $pdo->query("
    SELECT 
        ж.id_жанра,
        ж.название,
        COUNT(п.id_песни) as songs_count
    FROM жанры ж
    LEFT JOIN песни п ON ж.id_жанра = п.id_жанра
    GROUP BY ж.id_жанра
    ORDER BY songs_count DESC
    LIMIT 10
")->fetchAll();

// Распределение по странам
$countriesStats = $pdo->query("
    SELECT 
        страна,
        COUNT(*) as artists_count
    FROM артисты
    WHERE страна IS NOT NULL AND страна != ''
    GROUP BY страна
    ORDER BY artists_count DESC
    LIMIT 10
")->fetchAll();

// Последние добавленные записи
$recentArtists = $pdo->query("
    SELECT * FROM артисты 
    ORDER BY id_артиста DESC 
    LIMIT 5
")->fetchAll();

$recentAlbums = $pdo->query("
    SELECT альб.*, арт.имя as artist_name 
    FROM альбомы альб
    LEFT JOIN артисты арт ON альб.id_артиста = арт.id_артиста
    ORDER BY альб.id_альбома DESC 
    LIMIT 5
")->fetchAll();

$recentSongs = $pdo->query("
    SELECT п.*, арт.имя as artist_name 
    FROM песни п
    LEFT JOIN артисты арт ON п.id_артиста = арт.id_артиста
    ORDER BY п.id_песни DESC 
    LIMIT 5
")->fetchAll();

require_once 'includes/header.php';
require_once 'includes/sidebar.php';
?>

<style>
.report-card {
    background: rgba(20, 20, 30, 0.8);
    border: 1px solid rgba(147, 51, 234, 0.2);
    border-radius: 20px;
    padding: 25px;
    backdrop-filter: blur(10px);
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-box {
    background: rgba(30, 30, 40, 0.7);
    border-radius: 15px;
    padding: 20px;
    text-align: center;
    border: 1px solid rgba(147, 51, 234, 0.2);
    transition: all 0.3s ease;
}

.stat-box:hover {
    transform: translateY(-5px);
    border-color: #fbbf24;
    box-shadow: 0 10px 25px rgba(251, 191, 36, 0.15);
}

.stat-value {
    font-size: 2.5rem;
    font-weight: 800;
    font-family: 'Orbitron', sans-serif;
    background: linear-gradient(90deg, #fbbf24, #9333ea);
    -webkit-background-clip: text;
    background-clip: text;
    color: transparent;
    line-height: 1.2;
}

.stat-label {
    color: #b0b0b0;
    font-size: 0.95rem;
    margin-top: 8px;
}

.stat-change {
    font-size: 0.9rem;
    margin-top: 8px;
    color: #22c55e;
}

.period-selector {
    display: flex;
    gap: 10px;
    margin-bottom: 25px;
    flex-wrap: wrap;
}

.period-btn {
    padding: 8px 16px;
    background: rgba(30, 30, 40, 0.7);
    border: 1px solid rgba(147, 51, 234, 0.3);
    border-radius: 30px;
    color: #b0b0b0;
    cursor: pointer;
    transition: all 0.3s ease;
    font-size: 0.95rem;
}

.period-btn:hover {
    background: rgba(147, 51, 234, 0.2);
    color: #fbbf24;
    border-color: #fbbf24;
}

.period-btn.active {
    background: linear-gradient(90deg, #9333ea, #a855f7);
    color: white;
    border-color: #9333ea;
}

.export-buttons {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    margin-bottom: 30px;
}

.export-btn {
    background: rgba(30, 30, 40, 0.7);
    border: 1px solid rgba(147, 51, 234, 0.3);
    color: #b0b0b0;
    padding: 10px 20px;
    border-radius: 30px;
    font-size: 0.95rem;
    cursor: pointer;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    text-decoration: none;
}

.export-btn:hover {
    background: rgba(147, 51, 234, 0.2);
    color: #fbbf24;
    border-color: #fbbf24;
    transform: translateY(-2px);
}

.export-btn i {
    color: #22c55e;
}

.table-mini {
    width: 100%;
    border-collapse: collapse;
}

.table-mini th {
    text-align: left;
    padding: 10px;
    color: #fbbf24;
    font-size: 0.9rem;
    border-bottom: 1px solid rgba(147, 51, 234, 0.2);
}

.table-mini td {
    padding: 10px;
    color: #e0e0e0;
    border-bottom: 1px solid rgba(147, 51, 234, 0.1);
}

.table-mini tr:hover td {
    background: rgba(147, 51, 234, 0.1);
}
</style>

<div class="admin-header">
    <div class="header-title">
        <h1><i class="fas fa-chart-line" style="color: #fbbf24;"></i> Отчёты и аналитика</h1>
        <p>Статистика по базе данных SoundWave</p>
    </div>
    <div class="header-actions">
        <button class="btn btn-primary" onclick="window.location.reload()">
            <i class="fas fa-sync-alt"></i> Обновить
        </button>
    </div>
</div>

<!-- Выбор периода -->
<!-- <div class="period-selector">
    <a href="?period=today" class="period-btn <?php echo $period == 'today' ? 'active' : ''; ?>">Сегодня</a>
    <a href="?period=week" class="period-btn <?php echo $period == 'week' ? 'active' : ''; ?>">Неделя</a>
    <a href="?period=month" class="period-btn <?php echo $period == 'month' ? 'active' : ''; ?>">Месяц</a>
    <a href="?period=quarter" class="period-btn <?php echo $period == 'quarter' ? 'active' : ''; ?>">Квартал</a>
    <a href="?period=year" class="period-btn <?php echo $period == 'year' ? 'active' : ''; ?>">Год</a>
    
    <form method="GET" style="display: flex; gap: 10px; margin-left: auto;">
        <input type="hidden" name="period" value="custom">
        <input type="date" name="date_from" value="<?php echo $date_from; ?>" style="background: rgba(25,25,35,0.8); border: 1px solid rgba(147,51,234,0.3); border-radius: 30px; padding: 8px 15px; color: white;">
        <span style="color: #888;">—</span>
        <input type="date" name="date_to" value="<?php echo $date_to; ?>" style="background: rgba(25,25,35,0.8); border: 1px solid rgba(147,51,234,0.3); border-radius: 30px; padding: 8px 15px; color: white;">
        <button type="submit" class="btn btn-primary" style="padding: 8px 20px;">Применить</button>
    </form>
</div> -->

<!-- Кнопки экспорта -->
<div class="export-buttons">
    <a href="?export=1&export_type=artists&date_from=<?php echo $date_from; ?>&date_to=<?php echo $date_to; ?>" class="export-btn">
        <i class="fas fa-file-excel"></i> Экспорт артистов
    </a>
    <a href="?export=1&export_type=albums&date_from=<?php echo $date_from; ?>&date_to=<?php echo $date_to; ?>" class="export-btn">
        <i class="fas fa-file-excel"></i> Экспорт альбомов
    </a>
    <a href="?export=1&export_type=songs&date_from=<?php echo $date_from; ?>&date_to=<?php echo $date_to; ?>" class="export-btn">
        <i class="fas fa-file-excel"></i> Экспорт песен
    </a>
    <a href="?export=1&export_type=users&date_from=<?php echo $date_from; ?>&date_to=<?php echo $date_to; ?>" class="export-btn">
        <i class="fas fa-file-excel"></i> Экспорт пользователей
    </a>
    <a href="?export=1&export_type=playlists&date_from=<?php echo $date_from; ?>&date_to=<?php echo $date_to; ?>" class="export-btn">
        <i class="fas fa-file-excel"></i> Экспорт плейлистов
    </a>
    <a href="?export=1&export_type=stats&date_from=<?php echo $date_from; ?>&date_to=<?php echo $date_to; ?>" class="export-btn">
        <i class="fas fa-file-excel"></i> Общая статистика
    </a>
</div>

<!-- Основные показатели -->
<div class="stats-grid">
    <div class="stat-box">
        <div class="stat-value"><?php echo number_format($totalArtists); ?></div>
        <div class="stat-label">Всего артистов</div>
        <?php if ($newArtists > 0): ?>
        <div class="stat-change">+<?php echo $newArtists; ?> за период</div>
        <?php endif; ?>
    </div>
    
    <div class="stat-box">
        <div class="stat-value"><?php echo number_format($totalAlbums); ?></div>
        <div class="stat-label">Всего альбомов</div>
        <?php if ($newAlbums > 0): ?>
        <div class="stat-change">+<?php echo $newAlbums; ?> за период</div>
        <?php endif; ?>
    </div>
    
    <div class="stat-box">
        <div class="stat-value"><?php echo number_format($totalSongs); ?></div>
        <div class="stat-label">Всего песен</div>
        <?php if ($newSongs > 0): ?>
        <div class="stat-change">+<?php echo $newSongs; ?> за период</div>
        <?php endif; ?>
    </div>
    
    <div class="stat-box">
        <div class="stat-value"><?php echo number_format($totalUsers); ?></div>
        <div class="stat-label">Всего пользователей</div>
        <?php if ($newUsers > 0): ?>
        <div class="stat-change">+<?php echo $newUsers; ?> за период</div>
        <?php endif; ?>
    </div>
    
    <div class="stat-box">
        <div class="stat-value"><?php echo number_format($totalPlaylists); ?></div>
        <div class="stat-label">Всего плейлистов</div>
    </div>
</div>

<!-- Две колонки: Топ артисты и Топ альбомы -->
<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 25px; margin-bottom: 25px;">
    <!-- Топ-10 артистов по количеству песен -->
    <div class="report-card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3 style="color: #fbbf24;"><i class="fas fa-crown"></i> Топ-10 артистов</h3>
            <a href="?export=1&export_type=artists&date_from=<?php echo $date_from; ?>&date_to=<?php echo $date_to; ?>" class="export-btn" style="padding: 6px 12px;">
                <i class="fas fa-file-excel"></i> Excel
            </a>
        </div>
        
        <table class="table-mini">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Артист</th>
                    <th>Страна</th>
                    <th>Песен</th>
                    <th>Альбомов</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($topArtists as $index => $artist): ?>
                <tr>
                    <td><?php echo $index + 1; ?></td>
                    <td><strong><?php echo htmlspecialchars($artist['имя']); ?></strong></td>
                    <td><?php echo htmlspecialchars($artist['страна'] ?? '—'); ?></td>
                    <td><?php echo $artist['songs_count']; ?></td>
                    <td><?php echo $artist['albums_count']; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Топ-10 альбомов по количеству песен -->
    <div class="report-card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3 style="color: #fbbf24;"><i class="fas fa-crown"></i> Топ-10 альбомов</h3>
            <a href="?export=1&export_type=albums&date_from=<?php echo $date_from; ?>&date_to=<?php echo $date_to; ?>" class="export-btn" style="padding: 6px 12px;">
                <i class="fas fa-file-excel"></i> Excel
            </a>
        </div>
        
        <table class="table-mini">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Альбом</th>
                    <th>Артист</th>
                    <th>Песен</th>
                    <th>Год</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($topAlbums as $index => $album): ?>
                <tr>
                    <td><?php echo $index + 1; ?></td>
                    <td><strong><?php echo htmlspecialchars($album['название']); ?></strong></td>
                    <td><?php echo htmlspecialchars($album['artist_name'] ?? '—'); ?></td>
                    <td><?php echo $album['songs_count']; ?></td>
                    <td><?php echo $album['дата_релиза'] ? date('Y', strtotime($album['дата_релиза'])) : '—'; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Две колонки: Топ жанры и Страны -->
<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 25px; margin-bottom: 25px;">
    <!-- Топ-10 жанров -->
    <div class="report-card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3 style="color: #fbbf24;"><i class="fas fa-music"></i> Топ-10 жанров</h3>
            <a href="?export=1&export_type=songs&date_from=<?php echo $date_from; ?>&date_to=<?php echo $date_to; ?>" class="export-btn" style="padding: 6px 12px;">
                <i class="fas fa-file-excel"></i> Excel
            </a>
        </div>
        
        <table class="table-mini">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Жанр</th>
                    <th>Количество песен</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($topGenres as $index => $genre): ?>
                <tr>
                    <td><?php echo $index + 1; ?></td>
                    <td><strong><?php echo htmlspecialchars($genre['название']); ?></strong></td>
                    <td><?php echo $genre['songs_count']; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Топ стран -->
    <div class="report-card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3 style="color: #fbbf24;"><i class="fas fa-globe"></i> Страны</h3>
            <a href="?export=1&export_type=artists&date_from=<?php echo $date_from; ?>&date_to=<?php echo $date_to; ?>" class="export-btn" style="padding: 6px 12px;">
                <i class="fas fa-file-excel"></i> Excel
            </a>
        </div>
        
        <table class="table-mini">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Страна</th>
                    <th>Количество артистов</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($countriesStats as $index => $country): ?>
                <tr>
                    <td><?php echo $index + 1; ?></td>
                    <td><strong><?php echo htmlspecialchars($country['страна']); ?></strong></td>
                    <td><?php echo $country['artists_count']; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Последние добавленные записи -->
<!-- <div class="report-card" style="margin-top: 25px;">
    <h3 style="color: #fbbf24; margin-bottom: 20px;"><i class="fas fa-history"></i> Последние добавленные</h3>
    
    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px;">
        <div>
            <h4 style="color: #9333ea; margin-bottom: 15px;">Артисты</h4>
            <table class="table-mini">
                <tbody>
                    <?php foreach ($recentArtists as $artist): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($artist['имя']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <div>
            <h4 style="color: #9333ea; margin-bottom: 15px;">Альбомы</h4>
            <table class="table-mini">
                <tbody>
                    <?php foreach ($recentAlbums as $album): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($album['название']); ?> <small style="color: #888;">(<?php echo htmlspecialchars($album['artist_name'] ?? '—'); ?>)</small></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <div>
            <h4 style="color: #9333ea; margin-bottom: 15px;">Песни</h4>
            <table class="table-mini">
                <tbody>
                    <?php foreach ($recentSongs as $song): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($song['название']); ?> <small style="color: #888;">(<?php echo htmlspecialchars($song['artist_name'] ?? '—'); ?>)</small></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div> -->

<?php
require_once 'includes/footer.php';
?>