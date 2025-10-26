// Задание 1: Получение данных из полей формы
document.getElementById('userForm').addEventListener('submit', function (e) {
    e.preventDefault();

    // Проверка валидности формы (Задание 2)
    if (validateForm()) {
        // Получение данных из формы
        const formData = getFormData();

        // Вывод данных в alert
        displayFormDataInAlert(formData);

    }
});

// Функция получения данных из формы
function getFormData() {
    const form = document.getElementById('userForm');
    const formData = new FormData(form);

    // Получаем значения из полей формы
    const data = {
        fullName: formData.get('fullName'),
        email: formData.get('email'),
        phone: formData.get('phone'),
        interests: [],
        age: formData.get('age'),
        country: formData.get('country'),
        comments: formData.get('comments')
    };

    // Получаем выбранные интересы (чекбоксы)
    const interestsCheckboxes = form.querySelectorAll('input[name="interests"]:checked');
    interestsCheckboxes.forEach(function (checkbox) {
        data.interests.push(checkbox.value);
    });

    return data;
}

// Функция отображения данных формы в alert
function displayFormDataInAlert(data) {
    let message = "Данные формы:\n\n";
    message += "ФИО: " + data.fullName + "\n";
    message += "Email: " + data.email + "\n";
    message += "Телефон: " + data.phone + "\n";
    message += "Интересы: " + (data.interests.length > 0 ? data.interests.join(', ') : 'Не выбрано') + "\n";
    message += "Возраст: " + (data.age || 'Не указан') + "\n";
    message += "Страна: " + (data.country || 'Не выбрана') + "\n";
    message += "Комментарий: " + (data.comments || 'Нет комментариев');

    alert(message);
}


// Задание 2: Валидация формы
function validateForm() {
    let isValid = true;

    // Валидация HTML5 + JS для ФИО
    const fullName = document.getElementById('fullName');
    const fullNameError = document.getElementById('fullNameError');

    if (!fullName.value.trim()) {
        showError(fullName, fullNameError, "Поле ФИО обязательно для заполнения");
        isValid = false;
    } else if (!/^[А-ЯЁа-яё]+\s[А-ЯЁа-яё]+\s[А-ЯЁа-яё]+$/.test(fullName.value.trim())) {
        showError(fullName, fullNameError, "Введите ФИО в формате: Фамилия Имя Отчество");
        isValid = false;
    } else {
        hideError(fullName, fullNameError);
    }

    // Валидация HTML5 + JS для email
    const email = document.getElementById('email');
    const emailError = document.getElementById('emailError');

    if (!email.value.trim()) {
        showError(email, emailError, "Поле Email обязательно для заполнения");
        isValid = false;
    } else if (!isValidEmail(email.value)) {
        showError(email, emailError, "Введите корректный email адрес");
        isValid = false;
    } else {
        hideError(email, emailError);
    }
    // Валидация HTML5 + JS для телефона
    const phone = document.getElementById('phone');
    const phoneError = document.getElementById('phoneError');

    if (!phone.value.trim()) {
        showError(phone, phoneError, "Поле Телефон обязательно для заполнения");
        isValid = false;
    } else if (!isValidPhone(phone.value)) {
        showError(phone, phoneError, "Введите телефон в формате: +375(XX)XXX-XX-XX");
        isValid = false;
    } else {
        hideError(phone, phoneError);
    }

    // Валидация JS для возраста (радиокнопки)
    const ageSelected = document.querySelector('input[name="age"]:checked');
    const ageError = document.getElementById('ageError');

    if (!ageSelected) {
        showAgeError("Пожалуйста, выберите возрастную категорию");
        isValid = false;
    } else {
        hideAgeError();
    }

    return isValid;
}

// Функции для отображения/скрытия ошибок
function showError(input, errorElement, message) {
    errorElement.textContent = message;
    errorElement.style.display = 'block';
    input.style.borderColor = '#e74c3c';
}

function hideError(input, errorElement) {
    errorElement.style.display = 'none';
    input.style.borderColor = '#e5c9eb';
}

function showAgeError(message) {
    const ageError = document.getElementById('ageError');
    ageError.textContent = message;
    ageError.style.display = 'block';
}

function hideAgeError() {
    const ageError = document.getElementById('ageError');
    ageError.style.display = 'none';
}

// Функции проверки с использованием регулярных выражений
function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

function isValidPhone(phone) {
    const phoneRegex = /^\+375\(\d{2}\)\d{3}-\d{2}-\d{2}$/;
    return phoneRegex.test(phone);
}

// Задание 3: Работа с регулярными выражениями
document.getElementById('testEmailBtn').addEventListener('click', function () {
    const testText = document.getElementById('regexTest').value;
    const resultElement = document.getElementById('regexResult');

    if (!testText) {
        resultElement.innerHTML = '<span class="failure">Введите текст для проверки</span>';
        return;
    }

    // Использование метода test()
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    const isEmail = emailRegex.test(testText);

    // Использование метода match()
    const matchResult = testText.match(emailRegex);

    let resultMessage = "Текст: " + testText + "\n" + "Регулярное выражение: /^[^\\s@]+@[^\\s@]+\\.[^\\s@]+$/\n"
        + "Метод test(): " + (isEmail ? "true - это email" : "false - это не email") + "\n"
        + "Метод match(): " + (matchResult ? "Найдено: " + matchResult[0] : "Совпадений не найдено");

    alert(resultMessage);
});

document.getElementById('testPhoneBtn').addEventListener('click', function () {
    const testText = document.getElementById('regexTest').value;
    const resultElement = document.getElementById('regexResult');

    if (!testText) {
        resultElement.innerHTML = '<span class="failure">Введите текст для проверки</span>';
        return;
    }

    // Использование метода exec()
    const phoneRegex = /(\+375)\((\d{2})\)(\d{3})-(\d{2})-(\d{2})/;
    const execResult = phoneRegex.exec(testText);

    // Использование метода replace() с обратными ссылками
    const formattedPhone = testText.replace(phoneRegex, function (match, p1, p2, p3, p4, p5) {
        return p1 + ' (' + p2 + ') ' + p3 + '-' + p4 + '-' + p5;
    });
    let resultMessage = "Текст: " + testText + "\n" + "Регулярное выражение: /(\\+375)\\((\\d{2})\\)(\\d{3})-(\\d{2})-(\\d{2})/\n\n"
        + "Метод exec(): " + (execResult ? "Найдено: " + execResult[0] + " (Группы: " + execResult.slice(1).join(', ') + ")" : "Совпадений не найдено") + "\n"
        + "Метод replace(): " + formattedPhone;
    alert(resultMessage);
});

document.getElementById('testWordsBtn').addEventListener('click', function () {
    const testText = document.getElementById('regexTest').value;
    const resultElement = document.getElementById('regexResult');

    if (!testText) {
        resultElement.innerHTML = '<span class="failure">Введите текст для проверки</span>';
        return;
    }

    // Использование метода split() с регулярным выражением
    const words = testText.split(/\s+/);

    // Использование метода search()
    const wordRegex = /\b[А-ЯЁа-яёA-Za-z]+\b/;
    const firstWordIndex = testText.search(wordRegex);

    let resultMessage = "Текст: " + testText + "\n" + "Метод split(/\\s+/):\n"
        + "Результат: [" + words.map(function (word) { return '"' + word + '"'; }).join(', ') + "]\n\n" + "Метод search(/\\b[А-ЯЁа-яёA-Za-z]+\\b/):\n"
        + "Результат: " + (firstWordIndex >= 0 ? "Индекс начала первого слова: " + firstWordIndex : "Слов не найдено");

    alert(resultMessage);
});

// Очистка ошибок при сбросе формы
document.querySelector('.reset-btn').addEventListener('click', function () {
    const errors = document.querySelectorAll('.error');
    errors.forEach(function (error) {
        error.style.display = 'none';
    });

    const inputs = document.querySelectorAll('input, select, textarea');
    inputs.forEach(function (input) {
        input.style.borderColor = '#e5c9eb';
    });

    document.getElementById('formResults').style.display = 'none';
    document.getElementById('regexResult').innerHTML = '';
});

// Добавляем обработчики для сброса ошибок при вводе
document.getElementById('fullName').addEventListener('input', function () {
    hideError(this, document.getElementById('fullNameError'));
});

document.getElementById('email').addEventListener('input', function () {
    hideError(this, document.getElementById('emailError'));
});

document.getElementById('phone').addEventListener('input', function () {
    hideError(this, document.getElementById('phoneError'));
});

// Сбрасываем ошибку возраста при выборе любого варианта
const ageInputs = document.querySelectorAll('input[name="age"]');
ageInputs.forEach(function (input) {
    input.addEventListener('change', function () {
        hideAgeError();
    });
});
