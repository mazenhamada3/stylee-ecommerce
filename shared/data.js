const API_BASE = 'index.php';
let products = [];
let cart = JSON.parse(localStorage.getItem('styleeCart')) || [];
let currentUser = null;

async function api(route, options = {}) {
  const { params = {}, ...fetchOptions } = options;

  const url = new URL('index.php', window.location.origin + '/Webproject/shared/');
  url.searchParams.set('route', route);

  Object.entries(params).forEach(([key, value]) => {
    if (value !== undefined && value !== null) {
      url.searchParams.set(key, value);
    }
  });

  const response = await fetch(url.toString(), {
    credentials: 'include',
    headers: {
      'Content-Type': 'application/json',
      ...(fetchOptions.headers || {})
    },
    ...fetchOptions
  });

  const data = await response.json().catch(() => ({
    success: false,
    message: 'Invalid server response'
  }));

  if (!response.ok || data.success === false) {
    throw new Error(data.message || 'Request failed');
  }

  return data;
}

async function loadProducts() {
  const data = await api('products');
  products = data.products || [];
  return products;
}

async function loadCurrentUser() {
  try {
    const data = await api('me');
    currentUser = data.user || null;
  } catch (error) {
    currentUser = null;
  }

  setLoginLabel();
  setAdminNavVisibility();
  return currentUser;
}

function saveCart() {
  localStorage.setItem('styleeCart', JSON.stringify(cart));
}

function clearLocalDemoData() {
  localStorage.removeItem('styleeProducts');
  localStorage.removeItem('styleeUser');
}

function getColor(product, colorNameOrIndex) {
  if (!product || !Array.isArray(product.colors) || !product.colors.length) return null;

  if (typeof colorNameOrIndex === 'number') {
    return product.colors[colorNameOrIndex] || product.colors[0];
  }

  if (typeof colorNameOrIndex === 'string' && colorNameOrIndex) {
    return product.colors.find(color => color.name === colorNameOrIndex) || product.colors[0];
  }

  return product.colors[0];
}

function getSizeList(product, colorNameOrIndex = null) {
  const color = getColor(product, colorNameOrIndex);
  const sizes = color?.sizes?.length ? color.sizes : product?.sizes || [];

  return sizes.map(size => {
    if (typeof size === 'string') {
      return { name: size, qty: 99 };
    }

    return {
      name: size.name,
      qty: Number(size.qty) || 0
    };
  });
}

function getAvailableSizes(product, colorNameOrIndex = null) {
  return getSizeList(product, colorNameOrIndex).filter(size => size.qty > 0);
}

function getStock(product, colorNameOrIndex, sizeName) {
  if (!product) return 0;

  // Backward compatibility: getStock(product, sizeName)
  if (sizeName === undefined) {
    sizeName = colorNameOrIndex;
    colorNameOrIndex = null;
  }

  const size = getSizeList(product, colorNameOrIndex).find(item => item.name === sizeName);
  return size ? Number(size.qty) || 0 : 0;
}

function formatGender(value) {
  if (value === 'men') return 'Men';
  if (value === 'women') return 'Women';
  return 'Men & Women';
}

function setLoginLabel() {
  const btn = document.getElementById('loginNav');
  if (!btn) return;

  btn.textContent = currentUser ? currentUser.name || 'Account' : 'Login';
  btn.onclick = () => {
    if (currentUser) {
      logout();
    } else {
      navigate('../login/login.html');
    }
  };
}

function setAdminNavVisibility() {
  document.querySelectorAll('.admin-nav').forEach(button => {
    if (currentUser?.role === 'admin') {
      button.classList.remove('hidden');
    } else {
      button.classList.add('hidden');
    }
  });
}

async function logout() {
  try {
    await api('logout', { method: 'POST', body: '{}' });
    currentUser = null;
    setLoginLabel();
    setAdminNavVisibility();
    toast('Logged out');
  } catch (error) {
    toast(error.message);
  }
}

function escapeHtml(value) {
  return String(value ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}

function productMainPhoto(product) {
  return product?.colors?.[0]?.photo || 'https://placehold.co/900x900?text=STYLEE';
}

function syncCartWithProducts() {
  cart = cart.filter(item => {
    const product = products.find(p => p.id === item.productId);
    if (!product) return false;

    const stock = getStock(product, item.colorName, item.size);
    if (stock <= 0) return false;

    item.qty = Math.min(Number(item.qty) || 1, stock);
    return item.qty > 0;
  });

  saveCart();
}

clearLocalDemoData();
