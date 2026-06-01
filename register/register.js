async function fakeSignup() {
  const name = document.getElementById('signupName').value.trim();
  const email = document.getElementById('signupEmail').value.trim();
  const password = document.getElementById('signupPassword').value.trim();

  if (!name) {
    toast('Please enter your name');
    return;
  }

  if (!email) {
    toast('Please enter your email');
    return;
  }

  if (!password) {
    toast('Please enter your password');
    return;
  }

  try {
    const data = await api('register', {
      method: 'POST',
      body: JSON.stringify({ name, email, password })
    });

    currentUser = data.user;
    setLoginLabel();
    toast('Account created successfully!');

    setTimeout(() => {
      navigate('../home/home.html');
    }, 900);
  } catch (error) {
    toast(error.message);
  }
}

async function initRegister() {
  await loadCurrentUser();
  updateCartCount();
}

initRegister();
