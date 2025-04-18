/**
 * REDAXO Undo Addon - Countdown-Funktionalität
 */
$(document).on('rex:ready', function() {
    // Countdown initialisieren, wenn ein Undo-Link vorhanden ist
    if($('.undo-link').length) {
        var countdownElement = $('#undo-countdown');
        var timeLeft = 30; // 30 Sekunden Countdown
        
        // Countdown-Timer starten
        var countdownTimer = setInterval(function() {
            timeLeft -= 1;
            countdownElement.text(timeLeft);
            
            // Wenn der Countdown abgelaufen ist
            if(timeLeft <= 0) {
                clearInterval(countdownTimer);
                $('.undo-message').fadeOut(500, function() {
                    $(this).remove();
                });
            }
        }, 1000);
    }
});