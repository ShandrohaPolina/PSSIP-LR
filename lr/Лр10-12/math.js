function calculate() {
    var x = parseFloat(document.getElementById('inputX').value);
    if (x === 2) {
        alert("Ошибка: деление на ноль.");
        return;
    }
    if (16 - Math.pow(x, 2) < 0) {
        alert("Ошибка: корень из отрицательного числа.");
        return;
    }
    var y = (Math.sqrt(16 - Math.pow(x, 2))) / (x - 2);
    document.getElementById('res').innerText = "Результат: y = " + y;
}
