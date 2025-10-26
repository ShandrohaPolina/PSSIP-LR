// Задание 1

let stri = "Привет как дела? Как же давно я тебя не видел...";
let num = 18;
let arr = [0, 1, 2, 3, 4, 5];
let dati = new Date();
let pi = Math.PI;
document.writeln(stri);
document.write("<br>")
document.writeln(num);
document.write("<br>")
document.writeln(arr);
document.write("<br>")
document.writeln(dati);
document.write("<br>")
document.writeln(pi);
document.write("<br>")
// Задание 2
// Приведите пример описания  пользовательского объекта в JS.
let person = {
    first: 'Екатерина',
    last: 'Сорока',
    middle: 'Владимировна ',
    age: 28,
    al: 666,
    getFullName: function () {
        return `${this.last} ${this.first} ${this.middle}`
    }
}
document.write(person.getFullName());
document.write("<br>")
// Задание 3
// Приведите пример доступа к свойствам объекта в JS.


delete person.age;
delete person['al']
document.write(person.age);
let user = {
    name: "Govard",
    age: 44
};


// 4
document.write("<br>")
let student = {
    n: "Алексей",
    a: 22,
    m: "Информатика",
    Deliko: function () {
        return `${this.n} ${this.a} ${this.m}`
    }
};
document.write(student.Deliko());
document.write("<br>")
// Удаление свойства m
delete student.m;
document.write(student.Deliko());
document.write("<br>")
document.write(student.n)
document.write("<br>")
document.write(`В кабинете, совершенно один, стоит он и глазом даже не повел. Это же - ${student.n}!`)


document.write("<br>")
document.write("a" in student); // true, user.age существует
document.write("<br>")
document.write("blabla" in student); // false, user.blabla не существует
// 6. Команда для перебора свойств объекта в JavaScript
// student bilo ranshe
for (let key in user) {
    // ключи
    alert(key);
    // значения ключей
    alert(user[key]);
}
