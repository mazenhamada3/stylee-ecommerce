async function fakeLogin() {
  const email = document.getElementById('loginEmail').value.trim();
  const password = document.getElementById('loginPassword').value.trim();

  if (!email) {
    toast('Please enter your email');
    return;
  }

  if (!password) {
    toast('Please enter your password');
    return;
  }

  try {
    const data = await api('login', {
      method: 'POST',
      body: JSON.stringify({ email, password })
    });

    currentUser = data.user;
    setLoginLabel();
    toast('Logged in successfully!');

    setTimeout(() => {
      navigate('../home/home.html');
    }, 900);
  } catch (error) {
    toast(error.message);
  }
}

async function initLogin() {
  await loadCurrentUser();
  updateCartCount();
}

initLogin();
