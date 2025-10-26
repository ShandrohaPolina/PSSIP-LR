document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('personalForm');
    const clearFormBtn = document.getElementById('clearForm');
    const clearStorageBtn = document.getElementById('clearStorage');
    const outputDiv = document.getElementById('output');
    const storageData = document.getElementById('storageData');

    // Загружаем данные из хранилища при загрузке страницы
    loadDataFromStorage();

    // Добавляем обработчики событий для валидации в реальном времени
    const inputs = form.querySelectorAll('input, textarea');
    inputs.forEach(input => {
        input.addEventListener('blur', function () {
            validateField(this);
        });

        input.addEventListener('input', function () {
            // Скрываем ошибку при начале ввода
            const fieldId = this.id;
            const errorElement = document.getElementById(fieldId + 'Error');
            if (errorElement) {
                errorElement.style.display = 'none';
            }
        });
    });

    // Обработчик для очистки хранилища
    clearStorageBtn.addEventListener('click', function () {
        clearStorage();
    });

    // Обработчик отправки формы
    form.addEventListener('submit', function (event) {
        event.preventDefault();

        if (!validateForm()) {
            return;
        }

        // Получение значений полей формы
        const formData = {
            fullName: document.getElementById('fullName').value,
            email: document.getElementById('email').value,
            birthDate: document.getElementById('birthDate').value,
            birthPlace: document.getElementById('birthPlace').value,
            hobbies: document.getElementById('hobbies').value,
            timestamp: new Date().toISOString()
        };

        // Сохранение в хранилище
        saveToStorage(formData);

        // Вывод сообщения об успешном сохранении
        alert('Данные успешно сохранены!');
    });

    // Очистка формы
    clearFormBtn.addEventListener('click', function () {
        form.reset();
        hideAllErrors();
    });







    // ==================== LOCAL STORAGE РЕАЛИЗАЦИЯ ====================

    // Функция для сохранения данных в хранилище
    function saveToStorage(data) {
        localStorage.setItem('personalData', JSON.stringify(data));
        displayStorageData();
    }

    // Функция для отображения данных из хранилища
    function displayStorageData() {
        const storedData = localStorage.getItem('personalData');

        if (storedData) {
            const data = JSON.parse(storedData);
            storageData.innerHTML = `
                <p><strong>ФИО:</strong> ${data.fullName}</p>
                <p><strong>Email:</strong> ${data.email}</p>
                <p><strong>Дата рождения:</strong> ${formatDate(data.birthDate)}</p>
                <p><strong>Место рождения:</strong> ${data.birthPlace}</p>
                <p><strong>Увлечения:</strong> ${data.hobbies || 'не указаны'}</p>
                <p><strong>Время сохранения:</strong> ${new Date(data.timestamp).toLocaleString()}</p>`
                ;
        } else {
            storageData.innerHTML = '<p>Нет сохраненных данных</p>';
        }
    }

    // Функция для загрузки данных из хранилища в форму
    function loadDataFromStorage() {
        const storedData = localStorage.getItem('personalData');

        if (storedData) {
            const data = JSON.parse(storedData);
            document.getElementById('fullName').value = data.fullName || '';
            document.getElementById('email').value = data.email || '';
            document.getElementById('birthDate').value = data.birthDate || '';
            document.getElementById('birthPlace').value = data.birthPlace || '';
            document.getElementById('hobbies').value = data.hobbies || '';
        }

        displayStorageData();
    }

    // Функция для очистки хранилища
    function clearStorage() {
        localStorage.removeItem('personalData');
        alert('Данные очищены!');
        displayStorageData();
    }







    // // ==================== COOKIE РЕАЛИЗАЦИЯ ====================

    // // Функция для получения значения cookie по имени
    // function getCookie(name) {
    //     const nameEQ = name + "=";
    //     const ca = document.cookie.split(';');
    //     for (let i = 0; i < ca.length; i++) {
    //         let c = ca[i];
    //         while (c.charAt(0) === ' ') c = c.substring(1, c.length);
    //         if (c.indexOf(nameEQ) === 0) return c.substring(nameEQ.length, c.length);
    //     }
    //     return null;
    // }

    // // Функция для сохранения данных в хранилище
    // function saveToStorage(data) {
    //     // Устанавливаем срок действия cookie на 30 дней
    //     const expirationDate = new Date();
    //     expirationDate.setDate(expirationDate.getDate() + 30);

    //     // Сохраняем данные в cookie
    //     document.cookie = `personalData = ${encodeURIComponent(JSON.stringify(data))}; expires = ${expirationDate.toUTCString()}; path =/`;

    //     displayStorageData();
    // }

    // // Функция для отображения данных из хранилища
    // function displayStorageData() {
    //     const cookieData = getCookie('personalData');

    //     if (cookieData) {
    //         try {
    //             const data = JSON.parse(decodeURIComponent(cookieData));
    //             storageData.innerHTML = `
    //             <p><strong>ФИО:</strong> ${data.fullName}</p>
    //             <p><strong>Email:</strong> ${data.email}</p>
    //             <p><strong>Дата рождения:</strong> ${formatDate(data.birthDate)}</p>
    //             <p><strong>Место рождения:</strong> ${data.birthPlace}</p>
    //             <p><strong>Увлечения:</strong> ${data.hobbies || 'не указаны'}</p>
    //             <p><strong>Время сохранения:</strong> ${new Date(data.timestamp).toLocaleString()}</p>`
    //                 ;
    //         } catch (e) {
    //             storageData.innerHTML = '<p>Ошибка при чтении данных</p>';
    //         }
    //     } else {
    //         storageData.innerHTML = '<p>Нет сохраненных данных</p>';
    //     }
    // }

    // // Функция для загрузки данных из хранилища в форму
    // function loadDataFromStorage() {
    //     const cookieData = getCookie('personalData');

    //     if (cookieData) {
    //         try {
    //             const data = JSON.parse(decodeURIComponent(cookieData));
    //             document.getElementById('fullName').value = data.fullName || '';
    //             document.getElementById('email').value = data.email || '';
    //             document.getElementById('birthDate').value = data.birthDate || '';
    //             document.getElementById('birthPlace').value = data.birthPlace || '';
    //             document.getElementById('hobbies').value = data.hobbies || '';
    //         } catch (e) {
    //             console.error('Ошибка при загрузке данных:', e);
    //         }
    //     }

    //     displayStorageData();
    // }

    // // Функция для очистки хранилища
    // function clearStorage() {
    //     // Устанавливаем срок действия в прошлом для удаления cookie
    //     const expirationDate = new Date();
    //     expirationDate.setDate(expirationDate.getDate() - 1);

    //     document.cookie = `personalData =; expires = ${expirationDate.toUTCString()}; path =/`;
    //     alert('Данные очищены!');
    //     displayStorageData();
    // }






    // ==================== ОБЩИЕ ФУНКЦИИ ====================

    // Валидация отдельного поля в реальном времени
    function validateField(field) {
        const fieldId = field.id;
        const fieldValue = field.value.trim();

        // Сначала скрываем ошибку
        const errorElement = document.getElementById(fieldId + 'Error');
        if (errorElement) {
            errorElement.style.display = 'none';
        }

        // Проверяем поле только если оно не пустое
        if (fieldValue === '') {
            return true;
        }

        let isValid = true;
        let errorMessage = '';

        switch (fieldId) {
            case 'fullName':
                const nameRegex = /^[A-Za-zА-Яа-яЁё\s-]+$/;
                if (!nameRegex.test(fieldValue)) {
                    errorMessage = 'ФИО может содержать только буквы, пробелы и дефисы';
                    isValid = false;
                } else if (fieldValue.length < 5) {
                    errorMessage = 'ФИО должно содержать минимум 5 символов';
                    isValid = false;
                } else if (fieldValue.length > 100) {
                    errorMessage = 'ФИО должно содержать не более 100 символов';
                    isValid = false;
                }
                break;

            case 'email':
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(fieldValue)) {
                    errorMessage = 'Пожалуйста, введите корректный email (например: user@example.com)';
                    isValid = false;
                }
                break;

            case 'birthDate':
                const today = new Date();
                const birthDate = new Date(fieldValue);
                const minDate = new Date();
                minDate.setFullYear(today.getFullYear() - 120); // Максимальный возраст 120 лет

                if (birthDate > today) {
                    errorMessage = 'Дата рождения не может быть в будущем';
                    isValid = false;
                } else if (birthDate < minDate) {
                    errorMessage = 'Дата рождения слишком ранняя';
                    isValid = false;
                }
                break;

            case 'birthPlace':
                if (fieldValue.length < 2) {
                    errorMessage = 'Место рождения должно содержать минимум 2 символа';
                    isValid = false;
                }
                break;
        }

        if (!isValid && errorElement) {
            errorElement.textContent = errorMessage;
            errorElement.style.display = 'block';
        }

        return isValid;
    }

    // Валидация формы при отправке
    function validateForm() {
        let isValid = true;
        // Сброс ошибок
        document.querySelectorAll('.error').forEach(error => {
            error.style.display = 'none';
        });

        // Проверка обязательных полей на пустоту
        const requiredFields = [
            { id: 'fullName', message: 'Поле "ФИО" обязательно для заполнения' },
            { id: 'email', message: 'Поле "Email" обязательно для заполнения' },
            { id: 'birthDate', message: 'Поле "Дата рождения" обязательно для заполнения' },
            { id: 'birthPlace', message: 'Поле "Место рождения" обязательно для заполнения' }
        ];

        requiredFields.forEach(field => {
            const input = document.getElementById(field.id);
            const errorElement = document.getElementById(field.id + 'Error');

            if (input && input.value.trim() === '') {
                if (errorElement) {
                    errorElement.textContent = field.message;
                    errorElement.style.display = 'block';
                }
                isValid = false;
            }
        });

        // Проверяем только заполненные поля на соответствие формату
        const fieldsToValidate = ['fullName', 'email', 'birthDate', 'birthPlace'];

        fieldsToValidate.forEach(fieldId => {
            const input = document.getElementById(fieldId);
            if (input && input.value.trim() !== '') {
                if (!validateField(input)) {
                    isValid = false;
                }
            }
        });

        return isValid;
    }

    // Функция скрытия всех ошибок
    function hideAllErrors() {
        document.querySelectorAll('.error').forEach(error => {
            error.style.display = 'none';
        });
    }

    // Функция для форматирования даты
    function formatDate(dateString) {
        if (!dateString) return 'не указана';
        const date = new Date(dateString);
        return date.toLocaleDateString('ru-RU');
    }
});

