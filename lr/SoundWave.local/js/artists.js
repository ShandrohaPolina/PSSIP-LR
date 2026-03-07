// Основной JavaScript для страницы артистов
document.addEventListener('DOMContentLoaded', function () {
    console.log('Artists.js loaded');

    // Инициализация компонентов
    initHeaderScroll();
    initAccordion();
    initFilters();
    initEventListeners();
    initDiscoverySection();
    initArtistButtons();

    // Устанавливаем начальные значения слайдера из URL
    const urlParams = new URLSearchParams(window.location.search);
    const minYear = urlParams.get('min_year') || '1950';
    const maxYear = urlParams.get('max_year') || '2025';

    document.getElementById('debutYearMin').value = minYear;
    document.getElementById('debutYearMax').value = maxYear;
    updateYearRange();
});

// Глобальные переменные
let currentArtists = typeof artistsData !== 'undefined' ? artistsData : [];

// Инициализация аккордеона фильтров
function initAccordion() {
    const filterHeaders = document.querySelectorAll('.filter-header');

    filterHeaders.forEach(header => {
        header.addEventListener('click', function (e) {
            e.preventDefault();

            // Переключаем активный класс
            this.classList.toggle('active');

            // Находим связанный контент
            const content = this.nextElementSibling;

            // Переключаем отображение контента
            if (content.style.display === 'block') {
                content.style.display = 'none';
            } else {
                content.style.display = 'block';
            }

            // Анимируем стрелку
            const icon = this.querySelector('.fa-chevron-down');
            if (icon) {
                icon.style.transform = this.classList.contains('active') ? 'rotate(180deg)' : 'rotate(0)';
            }
        });
    });
}

// Инициализация фильтров
function initFilters() {
    // Поиск по имени артиста через форму
    const searchForm = document.getElementById('searchForm');
    if (searchForm) {
        searchForm.addEventListener('submit', function (e) {
            e.preventDefault();
            submitFilters();
        });
    }

    // Поиск по странам
    const countrySearch = document.getElementById('countrySearch');
    if (countrySearch) {
        countrySearch.addEventListener('input', function () {
            filterCountryOptions(this.value.toLowerCase());
        });
    }

    // Слайдер года дебюта
    const debutYearMin = document.getElementById('debutYearMin');
    const debutYearMax = document.getElementById('debutYearMax');

    if (debutYearMin && debutYearMax) {
        [debutYearMin, debutYearMax].forEach(slider => {
            slider.addEventListener('input', function () {
                updateYearRange();
            });
        });
    }

    // Быстрые фильтры по декадам
    const debutQuickBtns = document.querySelectorAll('.year-quick-btn');
    debutQuickBtns.forEach(btn => {
        btn.addEventListener('click', function () {
            const decade = parseInt(this.dataset.year);
            document.getElementById('debutYearMin').value = decade;
            document.getElementById('debutYearMax').value = decade + 9;
            updateYearRange();
            submitFilters();
        });
    });

    // Обработка изменения чекбоксов стран
    document.querySelectorAll('#countryOptions input[type="checkbox"]').forEach(checkbox => {
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

// Фильтрация опций стран
function filterCountryOptions(searchTerm) {
    const options = document.querySelectorAll('#countryOptions .filter-option');
    options.forEach(option => {
        const label = option.querySelector('.option-label').textContent.toLowerCase();
        option.style.display = label.includes(searchTerm) ? 'flex' : 'none';
    });
}

// Обновление диапазона года дебюта
function updateYearRange() {
    const minYear = document.getElementById('debutYearMin');
    const maxYear = document.getElementById('debutYearMax');
    const minSpan = document.getElementById('minDebutYear');
    const maxSpan = document.getElementById('maxDebutYear');

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
    const searchInput = document.getElementById('artistNameSearch');

    // Добавляем поисковый запрос в форму
    if (searchInput && searchInput.value) {
        // Удаляем старый hidden input если есть
        let oldSearch = document.querySelector('input[name="search"]');
        if (oldSearch) oldSearch.remove();

        // Создаем новый
        const searchField = document.createElement('input');
        searchField.type = 'hidden';
        searchField.name = 'search';
        searchField.value = searchInput.value;
        filterForm.appendChild(searchField);
    }

    filterForm.submit();
}

// Применение фильтров (для обратной совместимости)
function applyFilters() {
    submitFilters();
}

// Сброс всех фильтров
function resetFilters() {
    window.location.href = 'artists.php';
}

// Показ подсказок поиска (отключено)
function showSearchSuggestions(searchTerm) {
    return;
}

// Инициализация кнопок артистов
function initArtistButtons() {
    // Кнопки "Подробнее"
    document.querySelectorAll('.view-details').forEach(button => {
        button.addEventListener('click', function () {
            const name = this.dataset.name;
            const bio = this.dataset.bio;
            openArtistBio(name, bio);
        });
    });

    // Кнопки "Слушать"
    document.querySelectorAll('.play-artist').forEach(button => {
        button.addEventListener('click', function () {
            const id = this.dataset.id;
            playArtist(id);
        });
    });
}

// Открытие модального окна с биографией
function openArtistBio(artistName, artistBio) {
    console.log('Открываем биографию:', artistName);
    document.getElementById('modalArtistName').textContent = artistName;
    document.getElementById('modalArtistBio').textContent = artistBio || 'Биография отсутствует.';
    document.getElementById('artistBioModal').classList.add('show');
    document.body.style.overflow = 'hidden';
}

// Закрытие модального окна
function closeBioModal() {
    document.getElementById('artistBioModal').classList.remove('show');
    document.body.style.overflow = '';
}

// Инициализация секции обнаружения
function initDiscoverySection() {
    const genresShowcase = document.getElementById('genresShowcase');
    const countriesShowcase = document.getElementById('countriesShowcase');

    if (genresShowcase) {
        const genres = [
            { name: 'Рок', icon: 'fa-guitar' },
            { name: 'Поп', icon: 'fa-microphone-alt' },
            { name: 'Хип-Хоп', icon: 'fa-headphones' },
            { name: 'Электроника', icon: 'fa-sliders-h' },
            { name: 'Джаз', icon: 'fa-saxophone' },
            { name: 'Классика', icon: 'fa-music' }
        ];

        genresShowcase.innerHTML = '';
        genres.forEach(genre => {
            const card = document.createElement('div');
            card.className = 'genre-showcase-card';
            card.innerHTML = `
                <i class="fas ${genre.icon}"></i>
                <h4>${genre.name}</h4>
                <p>Популярный жанр</p>
            `;
            genresShowcase.appendChild(card);
        });
    }

    if (countriesShowcase && typeof allCountries !== 'undefined') {
        countriesShowcase.innerHTML = '';
        const topCountries = allCountries.slice(0, 8);

        topCountries.forEach(country => {
            const count = countryCounts[country] || 0;
            const card = document.createElement('div');
            card.className = 'country-showcase-card';
            card.innerHTML = `
                <span style="font-size: 2rem; margin-bottom: 15px;">🌍</span>
                <h4>${country}</h4>
                <p>${count} артистов</p>
            `;
            countriesShowcase.appendChild(card);
        });
    }
}

// Инициализация обработчиков событий
function initEventListeners() {
    // Кнопка закрытия модального окна
    const closeBtn = document.getElementById('closeBioModal');
    if (closeBtn) {
        closeBtn.addEventListener('click', closeBioModal);
    }

    // Закрытие по клику вне окна
    const modal = document.getElementById('artistBioModal');
    if (modal) {
        modal.addEventListener('click', function (e) {
            if (e.target === this) {
                closeBioModal();
            }
        });
    }

    // Закрытие по клавише Escape
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            closeBioModal();
        }
    });

    // Кнопки в футере
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

// Функция прокрутки шапки
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

// Показ уведомлений
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

// Делаем функции глобально доступными
window.closeBioModal = closeBioModal;
window.playArtist = playArtist;