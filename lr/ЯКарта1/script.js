
document.addEventListener('DOMContentLoaded', function () {
    const form = document.querySelector('form');
    const clearFormBtn = document.getElementById('clearForm');
    const clearStorageBtn = document.getElementById('cleany');

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

    // Обработчик для очистки localStorage
    clearStorageBtn.addEventListener('click', function () {
        clearJSONData();
        displayLocalStorageData();
    });

    // Валидация формы при отправке
    if (form) {
        form.addEventListener('submit', function (event) {
            event.preventDefault();
            // Проверка на пустые поля
            if (!validateForm()) {
                return;
            }
            // Проверка формата с помощью регулярных выражений
            if (!validateFormWithRegex()) {
                return;
            }

            // Получение значений полей формы
            const name = document.getElementById('name').value;
            const email = document.getElementById('email').value;
            const dost = document.getElementById('dost').value;
            const phone = document.getElementById('phone').value;
            const productId = document.getElementById('productId').value;
            const infa = document.getElementById('infa').value;
            const num = document.getElementById('num').value;

            // Вывод в alert
            alert(`Данные формы: \nИмя: ${name}\nEmail: ${email}\nАдрес доставки: ${dost}\nТелефон: ${phone}\nID продукта: ${productId}\nДоп.информация: ${infa || 'не указана'}\nКапча: ${num}`);


            // Сохранение в Local Storage
            const formData = {
                name: name,
                email: email,
                dost: dost,
                phone: phone,
                productId: productId,
                infa: infa,
                num: num,
                timestamp: new Date().toISOString()
            };

            localStorage.setItem('orderFormData', JSON.stringify(formData));

            // Сохранение в JSON формате
            saveFormDataToJSON(formData);

            // Очистка формы
            // form.reset();
            hideAllErrors();
        });
    }

    // Очистка формы
    if (clearFormBtn) {
        clearFormBtn.addEventListener('click', function (event) {
            event.preventDefault();
            form.reset();
            hideAllErrors();
        });
    }

    // Функция для сохранения данных формы в JSON формате
    function saveFormDataToJSON(formData) {
        const jsonData = JSON.stringify(formData, null, 2);
        console.log('Данные формы в JSON формате:');
        console.log(jsonData);

        // Сохранение JSON в Local Storage
        localStorage.setItem('orderFormJSON', jsonData);

        return jsonData;
    }

    // Функция для отображения данных из Local Storage
    function displayLocalStorageData() {
        const storedData = localStorage.getItem('orderFormData');
        const outputDiv = document.getElementById('output');

        if (storedData && outputDiv) {
            const data = JSON.parse(storedData);
            outputDiv.innerHTML = `
                <h3>Данные из Local Storage:</h3>
                <p><strong>Имя:</strong> ${data.name}</p>
<p><strong>Email:</strong> ${data.email}</p>
                <p><strong>Адрес доставки:</strong> ${data.dost}</p>
                <p><strong>Телефон:</strong> ${data.phone}</p>
                <p><strong>ID продукта:</strong> ${data.productId}</p>
                <p><strong>Доп. информация:</strong> ${data.infa || 'не указана'}</p>
                <p><strong>Капча:</strong> ${data.num}</p>
                <p><strong>Время отправки:</strong> ${new Date(data.timestamp).toLocaleString()}</p>`
                ;
        } else if (outputDiv) {
            outputDiv.innerHTML = '<h3>Данные из Local Storage:</h3><p>Нет сохраненных данных</p>';
        }
    }

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
            case 'name':
                const nameRegex = /^[A-Za-zА-Яа-яЁё\s]+$/;
                if (!nameRegex.test(fieldValue)) {
                    errorMessage = 'Имя может содержать только буквы и пробелы';
                    isValid = false;
                } else if (fieldValue.length < 2) {
                    errorMessage = 'Имя должно содержать минимум 2 символа';
                    isValid = false;
                } else if (fieldValue.length > 50) {
                    errorMessage = 'Имя должно содержать не более 50 символов';
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

            case 'dost':
                if (fieldValue.length < 5) {
                    errorMessage = 'Адрес доставки должен содержать минимум 5 символов';
                    isValid = false;
                }
                break;

            case 'phone':
                const phoneRegex = /^\+375\(\d{2}\)\d{3}-\d{2}-\d{2}$/;
                if (!phoneRegex.test(fieldValue)) {
                    errorMessage = 'Телефон должен быть в формате +375(XX)XXX-XX-XX';
                    isValid = false;
                }
                break;

            case 'productId':
                const productIdRegex = /^[A-Z]{2}-\d{4}$/;
                if (!productIdRegex.test(fieldValue)) {
                    errorMessage = 'ID продукта должен быть в формате AB-1234 (две заглавные буквы, дефис, четыре цифры)';
                    isValid = false;
                }
                break;

            case 'num':
                const numRegex = /^\d{4}$/;
                if (!numRegex.test(fieldValue)) {
                    errorMessage = 'Пожалуйста, введите 4 цифры с картинки';
                    isValid = false;
                } else if (fieldValue !== '1111') {
                    errorMessage = 'Неверный код с картинки. Введите 1111';
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

    // Валидация полей пользовательской формы (при отправке)
    function validateForm() {
        let isValid = true;
        // Сброс ошибок
        document.querySelectorAll('.error').forEach(error => {
            error.style.display = 'none';
        });

        // Проверка обязательных полей на пустоту
        const requiredFields = [
            { id: 'name', message: 'Поле "Имя" обязательно для заполнения' },
            { id: 'email', message: 'Поле "Email" обязательно для заполнения' },
            { id: 'dost', message: 'Поле "Адрес доставки" обязательно для заполнения' },
            { id: 'phone', message: 'Поле "Телефон" обязательно для заполнения' },
            { id: 'productId', message: 'Поле "ID продукта" обязательно для заполнения' },
            { id: 'num', message: 'Поле "Число с картинки" обязательно для заполнения' }
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

        return isValid;
    }

    // Проверка на соответствие нужному формату с использованием регулярных выражений
    function validateFormWithRegex() {
        let isValid = true;

        // Проверяем только заполненные поля
        const fieldsToValidate = ['name', 'email', 'dost', 'phone', 'productId', 'num'];

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

    // Функция для очистки JSON данных
    function clearJSONData() {
        localStorage.removeItem('orderFormJSON');
        localStorage.removeItem('orderFormData');
        alert('Данные JSON очищены из Local Storage');

        // Обновляем отображение
        const outputDiv = document.getElementById('output');
        if (outputDiv) {
            outputDiv.innerHTML = '<h3>Данные из Local Storage:</h3><p>Нет сохраненных данных</p>';
        }
    }

    // При загрузке страницы показать данные из Local Storage, если они есть
    displayLocalStorageData();
});


