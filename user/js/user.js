// ===== CART =====
let cart = JSON.parse(localStorage.getItem('shmarket_cart') || '[]');

function updateCartCount() {
  const total = cart.reduce((s, i) => s + i.qty, 0);
  document.querySelectorAll('.cart-count').forEach(el => el.textContent = total);
}

function addToCart(id, name, price, image) {
  const existing = cart.find(i => i.id === id);
  if (existing) existing.qty++;
  else cart.push({ id, name, price, image, qty: 1 });
  localStorage.setItem('shmarket_cart', JSON.stringify(cart));
  updateCartCount();
  renderCart();
  showToast('Added to cart — ' + name.substring(0,25));
}

function removeFromCart(id) {
  cart = cart.filter(i => i.id !== id);
  localStorage.setItem('shmarket_cart', JSON.stringify(cart));
  updateCartCount();
  renderCart();
}

function changeQty(id, delta) {
  const item = cart.find(i => i.id === id);
  if (!item) return;
  item.qty += delta;
  if (item.qty <= 0) removeFromCart(id);
  else {
    localStorage.setItem('shmarket_cart', JSON.stringify(cart));
    updateCartCount();
    renderCart();
  }
}

function renderCart() {
  const el = document.getElementById('cartItems');
  if (!el) return;
  if (!cart.length) {
    el.innerHTML = '<div class="cart-empty-msg"><i class="fas fa-shopping-bag"></i><p>Your cart is empty</p></div>';
    document.getElementById('cartTotal').textContent = 'Rs. 0';
    document.getElementById('cartSubtotal') && (document.getElementById('cartSubtotal').textContent = 'Rs. 0');
    return;
  }
  el.innerHTML = cart.map(item => `
    <div class="c-item">
      <img src="${item.image || ''}" class="c-item-img" onerror="this.src='https://via.placeholder.com/56x56/1a1a1a/666?text=?'" alt="${item.name}">
      <div class="c-item-info">
        <div class="c-item-name">${item.name}</div>
        <div class="c-item-price">Rs. ${Number(item.price).toLocaleString()}</div>
        <div class="c-item-qty">
          <button class="qty-b" onclick="changeQty(${item.id}, -1)">−</button>
          <span class="qty-n">${item.qty}</span>
          <button class="qty-b" onclick="changeQty(${item.id}, 1)">+</button>
        </div>
      </div>
      <button class="c-remove" onclick="removeFromCart(${item.id})"><i class="fas fa-trash-can"></i></button>
    </div>
  `).join('');

  const subtotal = cart.reduce((s, i) => s + (i.price * i.qty), 0);
  const subtotalEl = document.getElementById('cartSubtotal');
  if (subtotalEl) subtotalEl.textContent = 'Rs. ' + subtotal.toLocaleString();
  document.getElementById('cartTotal').textContent = 'Rs. ' + subtotal.toLocaleString();
}

function toggleCart() {
  document.getElementById('cartBackdrop').classList.toggle('open');
  document.getElementById('cartDrawer').classList.toggle('open');
  renderCart();
}

function checkout() {
  if (!cart.length) { showToast('Cart is empty!', 'error'); return; }
  showToast('Order placed successfully! 🎉');
  cart = [];
  localStorage.setItem('shmarket_cart', JSON.stringify(cart));
  updateCartCount();
  renderCart();
  setTimeout(toggleCart, 1500);
}

// ===== TOAST =====
function showToast(msg, type = 'success') {
  const t = document.getElementById('toast');
  if (!t) return;
  t.querySelector('.toast-dot') && (t.querySelector('.toast-dot').className = 'toast-dot');
  t.className = `toast ${type}`;
  t.querySelector('span').textContent = msg;
  t.classList.add('show');
  clearTimeout(t._timer);
  t._timer = setTimeout(() => t.classList.remove('show'), 3000);
}

// ===== FILTER =====
function filterProducts(cat, btn) {
  if (btn) {
    document.querySelectorAll('.filter-chip').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
  }
  document.querySelectorAll('.product-card[data-category]').forEach(card => {
    if (cat === 'all' || card.dataset.category === cat) card.style.display = '';
    else card.style.display = 'none';
  });
}

// ===== MOBILE NAV =====
function toggleMobileNav() {
  document.getElementById('mobileNav').classList.toggle('open');
}

// ===== SCROLL TO PRODUCTS =====
function scrollToProducts() {
  document.getElementById('productsSection')?.scrollIntoView({ behavior: 'smooth' });
}

// ===== INIT =====
document.addEventListener('DOMContentLoaded', function () {
  updateCartCount();
  renderCart();

  // Close cart on backdrop click
  document.getElementById('cartBackdrop')?.addEventListener('click', toggleCart);

  // Close mobile nav on outside click
  document.addEventListener('click', function (e) {
    const nav = document.getElementById('mobileNav');
    const burger = document.querySelector('.hamburger');
    if (nav && nav.classList.contains('open') && !nav.contains(e.target) && burger && !burger.contains(e.target)) {
      nav.classList.remove('open');
    }
  });
});
