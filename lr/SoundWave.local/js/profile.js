// Основной JavaScript для личного кабинета
document.addEventListener('DOMContentLoaded', function () {
    console.log('Profile.js loaded');

    // Инициализация компонентов
    initHeaderScroll();
    initPlaylists();
    initPlaylistModals();
    initEventListeners();

    // Добавляем обработчик для URL
    const coverUrl = document.getElementById('coverUrl');
    if (coverUrl) {
        coverUrl.addEventListener('input', function () {
            const preview = document.getElementById('coverPreview');
            if (this.value) {
                preview.src = this.value;
                preview.onerror = function () {
                    this.src = 'images/default-playlist.jpg';
                };
            } else {
                preview.src = 'images/default-playlist.jpg';
            }
        });
    }
});

// Глобальные переменные
let currentPlaylist = null;
let playlists = typeof userPlaylists !== 'undefined' ? userPlaylists : [];
let sortableInstance = null;

// Стили для предпросмотра
const style = document.createElement('style');
style.textContent = `
    #coverPreview {
        transition: all 0.3s ease;
    }
    #coverPreview:hover {
        transform: scale(1.05);
        box-shadow: 0 0 15px rgba(147, 51, 234, 0.5);
    }
`;
document.head.appendChild(style);

// Инициализация плейлистов
function initPlaylists() {
    renderPlaylists();
    addPlaylistCardHandlers();
}

// Отображение плейлистов
function renderPlaylists() {
    const playlistsGrid = document.getElementById('playlistsGrid');
    if (!playlistsGrid) return;

    const addCard = playlistsGrid.querySelector('.add-playlist-card');
    playlistsGrid.innerHTML = '';
    if (addCard) playlistsGrid.appendChild(addCard);

    playlists.forEach(playlist => {
        const card = createPlaylistCard(playlist);
        playlistsGrid.appendChild(card);
    });
}

// Создание карточки плейлиста
function createPlaylistCard(playlist) {
    const card = document.createElement('div');
    card.className = 'playlist-card';
    card.dataset.id = playlist.id_плейлиста;

    let coverPath = playlist.обложка || 'images/default-playlist.jpg';
    if (coverPath && !coverPath.startsWith('http') && !coverPath.startsWith('images/')) {
        coverPath = 'images/' + coverPath;
    }

    card.innerHTML = `
        <div class="playlist-cover">
            <img src="${coverPath}" alt="${playlist.название}" onerror="this.src='images/default-playlist.jpg'">
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
            <div class="playlist-title">${playlist.название}</div>
            <div class="playlist-meta">
                <span><i class="fas fa-music"></i> ${playlist.tracksCount || 0}</span>
            </div>
        </div>
    `;

    card.addEventListener('click', function (e) {
        if (!e.target.closest('button')) {
            openPlaylist(playlist.id_плейлиста);
        }
    });

    return card;
}

// Добавление обработчиков для карточек
function addPlaylistCardHandlers() {
    const addPlaylistCard = document.getElementById('addPlaylistCard');
    if (addPlaylistCard) {
        addPlaylistCard.addEventListener('click', function () {
            openCreatePlaylistModal();
        });
    }

    document.querySelectorAll('.play-playlist').forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.stopPropagation();
            const card = this.closest('.playlist-card');
            const playlistId = parseInt(card.dataset.id);
            playPlaylist(playlistId);
        });
    });

    document.querySelectorAll('.edit-playlist').forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.stopPropagation();
            const card = this.closest('.playlist-card');
            const playlistId = parseInt(card.dataset.id);
            openEditPlaylistModal(playlistId);
        });
    });
}

// Открытие плейлиста
function openPlaylist(playlistId) {
    currentPlaylist = playlists.find(p => p.id_плейлиста === playlistId);
    if (!currentPlaylist) return;

    const panel = document.getElementById('activePlaylistPanel');
    panel.style.display = 'block';

    let coverPath = currentPlaylist.обложка || 'images/default-playlist.jpg';
    if (coverPath && !coverPath.startsWith('http') && !coverPath.startsWith('images/')) {
        coverPath = 'images/' + coverPath;
    }

    document.getElementById('activePlaylistCover').src = coverPath;
    document.getElementById('activePlaylistTitle').textContent = currentPlaylist.название;
    document.getElementById('activePlaylistDescription').textContent = currentPlaylist.описание || 'Нет описания';
    document.getElementById('activePlaylistTracksCount').textContent = currentPlaylist.tracksCount || 0;
    document.getElementById('activePlaylistDuration').textContent = '0:00';

    loadPlaylistTracks(playlistId);
    panel.scrollIntoView({ behavior: 'smooth' });
}

// Загрузка треков плейлиста
function loadPlaylistTracks(playlistId) {
    fetch('playlist_actions.php?action=get_tracks&playlist_id=' + playlistId)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('activePlaylistTracksCount').textContent = data.tracksCount;

                let totalSeconds = 0;
                data.tracks.forEach(track => {
                    if (track.duration_formatted) {
                        const parts = track.duration_formatted.split(':');
                        if (parts.length === 2) {
                            totalSeconds += parseInt(parts[0]) * 60 + parseInt(parts[1]);
                        }
                    }
                });
                const minutes = Math.floor(totalSeconds / 60);
                const seconds = totalSeconds % 60;
                document.getElementById('activePlaylistDuration').textContent = `${minutes}:${seconds.toString().padStart(2, '0')}`;

                renderPlaylistTracks(data.tracks);
                if (data.tracks.length > 0) initDragAndDrop();
            }
        });
}

// Отображение треков
function renderPlaylistTracks(tracks) {
    const tracksList = document.getElementById('playlistTracksList');
    if (!tracksList) return;

    tracksList.innerHTML = '';

    if (tracks.length === 0) {
        tracksList.innerHTML = '<div style="text-align: center; padding: 30px; color: #888;">В этом плейлисте пока нет треков</div>';
        return;
    }

    tracks.forEach((track, index) => {
        const trackElement = createTrackElement(track, index + 1);
        tracksList.appendChild(trackElement);
    });
}

// Создание элемента трека
function createTrackElement(track, number) {
    const div = document.createElement('div');
    div.className = 'playlist-track-item';
    div.dataset.id = track.id_песни;

    div.innerHTML = `
        <i class="fas fa-grip-vertical track-drag-handle"></i>
        <span class="track-number">${number}</span>
        <img src="https://ui-avatars.com/api/?name=${encodeURIComponent(track.название)}&background=9333ea&color=fff&size=50" alt="${track.название}" class="track-item-cover">
        <div class="track-item-info">
            <div class="track-item-title">${track.название}</div>
            <div class="track-item-artist">${track.artist_name || 'Неизвестный артист'}</div>
        </div>
        <span class="track-item-duration">${track.duration_formatted || '0:00'}</span>
        <div class="track-item-actions">
            <button class="remove-track-btn" data-track-id="${track.id_песни}" title="Удалить">
                <i class="fas fa-trash"></i>
            </button>
        </div>
    `;

    const removeBtn = div.querySelector('.remove-track-btn');
    removeBtn.addEventListener('click', function (e) {
        e.stopPropagation();
        removeTrackFromPlaylist(this.dataset.trackId);
    });

    return div;
}

// Удаление трека
function removeTrackFromPlaylist(trackId) {
    if (!currentPlaylist) return;
    if (!confirm('Удалить этот трек из плейлиста?')) return;

    const formData = new FormData();
    formData.append('action', 'remove_track');
    formData.append('playlist_id', currentPlaylist.id_плейлиста);
    formData.append('track_id', trackId);

    fetch('playlist_actions.php', { method: 'POST', body: formData })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification(data.message, 'success');
                loadPlaylistTracks(currentPlaylist.id_плейлиста);

                const index = playlists.findIndex(p => p.id_плейлиста === currentPlaylist.id_плейлиста);
                if (index !== -1) {
                    playlists[index].tracksCount = (playlists[index].tracksCount || 1) - 1;
                    renderPlaylists();
                }
            }
        });
}

// Drag & Drop
function initDragAndDrop() {
    const tracksList = document.getElementById('playlistTracksList');
    if (!tracksList) return;

    if (sortableInstance) sortableInstance.destroy();

    sortableInstance = new Sortable(tracksList, {
        animation: 300,
        handle: '.track-drag-handle',
        ghostClass: 'sortable-ghost',
        dragClass: 'sortable-drag',
        onEnd: () => showNotification('Порядок треков обновлен', 'success')
    });
}

// Инициализация модальных окон
function initPlaylistModals() {
    const modal = document.getElementById('playlistModal');
    const deleteModal = document.getElementById('deleteConfirmModal');

    document.querySelectorAll('.modal-close').forEach(btn => {
        btn.addEventListener('click', () => closeModal(btn.closest('.modal')));
    });

    document.getElementById('cancelModalBtn')?.addEventListener('click', () => closeModal(modal));
    document.getElementById('cancelDeleteBtn')?.addEventListener('click', () => closeModal(deleteModal));
    document.getElementById('savePlaylistBtn')?.addEventListener('click', savePlaylist);
    document.getElementById('confirmDeleteBtn')?.addEventListener('click', deletePlaylist);
    document.getElementById('createPlaylistBtn')?.addEventListener('click', openCreatePlaylistModal);
    document.getElementById('editPlaylistBtn')?.addEventListener('click', () => currentPlaylist && openEditPlaylistModal(currentPlaylist.id_плейлиста));
    document.getElementById('deletePlaylistBtn')?.addEventListener('click', () => currentPlaylist && openDeleteConfirmModal(currentPlaylist));
    document.getElementById('playPlaylistBtn')?.addEventListener('click', () => currentPlaylist && playPlaylist(currentPlaylist.id_плейлиста));
}

// Открытие модального окна создания
function openCreatePlaylistModal() {
    document.getElementById('modalTitle').textContent = 'Создать новый плейлист';
    document.getElementById('recordId').value = '';
    document.getElementById('playlistName').value = '';
    document.getElementById('playlistDescription').value = '';
    document.getElementById('coverFile').value = '';
    document.getElementById('coverUrl').value = '';
    document.getElementById('coverPreview').src = 'images/default-playlist.jpg';
    openModal(document.getElementById('playlistModal'));
}

// Открытие модального окна редактирования
function openEditPlaylistModal(playlistId) {
    const playlist = playlists.find(p => p.id_плейлиста === playlistId);
    if (!playlist) return;

    document.getElementById('modalTitle').textContent = 'Редактировать плейлист';
    document.getElementById('recordId').value = playlistId;
    document.getElementById('playlistName').value = playlist.название;
    document.getElementById('playlistDescription').value = playlist.описание || '';
    document.getElementById('coverFile').value = '';
    document.getElementById('coverUrl').value = '';

    const preview = document.getElementById('coverPreview');
    if (playlist.обложка) {
        preview.src = playlist.обложка.startsWith('http') || playlist.обложка.startsWith('images/')
            ? playlist.обложка
            : 'images/' + playlist.обложка;
    } else {
        preview.src = 'images/default-playlist.jpg';
    }

    openModal(document.getElementById('playlistModal'));
}

// Открытие модального окна удаления
function openDeleteConfirmModal(playlist) {
    document.getElementById('deletePlaylistName').textContent = `"${playlist.название}"`;
    openModal(document.getElementById('deleteConfirmModal'));
}

// Сохранение плейлиста
function savePlaylist() {
    const name = document.getElementById('playlistName').value;
    const description = document.getElementById('playlistDescription').value;
    const playlistId = document.getElementById('recordId')?.value;
    const isEditing = playlistId && playlistId !== '';

    if (!name) return showNotification('Введите название плейлиста', 'error');

    const formData = new FormData();
    formData.append('action', isEditing ? 'edit' : 'create');
    formData.append('name', name);
    formData.append('description', description);
    if (isEditing) formData.append('playlist_id', playlistId);

    const coverFile = document.getElementById('coverFile')?.files[0];
    if (coverFile) formData.append('cover', coverFile);

    const coverUrl = document.getElementById('coverUrl')?.value;
    if (coverUrl && !coverFile) formData.append('cover_url', coverUrl);

    fetch('playlist_actions.php', { method: 'POST', body: formData })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification(data.message, 'success');

                if (isEditing) {
                    const index = playlists.findIndex(p => p.id_плейлиста == playlistId);
                    if (index !== -1) {
                        playlists[index].название = name;
                        playlists[index].описание = description;
                        if (data.playlist?.обложка) playlists[index].обложка = data.playlist.обложка;
                    }
                    if (currentPlaylist?.id_плейлиста == playlistId) {
                        document.getElementById('activePlaylistPanel').style.display = 'none';
                        currentPlaylist = null;
                    }
                } else if (data.playlist) {
                    playlists.push(data.playlist);
                }

                renderPlaylists();
                closeModal(document.getElementById('playlistModal'));
            }
        });
}

// Удаление плейлиста
function deletePlaylist() {
    if (!currentPlaylist) return;

    const formData = new FormData();
    formData.append('action', 'delete');
    formData.append('playlist_id', currentPlaylist.id_плейлиста);

    fetch('playlist_actions.php', { method: 'POST', body: formData })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification(data.message, 'success');
                playlists = playlists.filter(p => p.id_плейлиста !== currentPlaylist.id_плейлиста);
                renderPlaylists();
                document.getElementById('activePlaylistPanel').style.display = 'none';
                currentPlaylist = null;
                closeModal(document.getElementById('deleteConfirmModal'));
            }
        });
}

// Воспроизведение плейлиста
function playPlaylist(playlistId) {
    openPlaylist(playlistId);
}

// Открытие/закрытие модального окна
function openModal(modal) {
    modal?.classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closeModal(modal) {
    modal?.classList.remove('show');
    document.body.style.overflow = '';
}

// Предпросмотр обложки
window.previewCover = function (input) {
    const preview = document.getElementById('coverPreview');
    if (input.files?.[0]) {
        const reader = new FileReader();
        reader.onload = e => preview.src = e.target.result;
        reader.readAsDataURL(input.files[0]);
    }
};

// Обработчики событий
function initEventListeners() {
    document.getElementById('editAvatarBtn')?.addEventListener('click', () =>
        showNotification('Изменение аватара', 'info')
    );
}

// Прокрутка шапки
function initHeaderScroll() {
    window.addEventListener('scroll', () => {
        const header = document.getElementById('header');
        header.classList.toggle('header-scrolled', window.scrollY > 50);
    });
}

// Уведомления
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;

    const colors = { success: '#22c55e', error: '#ef4444', warning: '#fbbf24', info: '#9333ea' };
    const icons = { success: 'fa-check-circle', error: 'fa-exclamation-circle', warning: 'fa-exclamation-triangle', info: 'fa-info-circle' };

    notification.style.cssText = `
        position: fixed; top: 100px; right: 30px; background: rgba(20,20,30,0.95);
        backdrop-filter: blur(10px); border-left: 4px solid ${colors[type]};
        color: white; padding: 15px 25px; border-radius: 12px;
        display: flex; align-items: center; gap: 12px; z-index: 10001;
        box-shadow: 0 10px 30px rgba(0,0,0,0.3); animation: slideInRight 0.3s ease;
        max-width: 350px;
    `;

    notification.innerHTML = `
        <i class="fas ${icons[type]}" style="color: ${colors[type]}; font-size: 1.2rem;"></i>
        <span style="flex: 1;">${message}</span>
        <i class="fas fa-times" style="cursor: pointer; opacity: 0.7;"></i>
    `;

    document.body.appendChild(notification);

    notification.querySelector('.fa-times').onclick = () => {
        notification.style.animation = 'slideOutRight 0.3s ease forwards';
        setTimeout(() => notification.remove(), 300);
    };

    setTimeout(() => {
        notification.style.animation = 'slideOutRight 0.3s ease forwards';
        setTimeout(() => notification.remove(), 300);
    }, 4000);
}