// Cart System
let cart = JSON.parse(localStorage.getItem('cart')) || [];

function saveCart() {
    localStorage.setItem('cart', JSON.stringify(cart));
    updateCartUI();
}

function updateCartUI() {
    const cartCount = document.getElementById('cart-count');
    const cartItems = document.getElementById('cart-items');
    const cartFooter = document.getElementById('cart-footer');
    const cartTotal = document.getElementById('cart-total');

    if (!cartCount || !cartItems) return;

    cartCount.textContent = cart.length;

    if (cart.length === 0) {
        cartItems.innerHTML = `
            <div class="cart-empty">
                <i class="bi bi-bag"></i>
                <p>Seu carrinho está vazio</p>
                <p style="font-size: 0.9rem;">Adicione produtos para continuar</p>
            </div>
        `;
        cartFooter.style.display = 'none';
    } else {
        let total = 0;
        let html = '';
        
        cart.forEach((item, index) => {
            const price = parseFloat(item.price.replace('R$', '').replace('.', '').replace(',', '.'));
            total += price;
            
            html += `
                <div class="cart-item">
                    <div class="cart-item-image">
                        <img src="${item.image}" alt="${item.name}">
                    </div>
                    <div class="cart-item-info">
                        <div class="cart-item-title">${item.name}</div>
                        <div class="cart-item-price">${item.price}</div>
                    </div>
                    <button class="cart-item-remove" onclick="removeFromCart(${index})" aria-label="Remover item">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            `;
        });
        
        cartItems.innerHTML = html;
        cartFooter.style.display = 'block';
        cartTotal.textContent = 'R$ ' + total.toFixed(2).replace('.', ',');
    }
}

function addToCart(name, price, image) {
    cart.push({ name, price, image });
    saveCart();
    showToast('Produto adicionado ao carrinho!');
}

function removeFromCart(index) {
    cart.splice(index, 1);
    saveCart();
    showToast('Produto removido do carrinho');
}

function openCart() {
    document.getElementById('cart-offcanvas').classList.add('open');
    document.getElementById('cart-overlay').classList.add('open');
    document.body.style.overflow = 'hidden';
}

function closeCart() {
    document.getElementById('cart-offcanvas').classList.remove('open');
    document.getElementById('cart-overlay').classList.remove('open');
    document.body.style.overflow = '';
}

function showToast(message) {
    const toast = document.getElementById('toast-notification');
    document.getElementById('toast-message').textContent = message;
    toast.classList.add('show');
    setTimeout(() => {
        toast.classList.remove('show');
    }, 3000);
}

function finalizeOrder() {
    if (cart.length === 0) return;

    const name = prompt('Digite seu nome:');
    if (!name || name.trim() === '') {
        alert('Por favor, digite seu nome para finalizar a compra.');
        return;
    }

    let message = `Olá! Meu nome é ${name.trim()} e tenho interesse nos seguintes produtos:\n\n`;
    let total = 0;

    cart.forEach((item, index) => {
        message += `${index + 1}. ${item.name} - ${item.price}\n`;
        const price = parseFloat(item.price.replace('R$', '').replace('.', '').replace(',', '.'));
        total += price;
    });

    message += `\nTotal: R$ ${total.toFixed(2).replace('.', ',')}\n\n`;
    message += 'Gostaria de finalizar a compra. Aguardo retorno!';

    const encodedMessage = encodeURIComponent(message);
    const phone = '5514997912841';
    const whatsappUrl = `https://wa.me/${phone}?text=${encodedMessage}`;

    window.open(whatsappUrl, '_blank');
}
