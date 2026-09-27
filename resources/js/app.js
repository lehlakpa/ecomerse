const toggle = document.querySelector('.menu-toggle');
const navigation = document.querySelector('#navigation');
toggle?.addEventListener('click', () => {
    const open = toggle.getAttribute('aria-expanded') !== 'true';
    toggle.setAttribute('aria-expanded', String(open));
    navigation.classList.toggle('is-open', open);
});
document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && toggle?.getAttribute('aria-expanded') === 'true') {
        toggle.setAttribute('aria-expanded', 'false');
        navigation.classList.remove('is-open');
        toggle.focus();
    }
});

const desktopNavigation = window.matchMedia('(min-width: 1101px)');
desktopNavigation.addEventListener('change', () => {
    toggle?.setAttribute('aria-expanded', 'false');
    navigation?.classList.remove('is-open');
});
document.querySelectorAll('[data-image]').forEach(button => {
    button.addEventListener('click', () => {
        document.querySelector('#main-product-image').src = button.dataset.image;
        document.querySelectorAll('[data-image]').forEach(item => item.setAttribute('aria-pressed', String(item === button)));
    });
});
document.querySelectorAll('[data-confirm]').forEach(form => {
    form.addEventListener('submit', event => {
        if (!window.confirm(form.dataset.confirm)) event.preventDefault();
    });
});
const orderForm = document.querySelector('.order-form');
if (orderForm) {
    const quantity = orderForm.querySelector('[name="quantity"]');
    quantity.addEventListener('input', () => {
        const total = Math.round(Number(orderForm.dataset.price) * 100) * Number(quantity.value) / 100;
        orderForm.querySelector('[data-order-total]').textContent = quantity.validity.valid ? 'Rs. ' + total.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : 'Choose 1?20 items';
    });
}
const uploadInput = document.querySelector('[data-preview-input]');
let previewUrls = [];
uploadInput?.addEventListener('change', () => {
    previewUrls.forEach(url => URL.revokeObjectURL(url));
    previewUrls = [];
    const container = document.querySelector('[data-preview-container]');
    container.replaceChildren();
    const files = [...uploadInput.files];
    const invalid = files.length > 6 || files.some(file => file.size > 5 * 1024 * 1024 || !['image/jpeg', 'image/png', 'image/webp'].includes(file.type));
    uploadInput.setCustomValidity(invalid ? 'Choose up to 6 JPG, PNG or WebP images, 5 MB each.' : '');
    if (invalid) { uploadInput.reportValidity(); return; }
    files.forEach(file => {
        const image = new Image();
        image.src = URL.createObjectURL(file);
        previewUrls.push(image.src);
        image.alt = file.name;
        image.className = 'existing-image';
        container.append(image);
    });
});
