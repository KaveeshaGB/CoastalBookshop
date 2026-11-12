console.log('book-actions.js loaded');

// Small confirmation box that slides in at the top right (styles are in css/cart.css)
function showCartMessage(html) {
    let box = document.getElementById('cart-toast');
    if (!box) {
        box = document.createElement('div');
        box.id = 'cart-toast';
        document.body.appendChild(box);
    }
    box.innerHTML = html;
    box.classList.add('show');
    clearTimeout(box.hideTimer);
    box.hideTimer = setTimeout(function () { box.classList.remove('show'); }, 3000);
}
 
document.addEventListener('click', async function (event) {
    const heartBtn = event.target.closest('.heart-btn');
    const cartBtn  = event.target.closest('.cart-btn');
    if (!heartBtn && !cartBtn) return;
 
    if (heartBtn) {
        const response = await fetch('like_toggle.php', {
            method: 'POST',
            body: new URLSearchParams({ book: heartBtn.dataset.book })
        });
        if (!response.ok) {
            alert('Could not complete that. Please try again.');
            return;
        }
        const data = await response.json();
        heartBtn.classList.toggle('liked', data.liked);
        return;
    }
 
    // ---- Add to cart ----
        let data;
    try {
        const response = await fetch('cart.php', {
            method: 'POST',
            body: new URLSearchParams({ action: 'add', book_id: cartBtn.dataset.book, quantity: 1 })
        });
        const text = await response.text();
        try {
            data = JSON.parse(text);
        } catch (err) {
            alert('cart.php did not send clean JSON (status ' + response.status + '):\n\n' + text.slice(0, 500));
            return;
        }
    } catch (err) {
        alert('Request failed: ' + err.message);
        return;
    }
 
    if (!data.ok) {
        // Not logged in: open the log-in pop-up
        if (data.login) {
            const loginLink = document.getElementById('open-login');
            if (loginLink) { loginLink.click(); return; }
        }
        alert(data.message);
        return;
    }
 
    cartBtn.classList.add('added');
    setTimeout(function () { cartBtn.classList.remove('added'); }, 800);
 
    showCartMessage('&#10003; "' + escapeHtml(data.title) + '" added to your cart. ' +
                    '<a href="cart.php">View cart</a>');
});
 
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}



 
