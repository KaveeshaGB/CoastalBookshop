const profileBtn = document.getElementById('profile-btn');
const profileDropdown = document.getElementById('profile-dropdown');

if (profileBtn && profileDropdown) {
    profileBtn.addEventListener('click', function (event) {
        event.stopPropagation();
        profileDropdown.classList.toggle('open');
    });

    document.addEventListener('click', function () {
        profileDropdown.classList.remove('open');
    });
}
// ---------- Log-in pop-up ----------
document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('login-modal');
    if (modal) {
        const open  = document.getElementById('open-login');
        const close = document.getElementById('close-login');
        const form  = document.getElementById('login-form');
        const error = document.getElementById('login-error');

        open.addEventListener('click', function (e) { e.preventDefault(); modal.hidden = false; });
        close.addEventListener('click', function () { modal.hidden = true; });
        modal.addEventListener('click', function (e) { if (e.target === modal) modal.hidden = true; });

        form.addEventListener('submit', async function (e) {
            e.preventDefault();
            error.hidden = true;
            try {
                const res  = await fetch('login.php', { method: 'POST', body: new FormData(form) });
                const data = await res.json();
                if (data.ok) {
                    window.location.reload();
                } else {
                    error.textContent = data.message;
                    error.hidden = false;
                }
            } catch (err) {
                error.textContent = 'Something went wrong. Try again.';
                error.hidden = false;
            }
        });
    }

    // ---------- Register form ----------
    const reg = document.getElementById('register-form');
    if (reg) {
        reg.addEventListener('submit', function (e) {
            if (document.getElementById('password').value !== document.getElementById('confirm').value) {
                e.preventDefault();
                alert('Passwords do not match.');
            }
        });
    }

    // ---------- Order form ----------
    const orderForm = document.getElementById('order-form');
    if (orderForm) {
        const typeSel   = document.getElementById('book-type');
        const nameSel   = document.getElementById('book-name');
        const qty       = document.getElementById('quantity');
        const delivery  = document.getElementById('delivery-method');
        const orderType = document.getElementById('order-type');
        const gift      = document.getElementById('gift-fields');
        const total     = document.getElementById('order-total');

        function fillBooks() {
            nameSel.innerHTML = '';
            window.BOOKS.filter(b => b.Category === typeSel.value).forEach(function (b) {
                const opt = document.createElement('option');
                opt.value = b.BookID;
                opt.textContent = b.Title + ' - Rs.' + Number(b.Price).toFixed(2);
                if (Number(b.BookID) === window.SELECTED_BOOK) opt.selected = true;
                nameSel.appendChild(opt);
            });
            updateTotal();
        }

        function updateTotal() {
            const book = window.BOOKS.find(b => String(b.BookID) === nameSel.value);
            if (!book) { total.textContent = ''; return; }
            const sum = Number(book.Price) * Number(qty.value || 1) + window.DELIVERY_COSTS[delivery.value];
            total.textContent = 'Estimated total: Rs.' + sum.toFixed(2);
        }

        function toggleGift() {
            const isGift = orderType.value === 'Gift';
            gift.hidden = !isGift;
            document.getElementById('recipient-name').required = isGift;
        }

        // If opened from an "Order Now" link, start on that book's category
        const pre = window.BOOKS.find(b => Number(b.BookID) === window.SELECTED_BOOK);
        if (pre) typeSel.value = pre.Category;

        typeSel.addEventListener('change', fillBooks);
        nameSel.addEventListener('change', updateTotal);
        qty.addEventListener('input', updateTotal);
        delivery.addEventListener('change', updateTotal);
        orderType.addEventListener('change', toggleGift);

        fillBooks();
        toggleGift();
    }
});
