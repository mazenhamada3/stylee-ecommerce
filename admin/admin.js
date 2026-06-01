let orders = [];
const SIZE_NAMES = ['S', 'M', 'L', 'XL', 'XXL'];

function sizeInputsHtml(prefix = '') {
  return SIZE_NAMES.map(size => `
    <label>${size}
      <input class="colorSizeQty" data-size="${size}" type="number" min="0" value="0" ${prefix ? `placeholder="${prefix}"` : ''} />
    </label>
  `).join('');
}

function addColorRow() {
  const wrapper = document.getElementById('colorRows');
  if (!wrapper) return;

  const row = document.createElement('div');
  row.className = 'color-row-wrap';

  row.innerHTML = `
    <button type="button" class="remove-color-btn" onclick="removeColorRow(this)">−</button>

    <div class="color-row">
      <div>
        <div class="field">
          <label>Color Name</label>
          <input class="colorName" placeholder="Black" />
        </div>

        <div class="field">
          <label>Color Photo URL</label>
          <input class="colorPhoto" placeholder="https://image-link.com/photo.jpg" />
        </div>
      </div>

      <div class="field">
        <label>Color</label>
        <input class="colorHex" type="color" value="#111111" />
      </div>

      <div class="field color-sizes-field">
        <label>Sizes for this color</label>
        <div class="size-stock-grid">
          ${sizeInputsHtml()}
        </div>
      </div>
    </div>
  `;

  wrapper.append(row);
}

function removeColorRow(button) {
  const rows = document.querySelectorAll('.color-row-wrap');

  if (rows.length <= 1) {
    toast('At least one color is required');
    return;
  }

  button.parentElement.remove();
}

async function addProduct() {
  const name = document.getElementById('adminName').value.trim();
  const category = document.getElementById('adminCategory').value.trim();
  const gender = document.getElementById('adminGender').value;
  const price = Number(document.getElementById('adminPrice').value);
  const description = document.getElementById('adminDesc').value.trim();
  const rows = [...document.querySelectorAll('.color-row')];

  const colors = rows.map(row => {
    const sizes = [...row.querySelectorAll('.colorSizeQty')].map(input => ({
      name: input.dataset.size,
      qty: Number(input.value) || 0
    }));

    return {
      name: row.querySelector('.colorName').value.trim(),
      hex: row.querySelector('.colorHex').value,
      photo: row.querySelector('.colorPhoto').value.trim(),
      sizes
    };
  }).filter(color => color.name && color.photo && color.sizes.some(size => size.qty > 0));

  if (!name || !category || !price || !description || !colors.length) {
    toast('Fill all product fields and add stock for at least one color size');
    return;
  }

  try {
    await api('admin/products', {
      method: 'POST',
      body: JSON.stringify({ name, category, gender, price, description, colors })
    });

    await loadProducts();
    clearAdminForm();
    renderAdmin();
    toast('Product added');
  } catch (error) {
    toast(error.message);
  }
}

function clearAdminForm() {
  document.getElementById('adminName').value = '';
  document.getElementById('adminCategory').value = '';
  document.getElementById('adminPrice').value = '';
  document.getElementById('adminDesc').value = '';
  document.getElementById('adminGender').value = 'men';

  document.getElementById('colorRows').innerHTML = '';
  addColorRow();
}

function colorStockSummary(color) {
  const sizes = getSizeList({ colors: [color] }, 0);
  return sizes.map(size => `${escapeHtml(size.name)}: ${size.qty}`).join(', ');
}

function renderAdmin() {
  const box = document.getElementById('adminProducts');
  if (!box) return;

  if (!products.length) {
    box.innerHTML = '<p>No products available.</p>';
    return;
  }

  box.innerHTML = products.map(product => `
    <div class="admin-product">
      <img src="${escapeHtml(productMainPhoto(product))}" alt="${escapeHtml(product.name)}" />

      <div>
        <h3>${escapeHtml(product.name)}</h3>
        <p>${escapeHtml(product.category)} · ${formatGender(product.gender)} · $${Number(product.price).toFixed(0)}</p>
        <div class="admin-color-stock">
          ${(product.colors || []).map(color => `
            <p><strong>${escapeHtml(color.name)}:</strong> ${colorStockSummary(color)}</p>
          `).join('')}
        </div>
      </div>

      <button class="btn danger" onclick="deleteProduct('${escapeHtml(product.id)}')">
        Delete
      </button>
    </div>
  `).join('');
}

async function deleteProduct(id) {
  try {
    await api('admin/products', {
      method: 'DELETE',
      params: { id }
    });

    cart = cart.filter(item => item.productId !== id);
    saveCart();

    await loadProducts();
    renderAdmin();
    updateCartCount();

    toast('Product deleted');
  } catch (error) {
    toast(error.message);
  }
}

async function loadOrders() {
  const data = await api('admin/orders');
  orders = data.orders || [];
  return orders;
}

function statusClass(status) {
  return String(status || '').replace(/[^a-z0-9_-]/gi, '').toLowerCase();
}

function renderOrders() {
  const box = document.getElementById('adminOrders');
  if (!box) return;

  if (!orders.length) {
    box.innerHTML = '<p>No orders yet.</p>';
    return;
  }

  box.innerHTML = orders.map(order => `
    <div class="admin-order">
      <div class="admin-order-head">
        <div>
          <h3>Order #${order.id}</h3>
          <p>${escapeHtml(order.customer_name)} · ${escapeHtml(order.customer_email)}</p>
          <p>${escapeHtml(order.created_at || '')}</p>
        </div>
        <div class="admin-order-actions">
          <span class="order-status ${statusClass(order.status)}">${formatOrderStatus(order.status)}</span>
          ${order.status === 'placed' ? `
            <button class="btn" onclick="updateOrderStatus(${order.id}, 'delivered')">Delivered</button>
            <button class="btn danger" onclick="updateOrderStatus(${order.id}, 'cancelled')">Cancel</button>
          ` : ''}
        </div>
      </div>

      <div class="admin-order-items">
        ${(order.items || []).map(item => `
          <div class="admin-order-item">
            <img src="${escapeHtml(item.photo)}" alt="${escapeHtml(item.product_name)}" />
            <div>
              <strong>${escapeHtml(item.product_name)}</strong>
              <p>${escapeHtml(item.color_name)} · Size ${escapeHtml(item.size_name)} · Qty ${Number(item.qty) || 0}</p>
              <p>$${Number(item.unit_price).toFixed(0)}</p>
            </div>
          </div>
        `).join('')}
      </div>

      <div class="admin-order-total">
        Total: <strong>$${Number(order.total).toFixed(0)}</strong>
      </div>
    </div>
  `).join('');
}

function formatOrderStatus(status) {
  if (status === 'delivered') return 'Delivered';
  if (status === 'cancelled') return 'Cancelled';
  return 'Placed';
}

async function updateOrderStatus(orderId, status) {
  const action = status === 'cancelled' ? 'cancel' : 'mark as delivered';

  if (!confirm(`Are you sure you want to ${action} order #${orderId}?`)) {
    return;
  }

  try {
    await api('admin/orders', {
      method: 'PATCH',
      body: JSON.stringify({ order_id: orderId, status })
    });

    await Promise.all([loadOrders(), loadProducts()]);
    renderOrders();
    renderAdmin();
    toast(status === 'cancelled' ? 'Order cancelled and stock restored' : 'Order marked as delivered');
  } catch (error) {
    toast(error.message);
  }
}

async function resetDemoData() {
  try {
    await api('admin/reset', {
      method: 'POST',
      body: '{}'
    });

    cart = [];
    saveCart();
    await Promise.all([loadProducts(), loadOrders()]);
    renderAdmin();
    renderOrders();
    updateCartCount();

    toast('Demo data reset');
  } catch (error) {
    toast(error.message);
  }
}

function renderAdminAccessDenied() {
  const page = document.querySelector('.admin-page');
  page.innerHTML = `
    <div class="panel" style="max-width:720px;margin:48px auto">
      <h2>Admin access required</h2>
      <p style="margin-top:8px;color:#666">Login with the admin account to manage products and orders.</p>
      <button class="btn" style="margin-top:18px" onclick="navigate('../login/login.html')">Login</button>
    </div>
  `;
}

async function initAdmin() {
  try {
    await loadCurrentUser();
    updateCartCount();

    if (!currentUser || currentUser.role !== 'admin') {
      renderAdminAccessDenied();
      return;
    }

    await Promise.all([loadProducts(), loadOrders()]);
    addColorRow();
    renderAdmin();
    renderOrders();
  } catch (error) {
    renderAdminAccessDenied();
    toast(error.message);
  }
}

initAdmin();
