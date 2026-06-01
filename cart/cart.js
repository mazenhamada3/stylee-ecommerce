function renderCart() {
  const box = document.getElementById('cartItems');

  if (!cart.length) {
    box.innerHTML = `
      <div class="panel">
        <h2>Your cart is empty.</h2>
        <p style="margin-top:8px;color:#666">Add products from the shop.</p>
      </div>
    `;
  } else {
    box.innerHTML = cart.map((item, index) => `
      <div class="cart-item">
        <img src="${escapeHtml(item.photo)}" alt="${escapeHtml(item.name)}" />

        <div>
          <p class="category">${escapeHtml(item.category)}</p>
          <h3>${escapeHtml(item.name)}</h3>
          <p>${escapeHtml(item.colorName)} · Size ${escapeHtml(item.size)}</p>
          <p class="price">$${Number(item.price).toFixed(0)}</p>

          <div class="qty">
            <button onclick="changeQty(${index}, -1)">-</button>
            <strong>${Number(item.qty) || 0}</strong>
            <button onclick="changeQty(${index}, 1)">+</button>
          </div>
        </div>

        <div>
          <strong>$${(Number(item.price) * Number(item.qty)).toFixed(0)}</strong><br><br>
          <button class="btn danger" onclick="removeCartItem(${index})">Remove</button>
        </div>
      </div>
    `).join('');
  }

  const subtotal = cart.reduce((sum, item) => sum + Number(item.price) * Number(item.qty), 0);
  const shipping = subtotal > 0 ? 12 : 0;

  document.getElementById('subtotalText').textContent = '$' + subtotal.toFixed(0);
  document.getElementById('shippingText').textContent = '$' + shipping.toFixed(0);
  document.getElementById('totalText').textContent = '$' + (subtotal + shipping).toFixed(0);

  updateCartCount();
}

function changeQty(index, amount) {
  if (amount > 0) {
    const product = products.find(p => p.id === cart[index].productId);
    const stock = getStock(product, cart[index].colorName, cart[index].size);

    if (cart[index].qty + 1 > stock) {
      toast('Only ' + stock + ' left in this size');
      return;
    }
  }

  cart[index].qty += amount;

  if (cart[index].qty <= 0) {
    cart.splice(index, 1);
  }

  saveCart();
  renderCart();
}

function removeCartItem(index) {
  cart.splice(index, 1);
  saveCart();
  renderCart();
}

async function checkout() {
  if (!cart.length) {
    toast('Cart is empty');
    return;
  }

  if (!currentUser) {
    toast('Please login before checkout');
    setTimeout(() => navigate('../login/login.html'), 900);
    return;
  }

  try {
    const payload = {
      items: cart.map(item => ({
        productId: item.productId,
        colorName: item.colorName,
        size: item.size,
        qty: item.qty
      }))
    };

    await api('checkout', {
      method: 'POST',
      body: JSON.stringify(payload)
    });

    cart = [];
    saveCart();
    await loadProducts();
    renderCart();
    toast('Order placed successfully');
  } catch (error) {
    toast(error.message);
  }
}

async function initCart() {
  try {
    await Promise.all([loadProducts(), loadCurrentUser()]);
    syncCartWithProducts();
    renderCart();
  } catch (error) {
    renderCart();
    toast(error.message);
  }
}

initCart();
