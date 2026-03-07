// Основной JavaScript для страницы музыки
document.addEventListener('DOMContentLoaded', function () {
    console.log('Music.js loaded');

    initHeaderScroll();
    initAccordion();
    initFilters();
    initEventListeners();
    initSongButtons();

    const urlParams = new URLSearchParams(window.location.search);
});

function initAccordion() {
    const filterHeaders = document.querySelectorAll('.filter-header');

    filterHeaders.forEach(header => {
        header.addEventListener('click', function (e) {
            e.preventDefault();
            this.classList.toggle('active');
            const content = this.nextElementSibling;

            if (content.style.display === 'block') {
                content.style.display = 'none';
            } else {
                content.style.display = 'block';
            }

            const icon = this.querySelector('.fa-chevron-down');
            if (icon) {
                icon.style.transform = this.classList.contains('active') ? 'rotate(180deg)' : 'rotate(0)';
            }
        });
    });
}

function initFilters() {
    const searchForm = document.getElementById('searchForm');
    if (searchForm) {
        searchForm.addEventListener('submit', function (e) {
            e.preventDefault();
            submitFilters();
        });
    }

    const genreSearch = document.getElementById('genreSearch');
    if (genreSearch) {
        genreSearch.addEventListener('input', function () {
            filterOptions(this.value.toLowerCase(), '#genreOptions .filter-option');
        });
    }

    const artistSearch = document.getElementById('artistSearch');
    if (artistSearch) {
        artistSearch.addEventListener('input', function () {
            filterOptions(this.value.toLowerCase(), '#artistOptions .filter-option');
        });
    }

    const albumSearch = document.getElementById('albumSearch');
    if (albumSearch) {
        albumSearch.addEventListener('input', function () {
            filterOptions(this.value.toLowerCase(), '#albumOptions .filter-option');
        });
    }

    document.querySelectorAll('#genreOptions input[type="checkbox"], #artistOptions input[type="checkbox"], #albumOptions input[type="checkbox"]').forEach(checkbox => {
        checkbox.addEventListener('change', function () {
            submitFilters();
        });
    });

    const clearFiltersBtn = document.getElementById('clearFilters');
    if (clearFiltersBtn) {
        clearFiltersBtn.addEventListener('click', resetFilters);
    }
}

function filterOptions(searchTerm, selector) {
    const options = document.querySelectorAll(selector);
    options.forEach(option => {
        const label = option.querySelector('.option-label').textContent.toLowerCase();
        option.style.display = label.includes(searchTerm) ? 'flex' : 'none';
    });
}

function submitFilters() {
    const filterForm = document.getElementById('filterForm');
    const searchInput = document.getElementById('songNameSearch');

    if (searchInput && searchInput.value) {
        let oldSearch = document.querySelector('input[name="search"]');
        if (oldSearch) oldSearch.remove();

        const searchField = document.createElement('input');
        searchField.type = 'hidden';
        searchField.name = 'search';
        searchField.value = searchInput.value;
        filterForm.appendChild(searchField);
    }

    filterForm.submit();
}

function resetFilters() {
    window.location.href = 'music.php';
}

function initSongButtons() {
    document.querySelectorAll('.play-song').forEach(button => {
        button.addEventListener('click', function () {
            const id = this.dataset.id;
            playSong(id);
        });
    });

    document.querySelectorAll('.add-to-playlist').forEach(button => {
        button.addEventListener('click', function () {
            const id = this.dataset.id;
            const title = this.dataset.title;
            const artist = this.dataset.artist;
            addToPlaylist(id, title, artist);
        });
    });

    document.querySelectorAll('.view-lyrics').forEach(button => {
        button.addEventListener('click', function () {
            const title = this.dataset.title;
            const artist = this.dataset.artist;
            const lyrics = this.dataset.lyrics;
            showLyrics(title, artist, lyrics);
        });
    });
}

function addToPlaylist(songId, title, artist) {
    console.log('Добавляем в плейлист:', title);

    if (!isUserLoggedIn) {
        showNotification('Для добавления в плейлист необходимо войти', 'warning');
        setTimeout(() => window.location.href = 'login.php', 1500);
        return;
    }

    if (typeof userPlaylists === 'undefined' || userPlaylists.length === 0) {
        showNotification('У вас нет плейлистов. Создайте плейлист в профиле', 'warning');
        return;
    }

    showPlaylistSelector(songId, title, artist);
}

function showPlaylistSelector(songId, songTitle, songArtist) {
    let modal = document.getElementById('playlistSelectorModal');

    if (!modal) {
        modal = document.createElement('div');
        modal.className = 'modal';
        modal.id = 'playlistSelectorModal';

        modal.innerHTML = `
            <div class="modal-content" style="max-width: 500px;">
                <div class="modal-header">
                    <h3>Выберите плейлист</h3>
                    <button class="modal-close" onclick="closePlaylistSelector()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="modal-body">
                    <p style="color: #b0b0b0; margin-bottom: 20px;">
                        Добавить "<span id="selectedSongTitle"></span>" - <span id="selectedSongArtist"></span>
                    </p>
                    <div class="playlists-list" id="playlistsList"></div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-outline" onclick="closePlaylistSelector()">Отмена</button>
                </div>
            </div>
        `;

        document.body.appendChild(modal);

        const style = document.createElement('style');
        style.textContent = `
            .playlists-list { max-height: 300px; overflow-y: auto; padding: 10px 0; }
            .playlist-select-item {
                display: flex; align-items: center; gap: 15px; padding: 12px 15px;
                background: rgba(30,30,40,0.7); border-radius: 10px; margin-bottom: 10px;
                cursor: pointer; transition: all 0.3s ease; border: 1px solid transparent;
            }
            .playlist-select-item:hover {
                background: rgba(147,51,234,0.2); border-color: #fbbf24; transform: translateX(5px);
            }
            .playlist-select-cover {
                width: 50px; height: 50px; border-radius: 8px; object-fit: cover;
                border: 2px solid #9333ea;
            }
            .playlist-select-info { flex: 1; }
            .playlist-select-name { color: #f0f0f0; font-weight: 600; margin-bottom: 3px; }
            .playlist-select-count { color: #b0b0b0; font-size: 0.85rem; }
            .playlist-select-icon { color: #fbbf24; font-size: 1.2rem; }
        `;
        document.head.appendChild(style);
    }

    document.getElementById('selectedSongTitle').textContent = songTitle;
    document.getElementById('selectedSongArtist').textContent = songArtist;

    const playlistsList = document.getElementById('playlistsList');
    playlistsList.innerHTML = '<div style="text-align: center; padding: 20px; color: #888;">Загрузка...</div>';

    setTimeout(() => {
        if (userPlaylists.length > 0) {
            playlistsList.innerHTML = '';
            userPlaylists.forEach(playlist => {
                const item = document.createElement('div');
                item.className = 'playlist-select-item';
                item.dataset.id = playlist.id_плейлиста;

                let coverPath = playlist.обложка || 'images/default-playlist.jpg';
                if (coverPath && !coverPath.startsWith('http') && !coverPath.startsWith('images/')) {
                    coverPath = 'images/' + coverPath;
                }

                item.innerHTML = `
                    <img src="${coverPath}" alt="${playlist.название}" class="playlist-select-cover" onerror="this.src='images/default-playlist.jpg'">
                    <div class="playlist-select-info">
                        <div class="playlist-select-name">${playlist.название}</div>
                        <div class="playlist-select-count">${playlist.tracksCount || 0} треков</div>
                    </div>
                    <i class="fas fa-plus-circle playlist-select-icon"></i>
                `;

                item.addEventListener('click', () => {
                    addSongToPlaylist(playlist.id_плейлиста, songId, playlist.название);
                });

                playlistsList.appendChild(item);
            });
        } else {
            playlistsList.innerHTML = '<div style="text-align: center; padding: 20px; color: #888;">У вас нет плейлистов. <a href="profile.php" style="color: #fbbf24;">Создать плейлист</a></div>';
        }
    }, 100);

    modal.classList.add('show');
    document.body.style.overflow = 'hidden';
}

function addSongToPlaylist(playlistId, songId, playlistName) {
    const formData = new FormData();
    formData.append('action', 'add_song');
    formData.append('playlist_id', playlistId);
    formData.append('song_id', songId);

    fetch('playlist_actions.php', { method: 'POST', body: formData })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification(`Песня добавлена в плейлист "${playlistName}"`, 'success');
                closePlaylistSelector();
            } else {
                showNotification(data.error || 'Ошибка при добавлении', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Ошибка при добавлении в плейлист', 'error');
        });
}

function closePlaylistSelector() {
    const modal = document.getElementById('playlistSelectorModal');
    if (modal) {
        modal.classList.remove('show');
        document.body.style.overflow = '';
    }
}

function playSong(songId) {
    const song = songsData.find(s => s.id_песни == songId);
    showNotification(`🎵 Слушаем: ${song?.название || 'Песня'}`, 'success');
}

function showLyrics(title, artist, lyrics) {
    document.getElementById('modalSongTitle').textContent = title;
    document.getElementById('modalSongArtist').textContent = artist;
    document.getElementById('modalSongLyrics').textContent = lyrics || 'Текст песни отсутствует.';
    document.getElementById('lyricsModal').classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closeLyricsModal() {
    document.getElementById('lyricsModal').classList.remove('show');
    document.body.style.overflow = '';
}

function initEventListeners() {
    const closeBtn = document.getElementById('closeLyricsModal');
    if (closeBtn) {
        closeBtn.addEventListener('click', closeLyricsModal);
    }

    const modal = document.getElementById('lyricsModal');
    if (modal) {
        modal.addEventListener('click', function (e) {
            if (e.target === this) closeLyricsModal();
        });
    }

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            closeLyricsModal();
            closePlaylistSelector();
        }
    });

    const adminBtn = document.getElementById('adminBtn');
    if (adminBtn) {
        adminBtn.addEventListener('click', () => window.location.href = 'admin/login.php');
    }

    const authBtn = document.getElementById('authBtn');
    if (authBtn) {
        authBtn.addEventListener('click', () => window.location.href = 'login.php');
    }
}

function initHeaderScroll() {
    window.addEventListener('scroll', function () {
        const header = document.getElementById('header');
        header.classList.toggle('header-scrolled', window.scrollY > 50);
    });
}

function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;

    const colors = { success: '#22c55e', error: '#ef4444', info: '#9333ea', warning: '#fbbf24' };
    const icons = { success: 'fa-check-circle', error: 'fa-exclamation-circle', info: 'fa-info-circle', warning: 'fa-exclamation-triangle' };

    notification.style.cssText = `
        position: fixed; top: 100px; right: 30px; background: rgba(20,20,30,0.95);
        backdrop-filter: blur(10px); border-left: 4px solid ${colors[type]}; color: white;
        padding: 15px 25px; border-radius: 12px; display: flex; align-items: center;
        gap: 12px; z-index: 10001; box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        animation: slideInRight 0.3s ease; max-width: 350px;
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

window.closeLyricsModal = closeLyricsModal;
window.playSong = playSong;
window.closePlaylistSelector = closePlaylistSelector;