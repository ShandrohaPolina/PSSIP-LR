// Создание плавающих музыкальных нот
document.addEventListener('DOMContentLoaded', function () {
    const notesContainer = document.getElementById('floatingNotes');
    const notes = ['♪', '♫', '♬', '♩', '♪', '♫', '𝄞'];

    for (let i = 0; i < 35; i++) {
        const note = document.createElement('div');
        note.className = 'music-note';
        note.textContent = notes[Math.floor(Math.random() * notes.length)];
        note.style.left = `${Math.random() * 100}%`;
        note.style.animationDelay = `${Math.random() * 15}s`;
        note.style.fontSize = `${Math.random() * 20 + 15}px`;
        note.style.opacity = Math.random() * 0.5 + 0.2;
        notesContainer.appendChild(note);
    }
});