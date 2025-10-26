
//Открытие меню бургера 
function toggleMenu() {
    const sidebar = document.getElementById('sidebar');
    if (sidebar.style.left === '0px') {
        sidebar.style.left = '-500px'; // Скрыть меню
    } else {
        sidebar.style.left = '0px'; // Показать меню
    }
}

// Наверх
function scrollToTop() {
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

// Функция для открытия изображения в новом окне
function openImageWindow(src) {
    var image = new Image();
    image.src = src;
    image.onload = function () {
        var width = image.width;
        var height = image.height;
        window.open(src, "Image", "width=" + width + ",height=" + height);
    };
}

// события мыши
// Добавляем обработчик события на все картинки
document.querySelectorAll('.clickable-image').forEach(function (image) {
    image.addEventListener('click', function () {
        openImageWindow(this.src);
    });
});


// Слайдер для "О магазине" в телефоне
document.addEventListener('DOMContentLoaded', function () {
    const images = [
        'Image/image 161.png',
        'Image/image 163.png',
        'Image/image 162.png',
        'Image/image 115.png'
    ];
    const imageElement = document.querySelector('.image-slider');
    const prevButton = document.querySelector('.pre-but');
    const nextButton = document.querySelector('.ne-but');
    let currentIndex = 0;

    // Функция для обновления изображения
    function updateImage() {
        imageElement.src = images[currentIndex];
    }
    // Обработчик для кнопки "предыдущая"
    prevButton.addEventListener('click', function (event) {
        event.preventDefault();
        currentIndex = (currentIndex > 0) ? currentIndex - 1 : images.length - 1;
        updateImage();
    });
    // Обработчик для кнопки "следующая"
    nextButton.addEventListener('click', function (event) {
        event.preventDefault();
        currentIndex = (currentIndex < images.length - 1) ? currentIndex + 1 : 0;
        updateImage();
    });
    // Инициализация слайдера
    updateImage();
});

// document.addEventListener('DOMContentLoaded', function () {
//     const images = document.querySelectorAll('.image-container');
//     const prevButton = document.querySelector('.prev-button');
//     const nextButton = document.querySelector('.next-button');
//     let currentIndex = 0;

//     // Функция для обновления видимых изображений
//     function updateImages() {
//         images.forEach((image, index) => {
//             if (index === currentIndex || index === (currentIndex + 1) % images.length || index === (currentIndex + 2) % images.length) {
//                 image.style.display = 'block'; // Показываем текущее и следующие два изображения
//             } else {
//                 image.style.display = 'none'; // Скрываем остальные изображения
//             }
//         });
//     }

//     // Обработчик для кнопки "предыдущая"
//     prevButton.addEventListener('click', function (event) {
//         event.preventDefault();
//         currentIndex = (currentIndex - 1 + images.length) % images.length; // Переход к предыдущему изображению
//         updateImages();
//     });

//     // Обработчик для кнопки "следующая"
//     nextButton.addEventListener('click', function (event) {
//         event.preventDefault();
//         currentIndex = (currentIndex + 1) % images.length; // Переход к следующему изображению
//         updateImages();
//     });

//     // Инициализация слайдера
//     updateImages();
// });

// Вызов звонка с pointer events
const phone = document.querySelectorAll('.btn');
phone.forEach(function (phoneNumberElement) {
    // Сохраняем оригинальный click для совместимости
    phoneNumberElement.addEventListener('click', function (event) {
        event.preventDefault();
        const phoneNumber = "+73952482805";
        window.location.href = 'tel:' + phoneNumber;
    });

    // Добавляем pointer event
    phoneNumberElement.addEventListener('pointerdown', function (event) {
        // Визуальная обратная связь при нажатии
        phoneNumberElement.style.transform = 'scale(0.9)';
    });

    phoneNumberElement.addEventListener('pointerup', function (event) {
        phoneNumberElement.style.transform = 'scale(1)';
    });
});


// события с таймером
// Реклама
window.addEventListener('load', function () {
    setInterval(function () {
        var ads = [
            "Узнайте про новые товары на складе!",
            "Распродажа! Успейте, пока не раскупили!",
            "Оформите заказ прямо сейчас и получите бесплатную доставку!",
            "Не упустите шанс получить красивую плитку для уюта в доме!"];
        var randomAd = ads[Math.floor(Math.random() * ads.length)];
        alert(randomAd);
    },
        300000);
});


// Анимация блока Услуги
function animateBlock(block) {
    // Добавляем класс с градиентом
    block.classList.add('block-gradient');
    setTimeout(() => {
        block.classList.remove('block-gradient');
    }, 2000); // Время в миллисекундах
}

// события мыши
// Подсказки
const tooltip = document.getElementById('tooltip');
const items = document.querySelectorAll('.block-2 li');
items.forEach(item => {
    item.addEventListener('mouseenter', function (event) {
        const tooltipText = item.getAttribute('data-tooltip');
        tooltip.textContent = tooltipText;
        // Позиционирование подсказки
        const rect = item.getBoundingClientRect();
        tooltip.style.left = (rect.left + window.scrollX) + 'px';
        tooltip.style.top = (rect.bottom + window.scrollY + 5) + 'px';
        tooltip.style.display = 'block'; // Показываем подсказку
    });
    item.addEventListener('mouseleave', function () {
        tooltip.style.display = 'none'; // Скрываем подсказку
    });
});


// Поле поиска
const products = [
    "MONO",
    "Simple",
    "Монте Тиберио",
    "Керамогранит Creto Forza Calacatta white PG 01 45x45",
    "Плитка Creto Aura Light Grey W M NR Mat 1 31x61",
    "Плитка Creto Effetto Base Grey Wall 01 25x60",
    "Серебро",
    "Золото",
    "Металлик"
];
const searchInput = document.querySelector('.poisk');
// Создание контейнера для предложений
const suggestionsContainer = document.createElement('div');
suggestionsContainer.classList.add('suggestions');
suggestionsContainer.style.position = 'absolute';
suggestionsContainer.style.zIndex = '100';
suggestionsContainer.style.backgroundColor = 'white';
suggestionsContainer.style.border = '1px solid #ccc';
suggestionsContainer.style.maxHeight = '150px';
suggestionsContainer.style.overflowY = 'auto';
suggestionsContainer.style.width = searchInput.offsetWidth + 'px';
suggestionsContainer.style.display = 'none'; // Скрываем контейнер по умолчанию
// Вставляем контейнер под полем ввода
searchInput.parentNode.insertBefore(suggestionsContainer, searchInput.nextSibling);
// Функция для отображения предложений
function showSuggestions(value) {
    suggestionsContainer.innerHTML = '';
    if (value.length === 0) {
        suggestionsContainer.style.display = 'none'; // Скрываем контейнер, если поле пустое
        return;
    }
    const filteredProducts = products.filter(product =>
        product.toLowerCase().includes(value.toLowerCase())
    );
    if (filteredProducts.length > 0) {
        suggestionsContainer.style.display = 'block'; // Показываем контейнер, если есть предложения
    } else {
        suggestionsContainer.style.display = 'none'; // Скрываем, если нет предложений
    }
    filteredProducts.forEach(product => {
        const suggestionItem = document.createElement('div');
        suggestionItem.textContent = product;
        suggestionItem.classList.add('suggestion-item');
        suggestionItem.style.padding = '10px';
        suggestionItem.style.cursor = 'pointer';
        // Обработчик клика по предложению
        suggestionItem.addEventListener('click', () => {
            searchInput.value = product; // Заполняем поле выбранным вариантом
            suggestionsContainer.innerHTML = ''; // Очищаем предложения
            suggestionsContainer.style.display = 'none'; // Скрываем контейнер
        });
        suggestionsContainer.appendChild(suggestionItem);
    });
}

// события клавиатуры
// Обработчик события ввода
searchInput.addEventListener('input', (event) => {
    const value = event.target.value;
    showSuggestions(value);
});
// Закрытие предложений при клике вне поля ввода
document.addEventListener('click', (event) => {
    if (!searchInput.contains(event.target) && !suggestionsContainer.contains(event.target)) {
        suggestionsContainer.innerHTML = ''; // Очищаем предложения
        suggestionsContainer.style.display = 'none'; // Скрываем контейнер
    }
});
// Установка позиции контейнера под полем поиска
const adjustSuggestionsPosition = () => {
    const rect = searchInput.getBoundingClientRect();
    // suggestionsContainer.style.top = ${ rect.bottom + window.scrollY } px; // Позиционируем под полем ввода
    // suggestionsContainer.style.left = ${ rect.left } px; // Выравниваем по левому краю поля ввода
    suggestionsContainer.style.left = (rect.left + window.scrollX) + 'px';
    suggestionsContainer.style.top = (rect.bottom + window.scrollY + 5) + 'px';
};
// Обновление позиции при изменении размера окна
window.addEventListener('resize', adjustSuggestionsPosition);
searchInput.addEventListener('focus', adjustSuggestionsPosition); // Устанавливаем позицию при фокусе на поле




// Увеличение блоков
document.querySelectorAll('.katalog > div').forEach(function (block) {
    block.addEventListener('mouseenter', function () {
        block.classList.add('scale-up'); // Добавляем класс для увеличения
    });
    block.addEventListener('mouseleave', function () {
        block.classList.remove('scale-up'); // Убираем класс увеличения
    });
});



