// ===== STATE =====
let currentStep = 1;
let selectedShipping = 'standard';
let selectedPayment = 'card';
let couponDiscount = 0;
const SHIPPING_COSTS = { standard: 12, express: 25, free: 0 };
const VALID_COUPONS = { 'STYLEE10': 10, 'SAVE20': 20, 'WELCOME15': 15 };

// ===== INIT =====
async function initCheckout() {
  try {
    await Promise.all([loadProducts(), loadCurrentUser()]);
    syncCartWithProducts();

    if (!cart.length) {
      toast('Your cart is empty!');
      setTimeout(() => navigate('../cart/cart.html'), 900);
      return;
    }

    renderSummary();
    setupShippingListeners();
  } catch (err) {
    renderSummary();
  }
}

// ===== STEP NAVIGATION =====
function goToStep(step) {
  if (step > currentStep && !validateStep(currentStep)) return;

  const panels = ['stepShipping', 'stepPayment', 'stepReview', 'stepSuccess'];
  panels.forEach((id, i) => {
    const el = document.getElementById(id);
    if (!el) return;
    if (i + 1 === step) {
      el.classList.remove('hidden');
    } else {
      el.classList.add('hidden');
    }
  });

  // Update progress indicators
  for (let i = 1; i <= 3; i++) {
    const ind = document.getElementById('step-indicator-' + i);
    if (!ind) continue;
    ind.classList.remove('active', 'done');
    if (i < step) ind.classList.add('done');
    if (i === step) ind.classList.add('active');
  }

  // Update step lines
  const lines = document.querySelectorAll('.step-line');
  lines.forEach((line, i) => {
    if (i + 1 < step) line.classList.add('done');
    else line.classList.remove('done');
  });

  // Populate review step
  if (step === 3) populateReview();

  // Scroll to top of form
  const formCol = document.querySelector('.form-column');
  if (formCol) formCol.scrollIntoView({ behavior: 'smooth', block: 'start' });

  currentStep = step;
  updateSummaryShipping();
}

// ===== VALIDATION =====
function validateStep(step) {
  if (step === 1) {
    const required = [
      { id: 'firstName',  label: 'First Name' },
      { id: 'lastName',   label: 'Last Name'  },
      { id: 'email',      label: 'Email'      },
      { id: 'phone',      label: 'Phone'      },
      { id: 'address',    label: 'Address'    },
      { id: 'city',       label: 'City'       },
      { id: 'country',    label: 'Country'    },
    ];
    for (const { id, label } of required) {
      const el = document.getElementById(id);
      if (!el || !el.value.trim()) {
        toast(`Please fill in ${label}`);
        el && el.focus();
        return false;
      }
    }
    const email = document.getElementById('email').value.trim();
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
      toast('Please enter a valid email');
      document.getElementById('email').focus();
      return false;
    }
    return true;
  }

  if (step === 2) {
    if (selectedPayment === 'card') {
      const num = document.getElementById('cardNumber').value.replace(/\s/g, '');
      if (num.length < 13) { toast('Please enter a valid card number'); return false; }

      const name = document.getElementById('cardName').value.trim();
      if (!name) { toast('Please enter the name on card'); return false; }

      const expiry = document.getElementById('cardExpiry').value.trim();
      if (!/^\d{2}\/\d{2}$/.test(expiry)) { toast('Please enter expiry as MM/YY'); return false; }

      const [mm, yy] = expiry.split('/').map(Number);
      const now = new Date();
      const expDate = new Date(2000 + yy, mm - 1);
      if (mm < 1 || mm > 12 || expDate < now) { toast('Card has expired or invalid month'); return false; }

      const cvv = document.getElementById('cardCVV').value.trim();
      if (cvv.length < 3) { toast('Please enter a valid CVV'); return false; }
    }
    return true;
  }

  return true;
}

// ===== SHIPPING LISTENERS =====
function setupShippingListeners() {
  document.querySelectorAll('input[name="shipping"]').forEach(radio => {
    radio.addEventListener('change', () => {
      selectedShipping = radio.value;

      document.querySelectorAll('.shipping-option').forEach(opt => {
        opt.classList.remove('selected');
      });

      const map = {
        standard: 'opt-standard',
        express: 'opt-express',
        free: 'opt-free'
      };

      const optEl = document.getElementById(map[selectedShipping]);
      if (optEl) optEl.classList.add('selected');

      updateSummaryShipping(); // UI update
    });
  });
}

// ===== PAYMENT TAB =====
function switchPayTab(type) {
  selectedPayment = type;

  document.querySelectorAll('.pay-tab').forEach(t => t.classList.remove('active'));
  document.getElementById('tab-' + type).classList.add('active');

  document.getElementById('panelCard').classList.add('hidden');
  document.getElementById('panelCod').classList.add('hidden');
  document.getElementById('panel' + type.charAt(0).toUpperCase() + type.slice(1)).classList.remove('hidden');
}

// ===== CARD FORMATTING =====
function formatCardNumber(input) {
  let val = input.value.replace(/\D/g, '').slice(0, 16);
  val = val.replace(/(.{4})/g, '$1 ').trim();
  input.value = val;

  const digits = val.replace(/\s/g, '');
  const brand = document.getElementById('cardBrand');

  if (/^4/.test(digits))        brand.textContent = 'VISA';
  else if (/^5[1-5]/.test(digits)) brand.textContent = 'MASTERCARD';
  else if (/^3[47]/.test(digits))  brand.textContent = 'AMEX';
  else brand.textContent = '';

  const preview = document.getElementById('cardPreviewNumber');
  if (preview) {
    const padded = digits.padEnd(16, '•');
    preview.textContent = padded.replace(/(.{4})/g, '$1 ').trim();
  }
}

function formatExpiry(input) {
  let val = input.value.replace(/\D/g, '').slice(0, 4);
  if (val.length >= 3) val = val.slice(0, 2) + '/' + val.slice(2);
  input.value = val;

  const preview = document.getElementById('cardPreviewExpiry');
  if (preview) preview.textContent = val || 'MM/YY';
}

function updateCardPreview() {
  const name = document.getElementById('cardName').value.trim().toUpperCase() || 'FULL NAME';
  const preview = document.getElementById('cardPreviewName');
  if (preview) preview.textContent = name;
}

// ===== COUPON =====
function applyCoupon() {
  const code = document.getElementById('couponInput').value.trim().toUpperCase();
  const msgEl = document.getElementById('couponMsg');
  msgEl.classList.remove('hidden', 'success', 'error');

  if (!code) {
    msgEl.textContent = 'Enter a coupon code.';
    msgEl.classList.add('error');
    return;
  }

  if (VALID_COUPONS[code] !== undefined) {
    couponDiscount = VALID_COUPONS[code];
    msgEl.textContent = `✓ Coupon applied! $${couponDiscount} off`;
    msgEl.classList.add('success');
    document.getElementById('couponInput').disabled = true;
  } else {
    couponDiscount = 0;
    msgEl.textContent = 'Invalid coupon code.';
    msgEl.classList.add('error');
  }

  updateSummaryShipping();
}

// ===== SUMMARY RENDER =====
function renderSummary() {
  const container = document.getElementById('summaryItems');
  if (!container) return;

  if (!cart.length) {
    container.innerHTML = '<p style="color:#aaa;font-size:13px">No items</p>';
    return;
  }

  container.innerHTML = cart.map(item => `
    <div class="summary-item">
      <div class="summary-item-img-wrap">
        <img src="${escapeHtml(item.photo)}" alt="${escapeHtml(item.name)}" />
        <span class="summary-item-qty">${Number(item.qty)}</span>
      </div>
      <div class="summary-item-info">
        <h4>${escapeHtml(item.name)}</h4>
        <p>${escapeHtml(item.colorName)} · Size ${escapeHtml(item.size)}</p>
      </div>
      <span class="summary-item-price">$${(Number(item.price) * Number(item.qty)).toFixed(0)}</span>
    </div>
  `).join('');

  updateSummaryShipping();
}

function updateSummaryShipping() {
  const subtotal = cart.reduce((sum, item) => sum + Number(item.price) * Number(item.qty), 0);
  const shippingCost = subtotal > 0 ? (SHIPPING_COSTS[selectedShipping] || 12) : 0;
  const total = Math.max(0, subtotal - couponDiscount) + shippingCost;

  const subEl      = document.getElementById('sumSubtotal');
  const shipEl     = document.getElementById('sumShipping');
  const totalEl    = document.getElementById('sumTotal');
  const discRow    = document.getElementById('discountRow');
  const discountEl = document.getElementById('sumDiscount');

  if (subEl)   subEl.textContent  = '$' + subtotal.toFixed(0);
  if (shipEl)  shipEl.textContent = shippingCost === 0 && subtotal > 0 ? 'FREE' : '$' + shippingCost.toFixed(0);
  if (totalEl) totalEl.textContent = '$' + total.toFixed(0);

  if (couponDiscount > 0 && discRow && discountEl) {
    discRow.style.display = 'flex';
    discountEl.textContent = '-$' + couponDiscount.toFixed(0);
  } else if (discRow) {
    discRow.style.display = 'none';
  }
}

// ===== POPULATE REVIEW =====
function populateReview() {
  const firstName = document.getElementById('firstName').value.trim();
  const lastName  = document.getElementById('lastName').value.trim();
  const email     = document.getElementById('email').value.trim();
  const phone     = document.getElementById('phone').value.trim();
  const address   = document.getElementById('address').value.trim();
  const city      = document.getElementById('city').value.trim();
  const postal    = document.getElementById('postal').value.trim();
  const countryEl = document.getElementById('country');
  const country   = countryEl.options[countryEl.selectedIndex]?.text || '';

  const shippingLabels = { standard: 'Standard Delivery ($12)', express: 'Express Delivery ($25)', free: 'Free Shipping' };

  document.getElementById('reviewShipping').innerHTML = `
    ${escapeHtml(firstName)} ${escapeHtml(lastName)}<br>
    ${escapeHtml(address)}, ${escapeHtml(city)} ${escapeHtml(postal)}<br>
    ${escapeHtml(country)}<br>
    ${escapeHtml(email)} · ${escapeHtml(phone)}<br>
    <strong>Shipping:</strong> ${shippingLabels[selectedShipping] || 'Standard'}
  `;

  let paymentText = '';
  if (selectedPayment === 'card') {
    const num = document.getElementById('cardNumber').value;
    const last4 = num.replace(/\s/g, '').slice(-4);
    const brand = document.getElementById('cardBrand').textContent;
    paymentText = `${brand || 'Card'} ending in ${last4}`;
  } else {
    paymentText = 'Cash on Delivery';
  }
  document.getElementById('reviewPayment').textContent = paymentText;

  document.getElementById('reviewItems').innerHTML = cart.map(item => `
    <div class="review-item">
      <img src="${escapeHtml(item.photo)}" alt="${escapeHtml(item.name)}" />
      <div class="review-item-info">
        <h4>${escapeHtml(item.name)}</h4>
        <p>${escapeHtml(item.colorName)} · Size ${escapeHtml(item.size)} · Qty ${Number(item.qty)}</p>
      </div>
      <span class="review-item-price">$${(Number(item.price) * Number(item.qty)).toFixed(0)}</span>
    </div>
  `).join('');
}

// ===== PLACE ORDER =====
async function placeOrder() {
  if (!cart.length) { toast('Cart is empty'); return; }
  if (!currentUser) { toast('Please login'); navigate('../login/login.html'); return; }

  const btn = document.getElementById('placeOrderBtn');
  const text = document.getElementById('placeOrderText');
  const spin = document.getElementById('placeOrderSpinner');

  btn.disabled = true;
  text.textContent = 'Placing Order…';
  spin.classList.remove('hidden');

  try {
            const payload = {
        items: cart.map(item => ({
            productId: item.productId,
            colorName: item.colorName,
            size: item.size,
            qty: item.qty
        })),
        shipping: selectedShipping,
        coupon: couponDiscount,
        };

    await api('checkout', { method: 'POST', body: JSON.stringify(payload) });

    // Clear cart
    cart = [];
    saveCart();
    updateCartCount();

    // Show success step
    document.getElementById('stepShipping').classList.add('hidden');
    document.getElementById('stepPayment').classList.add('hidden');
    document.getElementById('stepReview').classList.add('hidden');
    document.getElementById('stepSuccess').classList.remove('hidden');

    // Update progress steps to done
    for (let i = 1; i <= 3; i++) {
      const ind = document.getElementById('step-indicator-' + i);
      if (ind) { ind.classList.remove('active'); ind.classList.add('done'); }
    }
    document.querySelectorAll('.step-line').forEach(l => l.classList.add('done'));

    window.scrollTo({ top: 0, behavior: 'smooth' });
  } catch (err) {
    toast(err.message || 'Order failed');
  } finally {
    btn.disabled = false;
    text.textContent = 'Place Order';
    spin.classList.add('hidden');
  }
}

// ===== BOOT =====
initCheckout();