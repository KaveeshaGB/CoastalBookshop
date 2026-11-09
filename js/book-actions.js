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

    const response = await fetch('cart.php', {
        method: 'POST',
        body: new URLSearchParams({ action: 'add', book_id: cartBtn.dataset.book, quantity: 1 })
    });
    if (!response.ok) {
        alert('Could not complete that. Please try again.');
        return;
    }
    const data = await response.json();

    if (!data.ok) {
        alert(data.message);
        if (data.login) {
            const loginLink = document.getElementById('open-login');
            if (loginLink) loginLink.click();
        }
        return;
    }

    cartBtn.classList.add('added');
    setTimeout(function () { cartBtn.classList.remove('added'); }, 800);
});