document.addEventListener('DOMContentLoaded', () => {
  const signInForm   = document.getElementById('form-signin');
  const signUpForm   = document.getElementById('form-signup');
  const toSignup     = document.getElementById('to-signup');
  const toSignin     = document.getElementById('to-signin');
  const overlayBtn   = document.getElementById('overlay-btn');
  const titleOverlay = document.getElementById('overlay-title');
  const textOverlay  = document.getElementById('overlay-text');
  const panelImage   = document.querySelector('.panel-image');
  const bgImage      = document.getElementById('bg-image');

  const textos = {
    signin: {
      title: 'Bienvenido a Jardín de Mariposas La Paz',
      text:  'Disfruta de nuestra comunidad: inicia sesión o regístrate para acceder.',
      btn:   'Crear Cuenta'
    },
    signup: {
      title: '¡Únete a Jardín de Mariposas!',
      text:  'Crea tu cuenta gratis y forma parte de nuestra familia.',
      btn:   'Iniciar Sesión'
    }
  };

  function showForm(mode) {
    // 1) switch formularios
    signInForm.classList.toggle('active', mode === 'signin');
    signUpForm.classList.toggle('active', mode === 'signup');

    // 2) actualizar overlay
    titleOverlay.textContent = textos[mode].title;
    textOverlay.textContent  = textos[mode].text;
    overlayBtn.textContent   = textos[mode].btn;

    // 3) transición de imagen
    const newSrc = mode === 'signup'
      ? panelImage.dataset.imgSignup
      : panelImage.dataset.imgSignin;

    // fade-out
    bgImage.style.opacity = 0;
    const onFade = () => {
      bgImage.removeEventListener('transitionend', onFade);
      bgImage.src = newSrc;
      void bgImage.offsetWidth; // fuerza reflow
      bgImage.style.opacity = 1;
    };
    bgImage.addEventListener('transitionend', onFade);

    // fallback si falla la carga
    bgImage.onerror = () => { bgImage.style.opacity = 1; };
  }

  // eventos
  toSignup .addEventListener('click', e => { e.preventDefault(); showForm('signup'); });
  toSignin .addEventListener('click', e => { e.preventDefault(); showForm('signin'); });
  overlayBtn.addEventListener('click', () => {
    showForm(signUpForm.classList.contains('active') ? 'signin' : 'signup');
  });

  // arranque en login
  showForm('signin');
});
