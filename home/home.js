function scrollProducts() {
  const shopSection = document.getElementById('shopSection');
  if (!shopSection) return;

  shopSection.scrollIntoView({ behavior: 'smooth' });
}

function renderProducts() {
  const grid = document.getElementById('productGrid');
  const search = document.getElementById('searchInput').value.toLowerCase();
  const category = document.getElementById('categoryFilter').value;
  const gender = document.getElementById('genderFilter').value;

  const filtered = products.filter(product => {
    const matchSearch =
      product.name.toLowerCase().includes(search) ||
      product.category.toLowerCase().includes(search);

    const matchCategory =
      category === 'all' || product.category === category;

    const productGender = product.gender || 'both';

    const matchGender =
      gender === 'all' ||
      productGender === gender ||
      productGender === 'both';

    return matchSearch && matchCategory && matchGender;
  });

  grid.innerHTML = filtered.map(product => {
    const mainColor = product.colors[0] || {};

    return `
      <article class="product-card">
        <img src="${escapeHtml(mainColor.photo || productMainPhoto(product))}" alt="${escapeHtml(product.name)}" onclick="openProduct('${escapeHtml(product.id)}')" />

        <div class="product-body">
          <p class="category">${escapeHtml(product.category)} · ${formatGender(product.gender)}</p>
          <h3>${escapeHtml(product.name)}</h3>
          <p class="price">$${Number(product.price).toFixed(0)}</p>

          <div class="color-dots">
            ${(product.colors || []).map(color => `
              <span class="dot" style="background:${escapeHtml(color.hex)}"></span>
            `).join('')}
          </div>

          <button class="btn full" onclick="openProduct('${escapeHtml(product.id)}')">View Product</button>
        </div>
      </article>
    `;
  }).join('') || '<p>No products found.</p>';
}

function renderCategories() {
  const select = document.getElementById('categoryFilter');
  const categories = [...new Set(products.map(product => product.category))];

  select.innerHTML =
    '<option value="all">All categories</option>' +
    categories.map(category => `
      <option value="${escapeHtml(category)}">${escapeHtml(category)}</option>
    `).join('');
}

function openProduct(id) {
  localStorage.setItem('selectedProductId', id);
  navigate('../product-details/product-details.html');
}

async function initHome() {
  try {
    await Promise.all([loadProducts(), loadCurrentUser()]);
    syncCartWithProducts();
    renderCategories();
    renderProducts();
    updateCartCount();
  } catch (error) {
    document.getElementById('productGrid').innerHTML = '<p>Could not load products. Check backend setup.</p>';
    toast(error.message);
  }
}

initHome();
