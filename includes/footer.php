        </main>
    </div>
    
    <script>
    // Автоматическое скрытие сообщений через 5 секунд
    setTimeout(function() {
        var alerts = document.querySelectorAll('.alert');
        alerts.forEach(function(alert) {
            alert.style.opacity = '0';
            setTimeout(function() {
                alert.style.display = 'none';
            }, 500);
        });
    }, 5000);
    
    // Подтверждение удаления
    function confirmDelete(message) {
        return confirm(message || 'Вы уверены, что хотите удалить этот элемент?');
    }
    </script>
</body>
</html>