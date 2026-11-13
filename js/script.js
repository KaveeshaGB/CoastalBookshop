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
        const packaging = document.getElementById('packaging');
        const orderType = document.getElementById('order-type');
        const gift      = document.getElementById('gift-fields');
        const total     = document.getElementById('order-total');
        const payment   = document.getElementById('payment');
        const cardBox   = document.getElementById('card-fields');
        const cardName  = document.getElementById('card-name');
        const cardNum   = document.getElementById('card-number');
        const cardExp   = document.getElementById('card-expiry');
        const cardCvv   = document.getElementById('card-cvv');

        function fillBooks() {
            nameSel.innerHTML = '';
            window.BOOKS.filter(b => b.Category === typeSel.value).forEach(function (b) {
                const opt = document.createElement('option');
                opt.value = b.BookID;
                if (Number(b.Stock) <= 0) {
                    // Sold out: still listed, but cannot be chosen
                    opt.textContent = b.Title + ' - OUT OF STOCK';
                    opt.disabled = true;
                } else {
                    opt.textContent = b.Title + ' - Rs.' + Number(b.FinalPrice).toFixed(2) +
                                      (b.OnOffer ? ' (' + b.DiscountPercent + '% off)' : '');
                    if (b.BookID === window.SELECTED_BOOK) opt.selected = true;
                }
                nameSel.appendChild(opt);
            });
            updateTotal();
        }

        function updateTotal() {
            const note = document.getElementById('stock-note');
            const book = window.BOOKS.find(b => String(b.BookID) === nameSel.value);
            if (!book) {
                total.textContent = '';
                if (note) {
                    note.textContent = 'Every book in this category is out of stock right now.';
                    note.hidden = false;
                }
                return;
            }
            // Never let the quantity go above the copies left
            const stock = Number(book.Stock);
            qty.max = Math.max(1, Math.min(20, stock));
            if (note) {
                if (Number(qty.value) > stock) {
                    note.textContent = 'Only ' + stock + ' in stock.';
                    note.hidden = false;
                } else {
                    note.hidden = true;
                }
            }
            let sum = Number(book.FinalPrice) * Number(qty.value || 1) + window.DELIVERY_COSTS[delivery.value];
            if (packaging.value === 'Protective') sum += window.PROTECTIVE_BOX_FEE;   // protective box: +Rs.200
            // gift order: +Rs.500, but free if "No decoration" is chosen
            const deco = document.querySelector('input[name="decoration_type"]:checked');
            if (orderType.value === 'Gift' && deco && deco.value !== 'None') sum += window.GIFT_FEE;
            total.textContent = 'Estimated total: Rs.' + sum.toFixed(2);
        }

        function toggleGift() {
            const isGift = orderType.value === 'Gift';
            gift.hidden = !isGift;
            document.getElementById('recipient-name').required = isGift;
        }

        // Show the card boxes only when "Card" is chosen
        function toggleCard() {
            const isCard = payment.value === 'Card';
            cardBox.hidden = !isCard;
            [cardName, cardNum, cardExp, cardCvv].forEach(function (f) { f.required = isCard; });
        }

        // Card number: digits only, grouped in 4s
        cardNum.addEventListener('input', function () {
            const digits = cardNum.value.replace(/\D/g, '').slice(0, 16);
            cardNum.value = digits.replace(/(.{4})/g, '$1 ').trim();
            cardNum.setCustomValidity('');
        });

        // Expiry: digits only, slash added automatically
        cardExp.addEventListener('input', function () {
            const digits = cardExp.value.replace(/\D/g, '').slice(0, 4);
            cardExp.value = digits.length > 2 ? digits.slice(0, 2) + '/' + digits.slice(2) : digits;
            cardExp.setCustomValidity('');
        });

        // CVV: digits only
        cardCvv.addEventListener('input', function () {
            cardCvv.value = cardCvv.value.replace(/\D/g, '');
        });

        orderForm.addEventListener('submit', function (e) {
            if (payment.value !== 'Card') return;

            const digits = cardNum.value.replace(/\s/g, '');
            cardNum.setCustomValidity(digits.length === 16 ? '' : 'Enter a 16-digit card number.');

            const m = /^(0[1-9]|1[0-2])\/(\d{2})$/.exec(cardExp.value);
            let expMsg = 'Enter the expiry date as MM/YY.';
            if (m) {
                // Valid until the end of the expiry month
                const endOfMonth = new Date(2000 + Number(m[2]), Number(m[1]), 1);
                expMsg = endOfMonth > new Date() ? '' : 'That card has expired.';
            }
            cardExp.setCustomValidity(expMsg);

            cardCvv.setCustomValidity(/^\d{3,4}$/.test(cardCvv.value) ? '' : 'Enter a 3 or 4 digit CVV.');

            if (!orderForm.checkValidity()) {
                e.preventDefault();
                orderForm.reportValidity();
            }
        });

        // If opened from an "Order Now" link, start on that book's category
        const pre = window.BOOKS.find(b => b.BookID === window.SELECTED_BOOK);
        if (pre) typeSel.value = pre.Category;

        typeSel.addEventListener('change', fillBooks);
        nameSel.addEventListener('change', updateTotal);
        qty.addEventListener('input', updateTotal);
        delivery.addEventListener('change', updateTotal);
        packaging.addEventListener('change', updateTotal);
        orderType.addEventListener('change', function () { toggleGift(); updateTotal(); });
        document.querySelectorAll('input[name="decoration_type"]').forEach(function (r) {
            r.addEventListener('change', updateTotal);
        });
        payment.addEventListener('change', toggleCard);

        fillBooks();
        toggleGift();
        toggleCard();
    }
});