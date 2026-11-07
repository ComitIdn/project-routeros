/**
 * Project RouterOS - Client Script
 */

document.addEventListener('DOMContentLoaded', function() {
    // Format MAC Address input automatically with colons
    const macInput = document.getElementById('mac_address');
    if (macInput) {
        macInput.addEventListener('input', function(e) {
            let val = e.target.value.replace(/[^0-9a-fA-F]/g, '');
            if (val.length > 12) val = val.substring(0, 12);
            let formatted = val.match(/.{1,2}/g)?.join(':') || val;
            e.target.value = formatted.toUpperCase();
        });
    }

    // Auto dismiss notifications after 5 seconds
    const alerts = document.querySelectorAll('.alert-success');
    alerts.forEach(function(alert) {
        setTimeout(function() {
            alert.style.transition = 'opacity 0.5s ease';
            alert.style.opacity = '0';
            setTimeout(function() {
                alert.remove();
            }, 500);
        }, 5000);
    });
});
