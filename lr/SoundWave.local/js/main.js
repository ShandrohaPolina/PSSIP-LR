// Основной JavaScript файл
document.addEventListener('DOMContentLoaded', function () {
    // Инициализация всех компонентов
    initHeaderScroll();
    // Убираем initCounters() - счетчики уже правильные из PHP
    initSlider();
    initEventListeners();
    initScrollAnimations();

    // Создаем точки для слайдера
    initSliderDots();
});

// Функция для эффекта прокрутки шапки
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

// Функция для инициализации точек слайдера
function initSliderDots() {
    const sliderDots = document.getElementById('sliderDots');
    if (!sliderDots) return;

    sliderDots.innerHTML = '';

    // Проверяем, что slideCount определен и больше 0
    if (typeof slideCount === 'undefined' || slideCount === 0) return;

    for (let i = 0; i < slideCount; i++) {
        const dot = document.createElement('div');
        dot.className = 'slider-dot';
        if (i === 0) dot.classList.add('active');
        dot.addEventListener('click', () => goToSlide(i));
        sliderDots.appendChild(dot);
    }
}

// Функция для инициализации слайдера
function initSlider() {
    const sliderTrack = document.getElementById('sliderTrack');
    const prevBtn = document.getElementById('prevSlide');
    const nextBtn = document.getElementById('nextSlide');

    if (!sliderTrack || !prevBtn || !nextBtn) return;

    let currentSlide = 0;
    const slides = document.querySelectorAll('.slider-slide');
    const totalSlides = slides.length;

    // Если нет слайдов, выходим
    if (totalSlides === 0) return;

    // Функции для управления слайдером
    function goToSlide(slideIndex) {
        if (slideIndex < 0) slideIndex = 0;
        if (slideIndex >= totalSlides) slideIndex = totalSlides - 1;

        currentSlide = slideIndex;
        sliderTrack.style.transform = `translateX(-${currentSlide * 100}%)`;

        // Обновление активной точки
        document.querySelectorAll('.slider-dot').forEach((dot, index) => {
            dot.classList.toggle('active', index === currentSlide);
        });
    }

    function nextSlide() {
        currentSlide = (currentSlide + 1) % totalSlides;
        goToSlide(currentSlide);
    }

    function prevSlide() {
        currentSlide = (currentSlide - 1 + totalSlides) % totalSlides;
        goToSlide(currentSlide);
    }

    // Автоматическая смена слайдов
    let slideInterval = setInterval(nextSlide, 5000);

    // Остановка автосмены при наведении
    sliderTrack.addEventListener('mouseenter', () => clearInterval(slideInterval));
    sliderTrack.addEventListener('mouseleave', () => {
        slideInterval = setInterval(nextSlide, 5000);
    });

    // Назначение обработчиков кнопок
    nextBtn.addEventListener('click', () => {
        clearInterval(slideInterval);
        nextSlide();
        slideInterval = setInterval(nextSlide, 5000);
    });

    prevBtn.addEventListener('click', () => {
        clearInterval(slideInterval);
        prevSlide();
        slideInterval = setInterval(nextSlide, 5000);
    });

    // Делаем функцию goToSlide глобальной для доступа из точек
    window.goToSlide = goToSlide;
}

// Функция для инициализации обработчиков событий
function initEventListeners() {
    const searchBtn = document.querySelector('.search-btn');
    if (searchBtn) {
        searchBtn.addEventListener('click', function () {
            document.querySelector('.search-input').focus();
        });
    }
}

// Функция для инициализации анимаций при скролле
function initScrollAnimations() {
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('fade-in');
                observer.unobserve(entry.target);
            }
        });
    }, observerOptions);

    // Наблюдаем за элементами с задержкой анимации
    document.querySelectorAll('.album-card, .chart-item, .genre-card').forEach((el, index) => {
        el.classList.remove('fade-in');
        setTimeout(() => {
            observer.observe(el);
        }, index * 100);
    });
}

// Глобальные функции для кнопок
window.playAlbum = function (albumId, albumName) {
    alert(`🎵 Начинаем воспроизведение альбома: "${albumName || 'Альбом #' + albumId}"`);
};

window.addToPlaylist = function (albumId, albumName) {
    alert(`✅ Альбом "${albumName || 'Альбом #' + albumId}" добавлен в ваш плейлист!`);
};

window.likeAlbum = function (albumId, albumName) {
    alert(`❤️ Альбом "${albumName || 'Альбом #' + albumId}" добавлен в избранное!`);
};

window.addAlbumToPlaylist = function (albumId, albumName) {
    alert(`➕ Альбом "${albumName || 'Альбом #' + albumId}" добавлен в плейлист!`);
};

window.playSong = function (songId, songName) {
    alert(`🎵 Воспроизведение: "${songName || 'Песня #' + songId}"`);
};