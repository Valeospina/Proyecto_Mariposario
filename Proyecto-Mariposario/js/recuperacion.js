document.getElementById('recuperacionForm')?.addEventListener('submit', function (e) {
    const email = document.getElementById('email').value;
    if (!email.includes('@')) {
        alert('Por favor ingresa un correo válido.');
        e.preventDefault();
    }
});

document.getElementById('resetForm')?.addEventListener('submit', function (e) {
    const pass = document.getElementById('password').value;
    if (pass.length < 6) {
        alert('La contraseña debe tener al menos 6 caracteres.');
        e.preventDefault();
    }
});
