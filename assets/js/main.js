// assets/js/main.js

// Confirmar eliminación
function confirmDelete(nombre) {
    return confirm(`¿Estás seguro de que deseas eliminar a ${nombre}? Esta acción no se puede deshacer.`);
}

// Validar formulario de personal
function validatePersonalForm() {
    const nombre = document.getElementById('nombre').value.trim();
    const apellido = document.getElementById('apellido').value.trim();
    const email = document.getElementById('email').value.trim();
    const departamento = document.getElementById('departamento').value.trim();
    const puesto = document.getElementById('puesto').value.trim();
    
    if (!nombre || !apellido || !email || !departamento || !puesto) {
        alert('Por favor, completa todos los campos obligatorios.');
        return false;
    }
    
    // Validar email
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
        alert('Por favor, ingresa un email válido.');
        return false;
    }
    
    return true;
}

// Validar formulario de cambio de contraseña
function validatePasswordForm() {
    const currentPassword = document.getElementById('current_password').value;
    const newPassword = document.getElementById('new_password').value;
    const confirmPassword = document.getElementById('confirm_password').value;
    
    if (!currentPassword || !newPassword || !confirmPassword) {
        alert('Por favor, completa todos los campos.');
        return false;
    }
    
    if (newPassword.length < 6) {
        alert('La nueva contraseña debe tener al menos 6 caracteres.');
        return false;
    }
    
    if (newPassword !== confirmPassword) {
        alert('Las contraseñas no coinciden.');
        return false;
    }
    
    return true;
}

// Auto-ocultar alertas después de 5 segundos
document.addEventListener('DOMContentLoaded', function() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(function(alert) {
        setTimeout(function() {
            alert.style.opacity = '0';
            setTimeout(function() {
                alert.remove();
            }, 300);
        }, 5000);
    });
    
    // Agregar animación de fade-in a las cards
    const cards = document.querySelectorAll('.card, .dashboard-card');
    cards.forEach(function(card, index) {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        setTimeout(function() {
            card.style.transition = 'all 0.5s ease';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, index * 100);
    });
});

// Formatear números de salario
function formatSalary(input) {
    let value = input.value.replace(/[^\d.]/g, '');
    if (value) {
        input.value = parseFloat(value).toLocaleString('es-MX', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }
}

// Limpiar formato de salario antes de enviar
function cleanSalaryFormat(input) {
    input.value = input.value.replace(/[,]/g, '');
}