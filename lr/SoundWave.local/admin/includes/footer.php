        </main>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // Подтверждение удаления
        function confirmDelete(id, name) {
            if (confirm(`Вы уверены, что хотите удалить запись "${name}"?`)) {
                document.getElementById('delete-form-' + id).submit();
            }
        }
        
    </script>

    <script>
// Функция для показа уведомлений
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
        top: 20px;
        right: 20px;
        background: rgba(20, 20, 30, 0.95);
        backdrop-filter: blur(10px);
        border-left: 4px solid ${color};
        color: white;
        padding: 15px 25px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        gap: 12px;
        z-index: 1100;
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.3);
        animation: slideInRight 0.3s ease;
        max-width: 400px;
    `;
    
    notification.innerHTML = `
        <i class="fas ${icon}" style="color: ${color}; font-size: 1.2rem;"></i>
        <span style="flex: 1;">${message}</span>
        <i class="fas fa-times" style="cursor: pointer; opacity: 0.7;"></i>
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
</script>
</body>
</html>