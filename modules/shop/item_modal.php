<?php

?>

<style>

:root {
    --primary-grad: linear-gradient(135deg, #FF6B6B 0%, #FF8E53 100%);
    --accent: #FF8E53;
}

@keyframes backdropIn  { from { opacity:0; } to { opacity:1; } }
@keyframes backdropOut { from { opacity:1; } to { opacity:0; } }
@keyframes modalIn  { from { opacity:0; transform:translateY(40px) scale(0.95); } to { opacity:1; transform:translateY(0) scale(1); } }
@keyframes modalOut { from { opacity:1; transform:translateY(0) scale(1); } to { opacity:0; transform:translateY(40px) scale(0.95); } }
@keyframes imgReveal { from { opacity:0; transform:scale(1.08); } to { opacity:1; transform:scale(1); } }
@keyframes fadeUp { from { opacity:0; transform:translateY(16px); } to { opacity:1; transform:translateY(0); } }
@keyframes pulse { 0%,100% { transform:scale(1); } 50% { transform:scale(1.08); } }

#itemModal {
    position:fixed; inset:0; z-index:2000;
    display:none; align-items:center; justify-content:center;
    padding:2rem; background:rgba(0,0,0,0);
}
#itemModal.open {
    display:flex;
    animation:backdropIn 0.3s ease forwards;
    background:rgba(0,0,0,0.5);
}
#itemModal.closing { animation:backdropOut 0.25s ease forwards; }

#modalBox {
    background:white; border-radius:28px;
    width:100%; max-width:420px; overflow:hidden; position:relative;
    animation:modalIn 0.35s cubic-bezier(0.34,1.56,0.64,1) forwards;
    box-shadow:0 32px 80px rgba(0,0,0,0.2);
}
#modalBox.closing { animation:modalOut 0.25s ease forwards; }

.modal-img-wrap {
    width:100%; height:220px; overflow:hidden;
    display:flex; align-items:center; justify-content:center; position:relative;
}
.modal-img-wrap img {
    width:100%; height:100%; object-fit:cover;
    animation:imgReveal 0.5s ease 0.1s both;
}
.modal-img-wrap i {
    font-size:5rem; color:white; opacity:0.85;
    animation:fadeUp 0.4s ease 0.15s both;
}
.modal-stock {
    display:flex; align-items:center; gap:4px;
    animation:fadeUp 0.4s ease 0.28s both;
}
.modal-stock-dot { width:6px; height:6px; border-radius:50%; background:#00b894; flex-shrink:0; }
.modal-stock-dot.low { background:#fdcb6e; }
.modal-stock-dot.out { background:#d63031; }
.modal-stock-label { font-size:0.65rem; font-weight:600; color:#b2bec3; letter-spacing:0.2px; }
.modal-close-btn {
    position:absolute; top:14px; right:14px; z-index:10;
    background:rgba(255,255,255,0.92); border:none;
    width:36px; height:36px; border-radius:50%;
    cursor:pointer; font-size:1rem;
    display:flex; align-items:center; justify-content:center;
    transition:0.2s; backdrop-filter:blur(6px);
    animation:fadeUp 0.3s ease 0.1s both;
}
.modal-close-btn:hover { background:white; transform:rotate(90deg); }

.modal-body { padding:1.6rem 1.8rem 1.8rem; }

.modal-cat {
    font-size:0.7rem; font-weight:800; text-transform:uppercase;
    letter-spacing:1.5px; color:#FF8E53; color:var(--accent); margin-bottom:0.4rem;
    animation:fadeUp 0.4s ease 0.15s both;
}
.modal-name {
    font-family:'Plus Jakarta Sans',serif; font-size:1.55rem; font-weight:900;
    color:#1A1C1E; line-height:1.15; margin-bottom:0.6rem;
    animation:fadeUp 0.4s ease 0.2s both;
}
.modal-price-row {
    display:flex; align-items:baseline; gap:8px; margin-bottom:0.8rem;
    animation:fadeUp 0.4s ease 0.25s both;
}
.modal-price {
    font-size:1.5rem; font-weight:800;
    background:linear-gradient(135deg,#FF6B6B,#FF8E53);
    background:var(--primary-grad); background-clip:text;
    -webkit-background-clip:text; -webkit-text-fill-color:transparent;
    color:#FF6B6B;
}
.modal-unit { font-size:0.8rem; color:#bdc3c7; font-weight:600; }
.modal-desc {
    color:#7f8c8d; font-size:0.85rem; line-height:1.65;
    margin-bottom:1.2rem; max-height:60px; overflow:hidden;
    animation:fadeUp 0.4s ease 0.28s both;
}
.modal-qty-row {
    display:flex; align-items:center; gap:12px; margin-bottom:1rem;
    animation:fadeUp 0.4s ease 0.3s both;
}
.modal-qty-row label { font-weight:700; font-size:0.85rem; color:#1A1C1E; }
.modal-qty-wrap {
    display:flex; align-items:center;
    border:1.5px solid #eee; border-radius:12px; overflow:hidden;
}
.modal-qty-wrap button {
    width:38px; height:38px; border:none; background:white;
    font-size:1rem; font-weight:700; color:#1A1C1E; cursor:pointer; transition:0.2s;
}
.modal-qty-wrap button:hover { background:#f8f9fa; }
.modal-qty-wrap input {
    width:48px; height:38px; border:none;
    border-left:1.5px solid #eee; border-right:1.5px solid #eee;
    text-align:center; font-weight:800; font-size:0.9rem;
    outline:none; font-family:'Plus Jakarta Sans',sans-serif;
}
.modal-qty-wrap input::-webkit-outer-spin-button,
.modal-qty-wrap input::-webkit-inner-spin-button { -webkit-appearance:none; margin:0; }
.modal-qty-wrap input[type=number] { -moz-appearance:textfield; appearance:textfield; }

/* ── BUTTONS ── */
.modal-btn-add {
    width:100%; padding:13px; border:none; border-radius:14px;
    background:var(--primary-grad); color:white;
    font-weight:800; font-size:0.9rem;
    font-family:'Plus Jakarta Sans',sans-serif;
    cursor:pointer; transition:all 0.3s;
    display:flex; align-items:center; justify-content:center; gap:8px;
    margin-bottom:0.6rem;
    animation:fadeUp 0.4s ease 0.32s both;
}
.modal-btn-add:hover:not(:disabled) { opacity:0.88; transform:translateY(-3px); box-shadow:0 10px 26px rgba(255,107,107,0.3); }
.modal-btn-add:disabled { opacity:0.55; cursor:not-allowed; }
.modal-btn-add.success { background:linear-gradient(135deg,#00b894,#00cec9) !important; }
.modal-btn-add.success i { animation:pulse 0.4s ease; }

.modal-btn-cart {
    width:100%; padding:11px; border:1.5px solid #eee; border-radius:14px;
    background:white; color:#1A1C1E; font-weight:700; font-size:0.88rem;
    font-family:'Plus Jakarta Sans',sans-serif;
    cursor:pointer; transition:all 0.2s ease; text-decoration:none;
    display:flex; align-items:center; justify-content:center; gap:8px;
    animation:fadeUp 0.4s ease 0.35s both;
}
.modal-btn-cart:hover { border-color:#1A1C1E; background:#1A1C1E; color:white; }
</style>

<!-- Modal -->
<div id="itemModal" onclick="handleBackdropClick(event)">
    <div id="modalBox">
        <button class="modal-close-btn" onclick="closeModal()">
            <i class="bi bi-x-lg"></i>
        </button>

        <div class="modal-img-wrap" id="modalImgBox">
            <img id="modalImg" src="" alt="" style="display:none;">
            <i id="modalIcon" class="bi" style="display:none;"></i>
        </div>

        <div class="modal-body">
            <div class="modal-cat"  id="modalCat"></div>
            <h2  class="modal-name" id="modalName"></h2>
            <div class="modal-price-row">
                <span class="modal-price" id="modalPrice"></span>
                <span class="modal-unit"  id="modalUnit"></span>
            </div>
            <div class="modal-stock">
                <span class="modal-stock-dot" id="modalStockDot"></span>
                <span class="modal-stock-label" id="modalStockLabel"></span>
            </div>
            <p class="modal-desc" id="modalDesc"></p>

            <div class="modal-qty-row">
                <label>Quantity</label>
                <div class="modal-qty-wrap">
                    <button type="button" onclick="changeModalQty(-1)">−</button>
                    <input  type="number"  id="modalQty" value="1" min="1">
                    <button type="button" onclick="changeModalQty(1)">+</button>
                </div>
            </div>

            <button class="modal-btn-add" id="modalAddBtn" onclick="addToCartModal()">
                <i class="bi bi-bag-plus"></i> Add to Bag
            </button>
            <a href="<?= isset($cartUrl) ? $cartUrl : 'cart.php' ?>" class="modal-btn-cart">
                <i class="bi bi-bag"></i> View Cart
            </a>
        </div>
    </div>
</div>

<!-- Modal Js -->
<script>
let modalItemId   = null;
let modalMaxStock = 0;

const CART_PATH = typeof cartPath !== 'undefined' ? cartPath : 'addtocart.php';

function openModal(id, name, cat, price, imgSrc, grad, icon, stock, unit, desc) {
    modalItemId   = id;
    modalMaxStock = stock;

    document.getElementById('modalBox').classList.remove('closing');

    const img = document.getElementById('modalImg');
    const ico = document.getElementById('modalIcon');
    if (imgSrc) {
        img.src = imgSrc; img.style.display = 'block'; ico.style.display = 'none';
    } else {
        ico.className = 'bi ' + icon; ico.style.display = 'block'; img.style.display = 'none';
    }
    document.getElementById('modalImgBox').style.background = grad;

    const dot   = document.getElementById('modalStockDot');
    const label = document.getElementById('modalStockLabel');
    dot.className = 'modal-stock-dot';
    if (stock <= 0) {
        dot.classList.add('out'); label.textContent = 'Out of stock';
    } else if (stock <= 5) {
        dot.classList.add('low'); label.textContent = 'Low stock — ' + stock + ' left';
    } else {
        label.textContent = 'In stock';
    }

    document.getElementById('modalCat').textContent   = cat;
    document.getElementById('modalName').textContent  = name;
    document.getElementById('modalPrice').textContent = 'RM ' + parseFloat(price).toFixed(2);
    document.getElementById('modalUnit').textContent  = unit ? 'per ' + unit : '';
    document.getElementById('modalDesc').textContent  = desc || '';
    document.getElementById('modalQty').value = 1;
    document.getElementById('modalQty').max   = stock;

    const btn = document.getElementById('modalAddBtn');
    btn.disabled         = (stock <= 0);
    btn.className        = 'modal-btn-add';
    btn.style.background = '';
    btn.style.color      = '';
    btn.innerHTML        = stock <= 0
        ? '<i class="bi bi-x-circle"></i> Out of Stock'
        : '<i class="bi bi-bag-plus"></i> Add to Bag';

    document.getElementById('itemModal').classList.add('open');
    document.body.style.overflow = 'hidden';
}

function closeModal() {
    const modal = document.getElementById('itemModal');
    const box   = document.getElementById('modalBox');
    box.classList.add('closing');
    modal.classList.add('closing');
    setTimeout(() => {
        modal.classList.remove('open', 'closing');
        box.classList.remove('closing');
        document.body.style.overflow = '';
    }, 250);
}

function handleBackdropClick(e) {
    if (e.target === document.getElementById('itemModal')) closeModal();
}

document.addEventListener('keydown', e => { if (e.key === 'Escape') closeModal(); });

function changeModalQty(delta) {
    const input = document.getElementById('modalQty');
    let val = parseInt(input.value) + delta;
    if (val < 1)             val = 1;
    if (val > modalMaxStock) val = modalMaxStock;
    input.value = val;
}

async function addToCartModal() {
    if (!isLoggedIn) { window.location.href = '../auth/login.php'; return; }

    const qty  = parseInt(document.getElementById('modalQty').value) || 1;
    const btn  = document.getElementById('modalAddBtn');
    const orig = btn.innerHTML;

    btn.disabled  = true;
    btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Adding...';

    try {
        const fd = new FormData();
        fd.append('item_id',  modalItemId);
        fd.append('quantity', qty);

        const res  = await fetch(CART_PATH, { method: 'POST', body: fd });
        const data = await res.json();

        if (data.status === 'success') {
            btn.classList.add('success');
            btn.innerHTML = '<i class="bi bi-check-lg"></i> Added!';

            const cartBadge = document.getElementById('cartBadge');
            if (cartBadge) {
                cartBadge.style.display = 'inline-flex';
                cartBadge.textContent = parseInt(cartBadge.textContent || 0) + qty;
            } else {
                const countEl = document.querySelector('.floating-cart .cart-count');
                if (countEl) {
                    countEl.textContent = parseInt(countEl.textContent || 0) + qty;
                } else {
                    const span = document.querySelector('.floating-cart span.fw-bold');
                    if (span) {
                        const b = document.createElement('span');
                        b.className = 'cart-count ms-1'; b.textContent = qty;
                        span.appendChild(b);
                    }
                }
            }

            setTimeout(() => { btn.classList.remove('success'); btn.innerHTML = orig; btn.disabled = false; btn.style.background = ''; btn.style.color = ''; }, 1800);
        } else {
            btn.style.background = '#F8F9FA';
            btn.style.color = '#e17055';
            btn.innerHTML = '<i class="bi bi-exclamation-circle"></i> Failed';
            setTimeout(() => { btn.innerHTML = orig; btn.style.background = ''; btn.style.color = ''; btn.disabled = false; }, 1800);
        }
    } catch (err) {
        btn.innerHTML = '<i class="bi bi-exclamation-circle"></i> Error';
        setTimeout(() => { btn.innerHTML = orig; btn.style.background = ''; btn.style.color = ''; btn.disabled = false; }, 1800);
    }
}
</script>