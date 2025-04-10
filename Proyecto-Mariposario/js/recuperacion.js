document.getElementById("resetForm").addEventListener("submit", function (e) {
    e.preventDefault();
    const email = this.querySelector("input").value;
  
    // Simulación de feedback visual
    alert(`Se ha enviado un enlace de recuperación a: ${email}`);
  });
  