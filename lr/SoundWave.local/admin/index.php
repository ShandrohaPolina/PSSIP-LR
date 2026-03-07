<?php
require_once 'includes/config.php';
require_once 'includes/header.php';
require_once 'includes/sidebar.php';

// Получаем статистику по таблицам
$tables = getTables($pdo);
$stats = [];

foreach ($tables as $table) {
    $countStmt = $pdo->query("SELECT COUNT(*) FROM `$table`");
    $stats[$table] = $countStmt->fetchColumn();
}

// Получаем текущую таблицу для отображения
$currentTable = $_GET['table'] ?? 'артисты';
if (empty($currentTable)) {
    $currentTable = 'артисты';
}

// Проверяем, существует ли такая таблица
try {
    $tableCheck = $pdo->query("SHOW TABLES LIKE '$currentTable'");
    if ($tableCheck->rowCount() == 0) {
        $currentTable = 'артисты';
    }
} catch (Exception $e) {
    $currentTable = 'артисты';
}

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 20;

// Получаем данные таблицы
$tableData = getTableData($pdo, $currentTable, $page, $perPage);
$columns = getTableColumns($pdo, $currentTable);

// Названия таблиц для отображения
$tableNames = [
    'артисты' => 'Артисты',
    'альбомы' => 'Альбомы',
    'песни' => 'Песни',
    'жанры' => 'Жанры',
    'пользователи' => 'Пользователи',
    'плейлисты' => 'Плейлисты',
    'треки_плейлистов' => 'Треки плейлистов'
];

// Определяем поля ID для каждой таблицы
$idFields = [
    'артисты' => 'id_артиста',
    'альбомы' => 'id_альбома',
    'песни' => 'id_песни',
    'жанры' => 'id_жанра',
    'пользователи' => 'id_пользователя',
    'плейлисты' => 'id_плейлиста',
    'треки_плейлистов' => 'id_записи'
];

$currentIdField = $idFields[$currentTable] ?? 'id';
?>

<!-- Передаем данные в JavaScript -->
<script>
const currentTable = '<?php echo $currentTable; ?>';
const currentIdField = '<?php echo $currentIdField; ?>';
</script>

<div class="admin-header">
    <div class="header-title">
        <h1>Панель управления</h1>
        <p>Добро пожаловать, <?php echo htmlspecialchars($_SESSION['admin_username']); ?>!</p>
    </div>
    <div class="header-actions">
        <button class="btn btn-primary" onclick="window.location.reload()">
            <i class="fas fa-sync-alt"></i> Обновить
        </button>
    </div>
</div>

<!-- Быстрые действия -->
<div class="quick-actions">
    <h3><i class="fas fa-bolt"></i> Быстрые действия</h3>
    <div class="actions-grid">
        <div class="action-btn" onclick="openAddModal()">
            <i class="fas fa-plus-circle"></i>
            <span>Добавить запись</span>
        </div>
        <div class="action-btn" onclick="exportTable()">
            <i class="fas fa-file-export"></i>
            <span>Экспорт таблицы</span>
        </div>
       <div class="action-btn" onclick="window.location.href='reports.php'">
    <i class="fas fa-chart-line"></i>
    <span>Формирование отчёта</span>
</div>
    </div>
</div>

<!-- Таблица данных -->
<div class="table-container">
    <div class="table-header">
        <h2>
            <i class="fas fa-table"></i>
            <?php echo $tableNames[$currentTable] ?? $currentTable; ?>
        </h2>
        <div class="table-filters">
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" id="searchInput" placeholder="Поиск...">
            </div>
            <select class="per-page-select" id="perPageSelect" onchange="changePerPage(this.value)">
                <option value="20" <?php echo $perPage == 20 ? 'selected' : ''; ?>>20 записей</option>
                <option value="50" <?php echo $perPage == 50 ? 'selected' : ''; ?>>50 записей</option>
                <option value="100" <?php echo $perPage == 100 ? 'selected' : ''; ?>>100 записей</option>
            </select>
            <button class="btn btn-primary" onclick="openAddModal()">
                <i class="fas fa-plus"></i> Добавить
            </button>
        </div>
    </div>
    
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <?php foreach ($columns as $column): ?>
                        <th><?php echo htmlspecialchars($column['Field']); ?></th>
                    <?php endforeach; ?>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tableData['data'] as $row): ?>
                    <tr>
                        <?php foreach ($columns as $column): ?>
                            <td>
                                <?php 
                                $value = $row[$column['Field']] ?? '';
                                $fieldName = $column['Field'];
                                
                                if (is_null($value)) {
                                    echo '<span style="color: #666;">NULL</span>';
                                } elseif (in_array($fieldName, ['фото', 'обложка', 'аватар']) && !empty($value)) {
                                    if (strpos($value, 'http') === 0) {
                                        echo '<a href="' . htmlspecialchars($value) . '" target="_blank">';
                                        echo '<img src="' . htmlspecialchars($value) . '" style="width: 50px; height: 50px; object-fit: cover; border-radius: 5px;">';
                                        echo '</a>';
                                    } else {
                                        $imagePath = '../images/' . $value;
                                        if (file_exists($imagePath)) {
                                            echo '<a href="' . $imagePath . '" target="_blank">';
                                            echo '<img src="' . $imagePath . '" style="width: 50px; height: 50px; object-fit: cover; border-radius: 5px;">';
                                            echo '</a>';
                                        } else {
                                            echo '<span style="color: #888;">Файл не найден</span>';
                                        }
                                    }
                                } elseif (in_array($fieldName, ['id_артиста', 'id_альбома', 'id_жанра', 'id_пользователя', 'id_плейлиста'])) {
                                    echo 'ID: ' . htmlspecialchars($value);
                                } elseif (strlen($value) > 50) {
                                    echo htmlspecialchars(substr($value, 0, 50)) . '...';
                                } else {
                                    echo htmlspecialchars($value);
                                }
                                ?>
                            </td>
                        <?php endforeach; ?>
                        <td class="actions">
                            <?php
                            $recordId = $row[$currentIdField] ?? 0;
                            $recordName = $row['имя'] ?? $row['название'] ?? $row['имя_пользователя'] ?? 'запись';
                            ?>                            
                            <a href="#" class="action-icon edit" onclick="editRecord(<?php echo $recordId; ?>, '<?php echo htmlspecialchars(addslashes($recordName)); ?>')">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form method="POST" action="delete.php" style="display: inline;" id="delete-form-<?php echo $recordId; ?>">
                                <input type="hidden" name="table" value="<?php echo htmlspecialchars($currentTable); ?>">
                                <input type="hidden" name="id" value="<?php echo $recordId; ?>">
                                <button type="button" class="action-icon delete" onclick="confirmDelete(<?php echo $recordId; ?>, '<?php echo htmlspecialchars(addslashes($recordName)); ?>')">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Пагинация -->
<div class="pagination">
    <div class="pagination-info">
        Показано <?php echo ($page - 1) * $perPage + 1; ?> - 
        <?php echo min($page * $perPage, $tableData['total']); ?> 
        из <?php echo $tableData['total']; ?> записей
    </div>
    <div class="pagination-controls">
        <button class="page-btn" <?php echo $page <= 1 ? 'disabled' : ''; ?> onclick="goToPage(<?php echo $page - 1; ?>)">
            <i class="fas fa-chevron-left"></i>
        </button>
        <div class="page-numbers">
            <?php for ($i = 1; $i <= $tableData['pages']; $i++): ?>
                <?php if ($i >= $page - 2 && $i <= $page + 2): ?>
                    <button class="page-number <?php echo $i == $page ? 'active' : ''; ?>" onclick="goToPage(<?php echo $i; ?>)">
                        <?php echo $i; ?>
                    </button>
                <?php endif; ?>
            <?php endfor; ?>
        </div>
        <button class="page-btn" <?php echo $page >= $tableData['pages'] ? 'disabled' : ''; ?> onclick="goToPage(<?php echo $page + 1; ?>)">
            <i class="fas fa-chevron-right"></i>
        </button>
    </div>
</div>

</div>

<!-- Модальное окно добавления/редактирования -->
<div class="modal" id="editModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modalTitle">Добавить запись</h3>
            <button class="modal-close" onclick="closeModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form method="POST" action="save.php" id="editForm" enctype="multipart/form-data">
            <div class="modal-body">
                <input type="hidden" name="table" value="<?php echo htmlspecialchars($currentTable); ?>">
                <input type="hidden" name="id" id="recordId" value="">
                
                <?php foreach ($columns as $column): ?>
                    <?php 
                    $fieldName = $column['Field'];
                    
                    // Пропускаем поле ID (оно auto_increment)
                    if ($fieldName == $currentIdField) continue;
                    
                    // Определяем, обязательно ли поле
                    $isRequired = ($column['Null'] == 'NO' && $fieldName != $currentIdField);
                    ?>
                    
                    <div class="form-group">
                        <label for="<?php echo $fieldName; ?>">
                            <?php echo htmlspecialchars($fieldName); ?>
                            <?php if ($isRequired): ?>
                                <span style="color: #ef4444;">*</span>
                            <?php endif; ?>
                        </label>
                        
                        <?php if (in_array($fieldName, ['фото', 'обложка', 'аватар'])): ?>
                            <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                                <input type="file" name="<?php echo $fieldName; ?>" id="<?php echo $fieldName; ?>" 
                                       accept="image/jpeg,image/png,image/gif,image/webp" style="flex: 1; min-width: 200px;">
                                <span style="color: #888;">или</span>
                                <input type="text" name="<?php echo $fieldName; ?>_url" id="<?php echo $fieldName; ?>_url" 
                                       placeholder="URL изображения" style="flex: 2; min-width: 200px;">
                            </div>
                            <div id="<?php echo $fieldName; ?>_preview" style="margin-top: 10px;"></div>
                            <small style="color: #888; display: block; margin-top: 5px;">
                                Можно загрузить файл или указать ссылку на изображение
                            </small>
                            
                        <?php elseif (strpos($column['Type'], 'text') !== false): ?>
                            <textarea name="<?php echo $fieldName; ?>" id="<?php echo $fieldName; ?>" rows="4" 
                                      placeholder="<?php echo htmlspecialchars($fieldName); ?>" <?php echo $isRequired ? 'required' : ''; ?>></textarea>
                            
                        <?php elseif (strpos($fieldName, 'дата') !== false || strpos($fieldName, 'date') !== false): ?>
                            <input type="date" name="<?php echo $fieldName; ?>" id="<?php echo $fieldName; ?>" <?php echo $isRequired ? 'required' : ''; ?>>
                            
                        <?php elseif (strpos($fieldName, 'год') !== false || strpos($fieldName, 'year') !== false): ?>
                            <input type="number" name="<?php echo $fieldName; ?>" id="<?php echo $fieldName; ?>" 
                                   min="1900" max="2026" placeholder="Год" <?php echo $isRequired ? 'required' : ''; ?>>
                            
                        <?php elseif (strpos($fieldName, 'время') !== false || $fieldName == 'длительность'): ?>
                            <input type="text" name="<?php echo $fieldName; ?>" id="<?php echo $fieldName; ?>" 
                                   placeholder="MM:SS (например 03:45)" pattern="[0-9]{1,2}:[0-9]{2}" <?php echo $isRequired ? 'required' : ''; ?>>
                            <small style="color: #888; display: block; margin-top: 5px;">Формат: минуты:секунды (например 03:45)</small>
                            
                        <?php elseif ($fieldName == 'сингл'): ?>
                            <select name="<?php echo $fieldName; ?>" id="<?php echo $fieldName; ?>" <?php echo $isRequired ? 'required' : ''; ?>>
                                <option value="1">Да (сингл)</option>
                                <option value="0">Нет (альбомный трек)</option>
                            </select>
                            
                        <?php elseif ($fieldName == 'формат_аудио'): ?>
                            <select name="<?php echo $fieldName; ?>" id="<?php echo $fieldName; ?>" <?php echo $isRequired ? 'required' : ''; ?>>
                                <option value="MP3">MP3</option>
                                <option value="FLAC">FLAC</option>
                                <option value="WAV">WAV</option>
                                <option value="AAC">AAC</option>
                            </select>
                            
                        <?php elseif (strpos($fieldName, 'id_') === 0): ?>
                            <select name="<?php echo $fieldName; ?>" id="<?php echo $fieldName; ?>" <?php echo $isRequired ? 'required' : ''; ?>>
                                <option value="">Выберите...</option>
                                <?php
                                $relatedTable = '';
                                $displayField = '';
                                
                                if ($fieldName == 'id_артиста') {
                                    $relatedTable = 'артисты';
                                    $displayField = 'имя';
                                } elseif ($fieldName == 'id_альбома') {
                                    $relatedTable = 'альбомы';
                                    $displayField = 'название';
                                } elseif ($fieldName == 'id_жанра') {
                                    $relatedTable = 'жанры';
                                    $displayField = 'название';
                                } elseif ($fieldName == 'id_пользователя') {
                                    $relatedTable = 'пользователи';
                                    $displayField = 'имя_пользователя';
                                } elseif ($fieldName == 'id_плейлиста') {
                                    $relatedTable = 'плейлисты';
                                    $displayField = 'название';
                                }
                                
                                if (!empty($relatedTable)) {
                                    try {
                                        $relStmt = $pdo->query("SELECT * FROM `$relatedTable` ORDER BY $displayField LIMIT 500");
                                        while ($relRow = $relStmt->fetch()) {
                                            $displayValue = $relRow[$displayField] ?? ('ID: ' . $relRow[key($relRow)]);
                                            echo '<option value="' . $relRow[key($relRow)] . '">' . htmlspecialchars($displayValue) . '</option>';
                                        }
                                    } catch (Exception $e) {
                                        echo '<option value="">Ошибка загрузки данных</option>';
                                    }
                                }
                                ?>
                            </select>
                            
                        <?php elseif (strpos($fieldName, 'email') !== false): ?>
                            <input type="email" name="<?php echo $fieldName; ?>" id="<?php echo $fieldName; ?>" 
                                   placeholder="email@example.com" <?php echo $isRequired ? 'required' : ''; ?>>
                            
                        <?php elseif (strpos($fieldName, 'пароль') !== false): ?>
                            <input type="password" name="<?php echo $fieldName; ?>" id="<?php echo $fieldName; ?>" 
                                   placeholder="••••••••" <?php echo $isRequired ? 'required' : ''; ?>>
                            
                        <?php else: ?>
                            <input type="text" name="<?php echo $fieldName; ?>" id="<?php echo $fieldName; ?>" 
                                   placeholder="<?php echo htmlspecialchars($fieldName); ?>" <?php echo $isRequired ? 'required' : ''; ?>>
                        <?php endif; ?>
                        
                        <?php if (!empty($column['Comment'])): ?>
                            <small style="color: #888; display: block; margin-top: 5px;">
                                <?php echo htmlspecialchars($column['Comment']); ?>
                            </small>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal()">Отмена</button>
                <button type="submit" class="btn btn-primary">Сохранить</button>
            </div>
        </form>
    </div>
</div>

<script>
// Функции для работы с пагинацией (ИСПРАВЛЕННЫЕ)
function goToPage(page) {
    // Убираем currentSearch, так как он не определен
    window.location.href = 'index.php?table=' + currentTable + '&page=' + page + '&per_page=<?php echo $perPage; ?>';
}

function changePerPage(perPage) {
    window.location.href = 'index.php?table=' + currentTable + '&page=1&per_page=' + perPage;
}

// Функции для работы с модальным окном
function openAddModal() {
    document.getElementById('modalTitle').textContent = 'Добавить запись';
    document.getElementById('recordId').value = '';
    document.getElementById('editForm').reset();
    
    // Очищаем превью изображений
    <?php foreach ($columns as $column): 
        $fieldName = $column['Field'];
        if (in_array($fieldName, ['фото', 'обложка', 'аватар'])): ?>
        let preview<?php echo $fieldName; ?> = document.getElementById('<?php echo $fieldName; ?>_preview');
        if (preview<?php echo $fieldName; ?>) {
            preview<?php echo $fieldName; ?>.innerHTML = '';
        }
    <?php endif; endforeach; ?>
    
    document.getElementById('editModal').classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closeModal() {
    document.getElementById('editModal').classList.remove('show');
    document.body.style.overflow = '';
}

function editRecord(id, name) {
    console.log('Редактирование записи ID:', id, 'Название:', name);
    
    document.getElementById('modalTitle').textContent = 'Редактировать запись: ' + name;
    document.getElementById('recordId').value = id;
    
    document.getElementById('editModal').classList.add('show');
    document.body.style.overflow = 'hidden';
    
    // Очищаем форму
    document.getElementById('editForm').reset();
    
    // Очищаем превью
    <?php foreach ($columns as $column): 
        $fieldName = $column['Field'];
        if (in_array($fieldName, ['фото', 'обложка', 'аватар'])): ?>
        let preview<?php echo $fieldName; ?> = document.getElementById('<?php echo $fieldName; ?>_preview');
        if (preview<?php echo $fieldName; ?>) {
            preview<?php echo $fieldName; ?>.innerHTML = '';
        }
    <?php endif; endforeach; ?>
    
    // Загружаем данные
    fetch('get_record.php?table=' + currentTable + '&id=' + id)
        .then(response => response.json())
        .then(data => {
            console.log('Данные получены:', data);
            
            if (data.error) {
                alert('Ошибка: ' + data.error);
                closeModal();
                return;
            }
            
            // Заполняем поля
            for (let fieldName in data) {
                if (fieldName == currentIdField) continue;
                
                let input = document.getElementById(fieldName);
                if (input) {
                    if (input.type === 'file') continue;
                    
                    if (input.tagName === 'SELECT') {
                        input.value = data[fieldName];
                    } else if (input.type === 'date' && data[fieldName]) {
                        input.value = data[fieldName].substring(0, 10);
                    } else {
                        input.value = data[fieldName] || '';
                    }
                }
            }
            
            // Показываем превью изображений
            <?php foreach ($columns as $column): 
                $fieldName = $column['Field'];
                if (in_array($fieldName, ['фото', 'обложка', 'аватар'])): ?>
                if (data.<?php echo $fieldName; ?>) {
                    let previewDiv = document.getElementById('<?php echo $fieldName; ?>_preview');
                    if (previewDiv) {
                        let imgValue = data.<?php echo $fieldName; ?>;
                        let imgHtml = '<div style="display: flex; align-items: center; gap: 10px; padding: 10px; background: rgba(30,30,40,0.5); border-radius: 8px;">';
                        imgHtml += '<span style="color: #888;">Текущее:</span>';
                        if (imgValue.startsWith('http')) {
                            imgHtml += '<img src="' + imgValue + '" style="max-width: 80px; max-height: 80px; border-radius: 5px; border: 2px solid #9333ea;" onerror="this.style.display=\'none\'">';
                        } else {
                            imgHtml += '<img src="../images/' + imgValue + '" style="max-width: 80px; max-height: 80px; border-radius: 5px; border: 2px solid #9333ea;" onerror="this.style.display=\'none\'">';
                        }
                        imgHtml += '</div>';
                        previewDiv.innerHTML = imgHtml;
                    }
                }
            <?php endif; endforeach; ?>
        })
        .catch(error => {
            console.error('Ошибка:', error);
            alert('Ошибка при загрузке данных');
            closeModal();
        });
}

function exportTable() {
    window.location.href = 'export.php?table=' + currentTable;
}


// Поиск по таблице
document.getElementById('searchInput').addEventListener('input', function(e) {
    const searchTerm = e.target.value.toLowerCase();
    const rows = document.querySelectorAll('.data-table tbody tr');
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(searchTerm) ? '' : 'none';
    });
});

// Закрытие модального окна
window.onclick = function(event) {
    const modal = document.getElementById('editModal');
    if (event.target == modal) {
        closeModal();
    }
}

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeModal();
    }
});
</script>

<?php
require_once 'includes/footer.php';
?>