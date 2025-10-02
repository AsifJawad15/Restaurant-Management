// Simple cart client that persists in session via PHP endpoints
document.addEventListener('DOMContentLoaded', function() {
    // Qty controls on menu cards
    document.querySelectorAll('.btn-qty').forEach(btn => {
        btn.addEventListener('click', function() {
            const op = this.getAttribute('data-op');
            const input = this.parentElement.querySelector('.qty-input');
            const val = parseInt(input.value || '1', 10);
            input.value = Math.max(1, val + (op === '+' ? 1 : -1));
        });
    });

    // Add to cart
    document.querySelectorAll('.btn-add').forEach(btn => {
        btn.addEventListener('click', async function() {
            const card = this.closest('.card');
            const qty = parseInt(card.querySelector('.qty-input').value || '1', 10);
            const payload = new URLSearchParams();
            payload.set('action', 'add');
            payload.set('id', this.dataset.id);
            payload.set('name', this.dataset.name);
            payload.set('price', this.dataset.price);
            payload.set('qty', String(qty));
            await fetch(window.CART_ENDPOINT, { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: payload.toString() });
            await refreshCart();
        });
    });

    // Clear cart
    document.getElementById('btnClearCart')?.addEventListener('click', async () => {
        const payload = new URLSearchParams();
        payload.set('action', 'clear');
        await fetch(window.CART_ENDPOINT, { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: payload.toString() });
        await refreshCart();
    });

    // Checkout - redirect to checkout page
    document.getElementById('btnCheckout')?.addEventListener('click', () => {
        window.location.href = 'checkout.php';
    });

    refreshCart();
});

async function refreshCart() {
    const res = await fetch(window.CART_ENDPOINT + '?action=get');
    const data = await res.json();
    const list = document.getElementById('cartItems');
    list.innerHTML = '';
    let subtotal = 0;
    data.items.forEach(item => {
        subtotal += item.total;
        const row = document.createElement('div');
        row.className = 'list-group-item';
        row.innerHTML = `
            <span class="cart-item-name">${escapeHtml(item.name)}</span>
            <span class="qty-badge badge bg-secondary me-2">${item.qty}</span>
            <span>$${item.total.toFixed(2)}</span>
            <button class="btn btn-sm btn-outline-danger ms-2" data-remove="${item.id}"><i class="fas fa-times"></i></button>
        `;
        row.querySelector('[data-remove]')?.addEventListener('click', async (e) => {
            const id = e.currentTarget.getAttribute('data-remove');
            const payload = new URLSearchParams();
            payload.set('action', 'remove');
            payload.set('id', id);
            await fetch(window.CART_ENDPOINT, { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: payload.toString() });
            await refreshCart();
        });
        list.appendChild(row);
    });
    document.getElementById('subtotal').textContent = `$${subtotal.toFixed(2)}`;
    // Assume tax is 8.5% if present in data; else 0
    const taxRate = data.taxRate ?? 0;
    const tax = subtotal * (taxRate / 100.0);
    document.getElementById('tax').textContent = `$${tax.toFixed(2)}`;
    document.getElementById('total').textContent = `$${(subtotal + tax).toFixed(2)}`;
}

function escapeHtml(str) {
    return str.replace(/[&<>"]+/g, s => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[s]));
}
