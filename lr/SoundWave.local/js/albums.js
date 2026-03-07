// Основной JavaScript для страницы альбомов
document.addEventListener('DOMContentLoaded', function () {
    console.log('Albums.js loaded');

    // Инициализация компонентов
    initHeaderScroll();
    initAccordion();
    initFilters();
    initEventListeners();
    initAlbumButtons();

    // Устанавливаем начальные значения слайдера из URL
    const urlParams = new URLSearchParams(window.location.search);
    const minYear = urlParams.get('min_year') || '1950';
    const maxYear = urlParams.get('max_year') || '2025';

    document.getElementById('yearMin').value = minYear;
    document.getElementById('yearMax').value = maxYear;
    updateYearRange();
});

// Глобальные переменные
let currentAlbums = typeof albumsData !== 'undefined' ? albumsData : [];

// Инициализация аккордеона фильтров
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

// Инициализация фильтров
function initFilters() {
    // Поиск по названию альбома
    const searchForm = document.getElementById('searchForm');
    if (searchForm) {
        searchForm.addEventListener('submit', function (e) {
            e.preventDefault();
            submitFilters();
        });
    }

    // Поиск по артистам
    const artistSearch = document.getElementById('artistSearch');
    if (artistSearch) {
        artistSearch.addEventListener('input', function () {
            filterArtistOptions(this.value.toLowerCase());
        });
    }

    // Слайдер года выпуска
    const yearMin = document.getElementById('yearMin');
    const yearMax = document.getElementById('yearMax');

    if (yearMin && yearMax) {
        [yearMin, yearMax].forEach(slider => {
            slider.addEventListener('input', function () {
                updateYearRange();
            });
        });
    }

    // Быстрые фильтры по декадам
    const yearQuickBtns = document.querySelectorAll('.year-quick-btn');
    yearQuickBtns.forEach(btn => {
        btn.addEventListener('click', function () {
            const decade = parseInt(this.dataset.year);
            document.getElementById('yearMin').value = decade;
            document.getElementById('yearMax').value = decade + 9;
            updateYearRange();
            submitFilters();
        });
    });

    // Обработка изменения чекбоксов артистов
    document.querySelectorAll('#artistOptions input[type="checkbox"]').forEach(checkbox => {
        checkbox.addEventListener('change', function () {
            submitFilters();
        });
    });

    // Кнопка сброса фильтров
    const clearFiltersBtn = document.getElementById('clearFilters');
    if (clearFiltersBtn) {
        clearFiltersBtn.addEventListener('click', resetFilters);
    }
}

// Фильтрация опций артистов
function filterArtistOptions(searchTerm) {
    const options = document.querySelectorAll('#artistOptions .filter-option');
    options.forEach(option => {
        const label = option.querySelector('.option-label').textContent.toLowerCase();
        option.style.display = label.includes(searchTerm) ? 'flex' : 'none';
    });
}

// Обновление диапазона лет
function updateYearRange() {
    const minYear = document.getElementById('yearMin');
    const maxYear = document.getElementById('yearMax');
    const minSpan = document.getElementById('minYear');
    const maxSpan = document.getElementById('maxYear');

    if (minYear && maxYear && minSpan && maxSpan) {
        if (parseInt(minYear.value) > parseInt(maxYear.value)) {
            minYear.value = maxYear.value;
        }
        minSpan.textContent = minYear.value;
        maxSpan.textContent = maxYear.value;
    }
}

// Отправка формы с фильтрами
function submitFilters() {
    const filterForm = document.getElementById('filterForm');
    const searchInput = document.getElementById('albumNameSearch');

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

// Сброс всех фильтров
function resetFilters() {
    window.location.href = 'albums.php';
}

// Инициализация кнопок альбомов
function initAlbumButtons() {
    document.querySelectorAll('.view-details').forEach(button => {
        button.addEventListener('click', function () {
            const name = this.dataset.name;
            const artist = this.dataset.artist;
            const year = this.dataset.year;
            const date = this.dataset.date;
            const description = this.dataset.description;
            openAlbumDetails(name, artist, year, date, description);
        });
    });

    document.querySelectorAll('.play-album').forEach(button => {
        button.addEventListener('click', function () {
            const id = this.dataset.id;
            playAlbum(id);
        });
    });
}

function openAlbumDetails(albumName, artistName, year, date, description) {
    console.log('Открываем детали альбома:', albumName);
    document.getElementById('modalAlbumName').textContent = albumName;
    document.getElementById('modalAlbumArtist').textContent = artistName;
    document.getElementById('modalAlbumDate').textContent = `${date} (${year})`;
    document.getElementById('modalAlbumDescription').textContent = description || 'Описание отсутствует.';
    document.getElementById('albumBioModal').classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closeBioModal() {
    document.getElementById('albumBioModal').classList.remove('show');
    document.body.style.overflow = '';
}

function playAlbum(albumId) {
    const album = currentAlbums.find(a => a.id_альбома == albumId);
    showNotification(`🎵 Слушаем альбом: ${album?.название || 'Альбом'}`, 'success');
}

function initEventListeners() {
    const closeBtn = document.getElementById('closeBioModal');
    if (closeBtn) {
        closeBtn.addEventListener('click', closeBioModal);
    }

    const modal = document.getElementById('albumBioModal');
    if (modal) {
        modal.addEventListener('click', function (e) {
            if (e.target === this) {
                closeBioModal();
            }
        });
    }

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            closeBioModal();
        }
    });

    const adminBtn = document.getElementById('adminBtn');
    if (adminBtn) {
        adminBtn.addEventListener('click', function () {
            window.location.href = 'admin/login.php';
        });
    }

    const authBtn = document.getElementById('authBtn');
    if (authBtn) {
        authBtn.addEventListener('click', function () {
            window.location.href = 'login.php';
        });
    }
}

function initHeaderScroll() {
    window.addEventListener('scroll', function () {
        const header = document.getElementById('header');
        if (window.scrollY > 50) {
            header.classList.add('header-scrolled');
        } else {
            header.classList.remove('header-scrolled');
        }
    });
}

function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;

    let icon = 'fa-info-circle';
    let color = '#9333ea';

    if (type === 'success') {
        icon = 'fa-check-circle';
        color = '#22c55e';
    } else if (type === 'error') {
        icon = 'fa-exclamation-circle';
        color = '#ef4444';
    }

    notification.style.cssText = `
        position: fixed;
        top: 100px;
        right: 30px;
        background: rgba(20, 20, 30, 0.95);
        backdrop-filter: blur(10px);
        border-left: 4px solid ${color};
        color: white;
        padding: 15px 25px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        gap: 12px;
        z-index: 10001;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        animation: slideInRight 0.3s ease;
        max-width: 350px;
    `;

    notification.innerHTML = `
        <i class="fas ${icon}" style="color: ${color}; font-size: 1.2rem;"></i>
        <span style="flex: 1;">${message}</span>
        <i class="fas fa-times" style="cursor: pointer; opacity: 0.7; margin-left: 10px;"></i>
    `;

    document.body.appendChild(notification);

    notification.querySelector('.fa-times').addEventListener('click', () => {
        notification.style.animation = 'slideOutRight 0.3s ease forwards';
        setTimeout(() => notification.remove(), 300);
    });

    setTimeout(() => {
        if (notification.parentNode) {
            notification.style.animation = 'slideOutRight 0.3s ease forwards';
            setTimeout(() => notification.remove(), 300);
        }
    }, 4000);
}

window.closeBioModal = closeBioModal;
window.playAlbum = playAlbum;