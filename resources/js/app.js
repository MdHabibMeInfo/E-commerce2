document.addEventListener('DOMContentLoaded', () => {
    let cart = [];

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const apiHeaders = {
        Accept: 'application/json',
        ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {}),
    };

    const money = (amount) => `৳${Number(amount || 0).toLocaleString('en-BD', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    })}`;

    const normalizeCartItem = (item) => ({
        ...item,
        qty: Number(item.qty ?? item.quantity ?? 0),
        price: Number(item.price ?? 0),
        image_url: item.image_url || item.image || '',
        emoji: item.emoji || '🛍️',
    });

    const cartUi = document.createElement('div');
    cartUi.innerHTML = `
        <button type="button" data-floating-cart aria-label="Open shopping cart" aria-expanded="false"
            class="fixed right-0 top-[58%] z-[80] flex w-[68px] -translate-y-1/2 flex-col items-center overflow-hidden rounded-l-xl border border-[#e7e4d8] bg-white text-center shadow-lg transition-transform hover:-translate-x-1 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#145943] sm:w-[76px]">
            <span class="flex w-full flex-col items-center bg-[#145943] px-2 py-2.5 text-white">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                    <path d="M5 8h14l1 13H4L5 8Z"/><path d="M9 8V6a3 3 0 0 1 6 0v2"/>
                </svg>
                <span data-cart-count class="mt-1 text-[11px] font-semibold">0 Items</span>
            </span>
            <span data-cart-total class="w-full px-1 py-1.5 text-[11px] font-bold text-[#a48335]">৳0.00</span>
        </button>

        <button type="button" data-scroll-to-top aria-label="Back to top" aria-hidden="true" tabindex="-1" inert
            class="fixed bottom-6 right-6 z-[79] grid h-12 w-12 translate-y-2 place-items-center rounded-full opacity-0 transition-all duration-300 ease-out pointer-events-none motion-reduce:transition-none">
            <svg class="pointer-events-none absolute inset-0 h-full w-full -rotate-90" viewBox="0 0 48 48" fill="none" aria-hidden="true">
                <circle cx="24" cy="24" r="22" stroke="#d1d5ce" stroke-width="2" />
                <circle data-scroll-progress cx="24" cy="24" r="22" stroke="#145943" stroke-width="2" stroke-linecap="round" stroke-dasharray="138.23" stroke-dashoffset="138.23" />
            </svg>
            <span class="grid h-9 w-9 place-items-center rounded-full bg-[#145943] text-white shadow-sm transition-transform duration-200 hover:scale-105" aria-hidden="true">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path d="m5 14 7-7 7 7" />
                </svg>
            </span>
        </button>

        <div data-cart-overlay class="fixed inset-0 z-[89] hidden bg-[#102a22]/45 opacity-0 transition-opacity duration-200" aria-hidden="true"></div>

        <aside data-cart-drawer aria-label="Shopping cart" aria-modal="true" role="dialog"
            class="fixed inset-y-0 right-0 z-[90] flex w-full max-w-[420px] translate-x-full flex-col bg-white shadow-2xl transition-transform duration-300 ease-out" aria-hidden="true">
            <div class="flex items-center justify-between border-b border-gray-200 bg-white px-5 py-4">
                <h2 class="text-sm font-bold tracking-wider text-gray-900 uppercase">SHOPPING CART</h2>
                <button type="button" data-close-cart aria-label="Close cart" class="group flex items-center gap-1 text-xs font-semibold text-gray-500 transition hover:text-gray-900">
                    <span>Close</span>
                    <span class="text-sm font-bold transition group-hover:translate-x-0.5">→</span>
                </button>
            </div>

            <div data-cart-items class="flex-1 space-y-3 overflow-y-auto px-5 py-4 divide-y divide-gray-100"></div>

            <div class="border-t border-gray-200 bg-white p-5">
                <div class="mb-4 flex items-center justify-between text-sm">
                    <span class="font-medium text-gray-700">Total:</span>
                    <strong data-cart-total-amount class="text-base font-bold text-gray-900">৳0.00</strong>
                </div>
                <button type="button" data-cart-checkout class="w-full rounded-xl bg-[#145943] py-3.5 text-center text-sm font-bold uppercase tracking-wider text-white shadow-sm transition hover:bg-[#d96e16] active:scale-[0.99]">
                    CHECKOUT
                </button>
            </div>
        </aside>

        <div data-cart-loading-overlay role="status" aria-label="Adding product to cart" aria-hidden="true" inert
            class="pointer-events-none fixed inset-0 z-[94] grid place-items-center opacity-0 transition-opacity duration-100">
            <span class="relative grid h-20 w-20 place-items-center text-[#145943]" aria-hidden="true">
                <span class="absolute inset-0 rounded-full border-[3px] border-[#145943]/15 border-t-[#145943] motion-safe:animate-[spin_0.65s_linear_infinite]"></span>
                <svg class="h-9 w-9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
                    <path d="M3 3h2l2.4 12.1a2 2 0 0 0 2 1.6h8.8a2 2 0 0 0 2-1.6L22 7H6" />
                    <circle cx="9" cy="21" r="1" />
                    <circle cx="19" cy="21" r="1" />
                </svg>
            </span>
        </div>

        <div data-cart-toast role="status" aria-live="polite" aria-atomic="true" aria-hidden="true" inert
            class="pointer-events-none fixed right-3 top-4 z-[95] flex w-[min(92vw,380px)] translate-x-[calc(100%_+_100px)] items-start gap-3 rounded-xl border border-emerald-100/70 bg-emerald-500/55 p-4 text-white opacity-0 shadow-xl shadow-emerald-950/10 backdrop-blur-2xl transition-all duration-300 ease-out sm:right-[88px]">
            <span class="grid h-10 w-10 shrink-0 place-items-center rounded-full border border-white/20 bg-white/20">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path d="m5 12 4 4L19 6"></path>
                </svg>
            </span>
            <span class="min-w-0 flex-1 pt-0.5">
                <span class="block text-sm font-bold">Added to your cart</span>
                <span data-cart-toast-message class="mt-0.5 block truncate text-xs text-emerald-50"></span>
            </span>
            <button type="button" data-dismiss-cart-toast aria-label="Dismiss notification" class="grid h-7 w-7 shrink-0 place-items-center rounded-full text-white/80 transition hover:bg-white/20 hover:text-white">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path d="m6 6 12 12M18 6 6 18"></path>
                </svg>
            </button>
        </div>
    `;

    document.body.appendChild(cartUi);

    const floatingCart = cartUi.querySelector('[data-floating-cart]');
    if (document.body.dataset.adminArea === 'true') {
        floatingCart.classList.add('hidden');
    }

    const adminNotificationsLink = document.querySelector('[data-admin-notifications]');
    const adminNotificationCount = document.querySelector('[data-admin-notification-count]');

    if (adminNotificationsLink && adminNotificationCount) {
        const refreshAdminNotificationCount = async () => {
            try {
                const response = await fetch(adminNotificationsLink.dataset.countUrl, {
                    headers: { Accept: 'application/json' },
                    cache: 'no-store',
                });
                if (!response.ok) return;

                const result = await response.json();
                const count = Math.max(0, Number(result.count) || 0);
                adminNotificationCount.textContent = count > 99 ? '99+' : String(count);
                adminNotificationCount.classList.toggle('hidden', count === 0);
                adminNotificationCount.classList.toggle('grid', count > 0);
                adminNotificationsLink.classList.toggle('admin-notification-has-unread', count > 0);
                adminNotificationsLink.setAttribute('aria-label', count > 0 ? `Admin notifications, ${count} new` : 'Admin notifications');
            } catch {
                return;
            }
        };

        refreshAdminNotificationCount();
        window.setInterval(refreshAdminNotificationCount, 30000);
    }

    const scrollToTopButton = cartUi.querySelector('[data-scroll-to-top]');
    const scrollProgressCircle = cartUi.querySelector('[data-scroll-progress]');
    const overlay = cartUi.querySelector('[data-cart-overlay]');
    const drawer = cartUi.querySelector('[data-cart-drawer]');
    const itemsContainer = cartUi.querySelector('[data-cart-items]');
    const cartLoadingOverlay = cartUi.querySelector('[data-cart-loading-overlay]');
    const cartToast = cartUi.querySelector('[data-cart-toast]');
    let cartToastTimer = null;
    let cartToastVisible = false;

    const updateScrollToTopButton = () => {
        const maximumScroll = document.documentElement.scrollHeight - window.innerHeight;
        const progress = maximumScroll > 0 ? Math.min(window.scrollY / maximumScroll, 1) : 0;
        const isVisible = window.scrollY > 180;

        scrollProgressCircle.style.strokeDashoffset = String(138.23 * (1 - progress));
        scrollToTopButton.classList.toggle('translate-y-2', !isVisible);
        scrollToTopButton.classList.toggle('opacity-0', !isVisible);
        scrollToTopButton.classList.toggle('pointer-events-none', !isVisible);
        scrollToTopButton.setAttribute('aria-hidden', String(!isVisible));
        scrollToTopButton.toggleAttribute('inert', !isVisible);
        scrollToTopButton.tabIndex = isVisible ? 0 : -1;
    };

    let scrollUpdateFrame = null;
    const scheduleScrollToTopUpdate = () => {
        if (scrollUpdateFrame !== null) return;

        scrollUpdateFrame = window.requestAnimationFrame(() => {
            updateScrollToTopButton();
            scrollUpdateFrame = null;
        });
    };

    window.addEventListener('scroll', scheduleScrollToTopUpdate, { passive: true });
    window.addEventListener('resize', scheduleScrollToTopUpdate);
    scrollToTopButton.addEventListener('click', () => {
        window.scrollTo({
            top: 0,
            behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth',
        });
    });
    updateScrollToTopButton();

    const showCartLoading = () => {
        cartLoadingOverlay.inert = false;
        cartLoadingOverlay.setAttribute('aria-hidden', 'false');
        cartLoadingOverlay.classList.remove('pointer-events-none', 'opacity-0');
    };

    const hideCartLoading = () => {
        cartLoadingOverlay.classList.add('pointer-events-none', 'opacity-0');
        cartLoadingOverlay.setAttribute('aria-hidden', 'true');
        cartLoadingOverlay.inert = true;
    };

    const hideCartToast = () => {
        cartToastVisible = false;
        cartToast.classList.add('pointer-events-none', 'translate-x-[calc(100%_+_100px)]', 'opacity-0');
        cartToast.setAttribute('aria-hidden', 'true');
        cartToast.inert = true;
    };

    const showCartToast = (productName) => {
        cartToast.querySelector('[data-cart-toast-message]').textContent = `${productName} added successfully.`;
        cartToastVisible = true;
        cartToast.inert = false;
        cartToast.setAttribute('aria-hidden', 'false');
        cartToast.classList.remove('pointer-events-none', 'translate-x-[calc(100%_+_100px)]', 'opacity-0');
        window.clearTimeout(cartToastTimer);
        cartToastTimer = window.setTimeout(hideCartToast, 2500);
    };

    cartToast.querySelector('[data-dismiss-cart-toast]').addEventListener('click', hideCartToast);

    const setCartDrawerState = (open) => {
        if (open) {
            overlay.classList.remove('hidden');
            drawer.classList.remove('translate-x-full');
            drawer.setAttribute('aria-hidden', 'false');
            floatingCart.setAttribute('aria-expanded', 'true');
            overlay.setAttribute('aria-hidden', 'false');
            requestAnimationFrame(() => overlay.classList.remove('opacity-0'));
            document.body.classList.add('overflow-hidden');
            cartUi.querySelector('[data-close-cart]')?.focus();
            return;
        }

        overlay.classList.add('opacity-0');
        drawer.classList.add('translate-x-full');
        drawer.setAttribute('aria-hidden', 'true');
        floatingCart.setAttribute('aria-expanded', 'false');
        overlay.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('overflow-hidden');
        window.setTimeout(() => overlay.classList.add('hidden'), 220);
        floatingCart.focus();
    };

    const renderCart = () => {
        const quantity = cart.reduce((sum, item) => sum + Number(item.qty ?? item.quantity ?? 0), 0);
        const total = cart.reduce((sum, item) => sum + Number(item.price ?? 0) * Number(item.qty ?? item.quantity ?? 0), 0);

        cartUi.querySelector('[data-cart-count]').textContent = `${quantity} ${quantity === 1 ? 'Item' : 'Items'}`;
        cartUi.querySelector('[data-cart-total]').textContent = money(total);
        const totalAmountEl = cartUi.querySelector('[data-cart-total-amount]') || cartUi.querySelector('[data-cart-subtotal]');
        if (totalAmountEl) {
            totalAmountEl.textContent = money(total);
        }

        document.querySelectorAll('[data-cart-count-badge]').forEach((badge) => {
            badge.textContent = String(quantity);
        });

        itemsContainer.replaceChildren();

        if (!cart.length) {
            const empty = document.createElement('div');
            empty.className = 'flex h-full min-h-56 flex-col items-center justify-center text-center';
            empty.innerHTML = `
                <div class="grid h-16 w-16 place-items-center rounded-full bg-gray-100 text-3xl">🛒</div>
                <p class="mt-3 text-sm font-bold text-gray-800">Your cart is empty</p>
                <p class="mt-1 text-xs text-gray-500">Add some fresh products to get started.</p>
            `;
            itemsContainer.appendChild(empty);
            return;
        }

        cart.forEach((item) => {
            const row = document.createElement('div');
            row.className = 'flex items-center gap-3.5 pt-3.5 first:pt-0';

            const imgBox = document.createElement('div');
            imgBox.className = 'h-20 w-20 shrink-0 rounded-xl border border-gray-200 bg-white p-1 flex items-center justify-center overflow-hidden';

            if (item.image_url) {
                const img = document.createElement('img');
                img.src = item.image_url;
                img.alt = item.name;
                img.className = 'h-full w-full object-contain';
                imgBox.appendChild(img);
            } else {
                const placeholder = document.createElement('span');
                placeholder.className = 'text-2xl';
                placeholder.textContent = item.emoji || '🛍️';
                imgBox.appendChild(placeholder);
            }
            row.appendChild(imgBox);

            const detail = document.createElement('div');
            detail.className = 'flex-1 min-w-0 pr-1';

            const name = document.createElement('p');
            name.className = 'text-xs sm:text-sm font-semibold text-gray-800 line-clamp-1 truncate';
            name.textContent = item.name;
            name.title = item.name;

            const bottomRow = document.createElement('div');
            bottomRow.className = 'mt-2 flex items-center gap-2 flex-wrap';

            const qtyBox = document.createElement('div');
            qtyBox.className = 'inline-flex items-center rounded border border-gray-300 bg-white text-xs';

            const currentQuantity = Number(item.qty ?? item.quantity ?? 1);

            const minusBtn = document.createElement('button');
            minusBtn.type = 'button';
            minusBtn.textContent = '−';
            minusBtn.className = 'w-6 h-6 flex items-center justify-center text-gray-600 hover:bg-gray-100 font-semibold transition';
            minusBtn.setAttribute('aria-label', 'Decrease quantity');
            minusBtn.addEventListener('click', async () => {
                if (!item.id) return;
                const nextQty = currentQuantity - 1;
                if (nextQty <= 0) {
                    await fetch(`/api/cart/items/${item.id}`, {
                        method: 'DELETE',
                        headers: { ...apiHeaders },
                        credentials: 'same-origin',
                    });
                } else {
                    await fetch(`/api/cart/items/${item.id}`, {
                        method: 'PATCH',
                        headers: { ...apiHeaders, 'Content-Type': 'application/json' },
                        credentials: 'same-origin',
                        body: JSON.stringify({ quantity: nextQty }),
                    });
                }
                const response = await fetch('/api/cart', { headers: apiHeaders, credentials: 'same-origin' });
                const data = await response.json();
                cart = (data.items || []).map(normalizeCartItem);
                renderCart();
            });

            const qtyNum = document.createElement('span');
            qtyNum.className = 'min-w-[20px] text-center text-xs font-bold text-gray-800 px-1';
            qtyNum.textContent = String(currentQuantity);

            const plusBtn = document.createElement('button');
            plusBtn.type = 'button';
            plusBtn.textContent = '+';
            plusBtn.className = 'w-6 h-6 flex items-center justify-center text-gray-600 hover:bg-gray-100 font-semibold transition';
            plusBtn.setAttribute('aria-label', 'Increase quantity');
            plusBtn.addEventListener('click', async () => {
                if (!item.id) return;
                const nextQty = currentQuantity + 1;
                await fetch(`/api/cart/items/${item.id}`, {
                    method: 'PATCH',
                    headers: { ...apiHeaders, 'Content-Type': 'application/json' },
                    credentials: 'same-origin',
                    body: JSON.stringify({ quantity: nextQty }),
                });
                const response = await fetch('/api/cart', { headers: apiHeaders, credentials: 'same-origin' });
                const data = await response.json();
                cart = (data.items || []).map(normalizeCartItem);
                renderCart();
            });

            qtyBox.append(minusBtn, qtyNum, plusBtn);

            const priceCalc = document.createElement('span');
            priceCalc.className = 'text-xs font-semibold text-gray-700 whitespace-nowrap';
            const itemPrice = Number(item.price || 0);
            const itemSubtotal = itemPrice * currentQuantity;
            priceCalc.textContent = `× ${money(itemPrice)} = ${money(itemSubtotal)}`;

            bottomRow.append(qtyBox, priceCalc);
            detail.append(name, bottomRow);
            row.appendChild(detail);

            const removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.className = 'shrink-0 p-1 text-gray-400 hover:text-red-500 transition';
            removeBtn.setAttribute('aria-label', 'Remove ' + item.name);
            removeBtn.innerHTML = `
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            `;
            removeBtn.addEventListener('click', async () => {
                if (!item.id) return;
                await fetch(`/api/cart/items/${item.id}`, {
                    method: 'DELETE',
                    headers: { ...apiHeaders },
                    credentials: 'same-origin',
                });
                const response = await fetch('/api/cart', { headers: apiHeaders, credentials: 'same-origin' });
                const data = await response.json();
                cart = (data.items || []).map(normalizeCartItem);
                renderCart();
            });
            row.appendChild(removeBtn);

            itemsContainer.appendChild(row);
        });
    };

    const loadCart = async () => {
        try {
            const response = await fetch('/api/cart', {
                headers: apiHeaders,
                credentials: 'same-origin',
            });

            if (!response.ok) throw new Error('Failed to load cart');

            const data = await response.json();
            cart = (data.items || []).map(normalizeCartItem);
            renderCart();
        } catch (error) {
            cart = [];
            renderCart();
        }
    };

    const addToCart = async (button, shouldCheckout = false) => {
        const productId = Number(button.dataset.productId || button.closest('[data-product-card]')?.dataset.productId || 0);
        const name = (button.dataset.productName || button.closest('[data-product-card]')?.dataset.productName || '').trim();
        const price = Number(button.dataset.productPrice || button.closest('[data-product-card]')?.dataset.productPrice || 0);

        if (!productId || !name || !Number.isFinite(price) || price <= 0) {
            return;
        }

        const buttonLabel = button.querySelector('[data-cart-button-label]');
        const buttonSpinner = button.querySelector('[data-cart-button-spinner]');
        const buttonSuccess = button.querySelector('[data-cart-button-success]');
        const originalLabel = buttonLabel?.textContent ?? button.textContent;
        button.disabled = true;
        button.setAttribute('aria-busy', 'true');
        if (buttonLabel) {
            buttonLabel.textContent = 'Adding...';
        } else {
            button.textContent = 'Adding...';
        }
        buttonSpinner?.classList.remove('hidden');
        buttonSuccess?.classList.add('hidden');
        showCartLoading();

        try {
            const response = await fetch('/api/cart/items', {
                method: 'POST',
                credentials: 'same-origin',
                headers: { ...apiHeaders, 'Content-Type': 'application/json' },
                body: JSON.stringify({ product_id: productId, quantity: 1 }),
            });

            if (!response.ok) throw new Error('Failed to add cart item');

            const data = await response.json();
            cart = (data.items || []).map(normalizeCartItem);
            renderCart();
            hideCartLoading();
            if (shouldCheckout) {
                window.location.href = '/checkout';
                return;
            }
            showCartToast(name);
            if (buttonLabel) {
                buttonLabel.textContent = 'Added!';
                buttonSpinner?.classList.add('hidden');
                buttonSuccess?.classList.remove('hidden');
            } else {
                button.textContent = 'Added ✓';
            }
        } catch (error) {
            if (buttonLabel) {
                buttonLabel.textContent = 'Error';
            } else {
                button.textContent = 'Error';
            }
        } finally {
            hideCartLoading();
            window.setTimeout(() => {
                if (buttonLabel) {
                    buttonLabel.textContent = originalLabel;
                    buttonSpinner?.classList.add('hidden');
                    buttonSuccess?.classList.add('hidden');
                } else {
                    button.textContent = originalLabel;
                }
                button.disabled = false;
                button.setAttribute('aria-busy', 'false');
            }, 1000);
        }
    };

    floatingCart.addEventListener('click', () => setCartDrawerState(true));
    overlay.addEventListener('click', () => setCartDrawerState(false));
    cartUi.querySelectorAll('[data-close-cart]').forEach((button) => {
        button.addEventListener('click', () => setCartDrawerState(false));
    });

    document.querySelectorAll('[data-open-cart]').forEach((button) => {
        button.addEventListener('click', (event) => {
            event.preventDefault();
            setCartDrawerState(true);
        });
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && drawer.getAttribute('aria-hidden') === 'false') {
            setCartDrawerState(false);
        }
    });

    document.addEventListener('click', (event) => {
        const button = event.target.closest('button[aria-label^="Add "][aria-label$=" to cart"], button[data-buy-now]');

        if (button) {
            event.preventDefault();
            event.stopPropagation();
            addToCart(button, button.hasAttribute('data-buy-now'));
        }
    });

    cartUi.querySelector('[data-cart-checkout]').addEventListener('click', () => {
        if (!cart.length) {
            alert('Your cart is empty. Please add products before checking out.');
            return;
        }
        setCartDrawerState(false);
        window.location.href = '/checkout';
    });

    const syncPriceRangeInputs = () => {
        const sliderMin = document.querySelector('[data-price-range="min"]');
        const sliderMax = document.querySelector('[data-price-range="max"]');
        const minInput = document.getElementById('min-price-input');
        const maxInput = document.getElementById('max-price-input');
        const filterForm = document.getElementById('shop-filter-form');

        if (!sliderMin || !sliderMax || !minInput || !maxInput || !filterForm) {
            return;
        }

        const clampPrice = (value, min, max) => Math.min(Math.max(value, min), max);

        const submitFilterForm = () => {
            if (filterForm) {
                filterForm.requestSubmit();
            }
        };

        const updateValues = () => {
            const minValue = Number(sliderMin.value || 0);
            const maxValue = Number(sliderMax.value || 0);
            const minLimit = Number(sliderMin.min || 0);
            const maxLimit = Number(sliderMax.max || 0);

            if (minValue > maxValue) {
                sliderMin.value = maxValue;
                minInput.value = maxValue;
            }

            if (maxValue < minValue) {
                sliderMax.value = minValue;
                maxInput.value = minValue;
            }

            const safeMin = clampPrice(Number(sliderMin.value || 0), minLimit, maxLimit);
            const safeMax = clampPrice(Number(sliderMax.value || 0), minLimit, maxLimit);

            sliderMin.value = safeMin;
            sliderMax.value = safeMax;
            minInput.value = safeMin;
            maxInput.value = safeMax;
        };

        sliderMin.addEventListener('input', updateValues);
        sliderMax.addEventListener('input', updateValues);
        sliderMin.addEventListener('change', submitFilterForm);
        sliderMax.addEventListener('change', submitFilterForm);

        minInput.addEventListener('input', () => {
            const next = clampPrice(Number(minInput.value || 0), Number(sliderMin.min || 0), Number(sliderMax.value || 0));
            sliderMin.value = next;
            minInput.value = next;
        });
        maxInput.addEventListener('input', () => {
            const next = clampPrice(Number(maxInput.value || 0), Number(sliderMin.value || 0), Number(sliderMax.max || 0));
            sliderMax.value = next;
            maxInput.value = next;
        });

        minInput.addEventListener('change', submitFilterForm);
        maxInput.addEventListener('change', submitFilterForm);

        filterForm.querySelectorAll('input[name="variants[]"]').forEach((checkbox) => {
            checkbox.addEventListener('change', submitFilterForm);
        });

        updateValues();
    };

    const heroCarousel = document.querySelector('[data-hero-carousel]');
    const heroTrack = heroCarousel?.querySelector('[data-hero-track]');
    const heroSlides = heroTrack ? Array.from(heroTrack.querySelectorAll('[data-hero-slide]')) : [];
    const heroDots = heroCarousel ? Array.from(heroCarousel.querySelectorAll('[data-hero-dot]')) : [];
    const heroPrev = heroCarousel?.querySelector('[data-hero-prev]');
    const heroNext = heroCarousel?.querySelector('[data-hero-next]');

    if (heroCarousel && heroTrack && heroSlides.length > 1) {
        const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        let activeHeroIndex = 0;
        let autoplayTimer = null;
        let heroHovered = false;
        let heroFocused = false;
        let touchStartX = null;

        const stopAutoplay = () => {
            if (autoplayTimer) {
                window.clearInterval(autoplayTimer);
                autoplayTimer = null;
            }
        };

        const showHeroSlide = (index) => {
            activeHeroIndex = (index + heroSlides.length) % heroSlides.length;
            heroTrack.style.transform = `translateX(-${activeHeroIndex * 100}%)`;

            heroSlides.forEach((slide, slideIndex) => {
                const isActive = slideIndex === activeHeroIndex;
                slide.setAttribute('aria-hidden', String(!isActive));
                slide.inert = !isActive;
            });

            heroDots.forEach((dot, dotIndex) => {
                const isActive = dotIndex === activeHeroIndex;
                dot.classList.toggle('w-8', isActive);
                dot.classList.toggle('w-2.5', !isActive);
                dot.classList.toggle('bg-[#145943]', isActive);
                dot.classList.toggle('bg-[#c6c8b9]', !isActive);
                dot.setAttribute('aria-current', String(isActive));
            });
        };

        const startAutoplay = () => {
            stopAutoplay();
            if (reduceMotion || heroHovered || heroFocused) return;

            autoplayTimer = window.setInterval(() => {
                showHeroSlide(activeHeroIndex + 1);
            }, 5000);
        };

        heroPrev?.addEventListener('click', () => {
            showHeroSlide(activeHeroIndex - 1);
            startAutoplay();
        });

        heroNext?.addEventListener('click', () => {
            showHeroSlide(activeHeroIndex + 1);
            startAutoplay();
        });

        heroDots.forEach((dot, index) => {
            dot.addEventListener('click', () => {
                showHeroSlide(index);
                startAutoplay();
            });
        });

        heroCarousel.addEventListener('mouseenter', () => {
            heroHovered = true;
            stopAutoplay();
        });

        heroCarousel.addEventListener('mouseleave', () => {
            heroHovered = false;
            startAutoplay();
        });

        heroCarousel.addEventListener('focusin', () => {
            heroFocused = true;
            stopAutoplay();
        });

        heroCarousel.addEventListener('focusout', (event) => {
            if (!heroCarousel.contains(event.relatedTarget)) {
                heroFocused = false;
                startAutoplay();
            }
        });

        heroCarousel.addEventListener('touchstart', (event) => {
            touchStartX = event.changedTouches[0]?.clientX ?? null;
        }, { passive: true });

        heroCarousel.addEventListener('touchend', (event) => {
            const touchEndX = event.changedTouches[0]?.clientX;
            if (touchStartX === null || touchEndX === undefined) return;

            const distance = touchEndX - touchStartX;
            if (Math.abs(distance) > 45) {
                showHeroSlide(activeHeroIndex + (distance < 0 ? 1 : -1));
                startAutoplay();
            }
            touchStartX = null;
        }, { passive: true });

        startAutoplay();
    }

    syncPriceRangeInputs();

    const shopFilterToggle = document.querySelector('[data-shop-filter-toggle]');
    const shopFilterSidebar = document.getElementById('shop-filter-sidebar');
    const shopFilterOverlay = document.querySelector('[data-shop-filter-overlay]');
    const shopFilterClose = document.querySelector('[data-shop-filter-close]');

    if (shopFilterToggle && shopFilterSidebar && shopFilterOverlay) {
        let shopFilterDrawerOpen = false;
        let shopFilterOverlayTimer = null;

        const setShopFilterDrawerState = (open) => {
            const isMobile = !window.matchMedia('(min-width: 1024px)').matches;
            const wasOpen = shopFilterDrawerOpen;
            window.clearTimeout(shopFilterOverlayTimer);
            shopFilterDrawerOpen = isMobile && open;
            shopFilterSidebar.classList.toggle('-translate-x-full', isMobile && !open);
            shopFilterSidebar.toggleAttribute('inert', isMobile && !open);
            shopFilterOverlay.classList.toggle('hidden', !shopFilterDrawerOpen);
            shopFilterSidebar.setAttribute('aria-hidden', String(isMobile && !open));
            shopFilterToggle.setAttribute('aria-expanded', String(shopFilterDrawerOpen));

            if (shopFilterDrawerOpen) {
                shopFilterOverlay.setAttribute('aria-hidden', 'false');
                document.body.classList.add('overflow-hidden');
                requestAnimationFrame(() => shopFilterOverlay.classList.remove('opacity-0'));
                shopFilterClose?.focus();
                return;
            }

            shopFilterOverlay.classList.add('opacity-0');
            shopFilterOverlay.setAttribute('aria-hidden', 'true');
            if (!document.querySelector('[data-cart-drawer][aria-hidden="false"]')) {
                document.body.classList.remove('overflow-hidden');
            }
            shopFilterOverlayTimer = window.setTimeout(() => shopFilterOverlay.classList.add('hidden'), 220);

            if (isMobile && wasOpen) {
                shopFilterToggle.focus();
            }
        };

        setShopFilterDrawerState(false);
        shopFilterToggle.addEventListener('click', () => setShopFilterDrawerState(!shopFilterDrawerOpen));
        shopFilterClose?.addEventListener('click', () => setShopFilterDrawerState(false));
        shopFilterOverlay.addEventListener('click', () => setShopFilterDrawerState(false));
        window.addEventListener('resize', () => {
            if (window.matchMedia('(min-width: 1024px)').matches) {
                setShopFilterDrawerState(false);
            }
        });
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && shopFilterDrawerOpen) {
                setShopFilterDrawerState(false);
            }
        });
    }

    const toggle = document.querySelector('[data-mobile-menu-toggle]');
    const menu = document.querySelector('[data-mobile-menu]');
    const openIcon = document.querySelector('[data-menu-open-icon]');
    const closeIcon = document.querySelector('[data-menu-close-icon]');

    if (toggle && menu) {
        const setMenuState = (open) => {
            menu.classList.toggle('hidden', !open);
            openIcon?.classList.toggle('hidden', open);
            closeIcon?.classList.toggle('hidden', !open);
            toggle.setAttribute('aria-expanded', String(open));
            toggle.setAttribute('aria-label', open ? 'Close menu' : 'Open menu');
        };

        toggle.addEventListener('click', () => {
            setMenuState(toggle.getAttribute('aria-expanded') !== 'true');
        });

        menu.querySelectorAll('a').forEach((link) => {
            link.addEventListener('click', () => setMenuState(false));
        });

        window.addEventListener('resize', () => {
            if (window.innerWidth >= 768) {
                setMenuState(false);
            }
        });
    }

    const setupCarousel = ({
        trackSelector,
        prevSelector = null,
        nextSelector,
        paginationSelector = null,
        minCardWidth,
        maxVisible,
        mobileMinCardWidth = null,
        mobileMaxVisible = null,
        centerWhenFits = false,
        initialDirection = 1,
        reversePauseMs = 2300,
    }) => {
        const track = document.querySelector(trackSelector);
        if (!track) return;

        const items = Array.from(track.querySelectorAll('[data-carousel-item]'));
        if (!items.length) return;

        const prev = prevSelector ? document.querySelector(prevSelector) : null;
        const next = nextSelector ? document.querySelector(nextSelector) : null;
        const pagination = paginationSelector ? document.querySelector(paginationSelector) : null;
        const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        let direction = initialDirection;
        let timer = null;
        let reverseTimer = null;
        let hovering = false;
        let touchPauseTimer = null;
        let dots = [];

        const gapSize = () => parseFloat(getComputedStyle(track).columnGap || getComputedStyle(track).gap) || 0;
        const maxScroll = () => Math.max(track.scrollWidth - track.clientWidth, 0);
        const stepSize = () => {
            const itemWidth = items[0]?.getBoundingClientRect().width || 0;
            return itemWidth + gapSize();
        };

        const updatePagination = () => {
            if (!pagination || !dots.length) return;

            const step = stepSize() || 1;
            const activeIndex = Math.max(0, Math.min(dots.length - 1, Math.round(track.scrollLeft / step)));

            dots.forEach((dot, index) => {
                const active = index === activeIndex;
                dot.classList.toggle('w-5', active);
                dot.classList.toggle('w-2', !active);
                dot.classList.toggle('bg-[#145943]', active);
                dot.classList.toggle('bg-[#c6c8b9]', !active);
                dot.classList.toggle('ring-2', active);
                dot.classList.toggle('ring-[#dce6ce]', active);
                dot.setAttribute('aria-current', active ? 'true' : 'false');
            });
        };

        const updateButtons = () => {
            const canMove = maxScroll() > 2;
            if (prev) prev.disabled = !canMove;
            if (next) next.disabled = !canMove;
            updatePagination();
        };

        const renderPagination = () => {
            if (!pagination) return;

            pagination.replaceChildren();
            const step = stepSize() || 1;
            const pageCount = Math.max(1, Math.ceil(maxScroll() / step) + 1);

            dots = Array.from({ length: pageCount }, (_, index) => {
                const dot = document.createElement('button');
                dot.type = 'button';
                dot.className = 'h-2.5 w-2.5 rounded-full bg-[#c6c8b9] transition-all duration-300 hover:bg-[#a48335] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#a48335] focus-visible:ring-offset-2';
                dot.setAttribute('aria-label', `Show featured products starting at card ${index + 1}`);
                dot.addEventListener('click', () => {
                    clearReverse();
                    const target = index === pageCount - 1 ? maxScroll() : Math.min(maxScroll(), index * stepSize());
                    track.scrollTo({ left: target, behavior: reduceMotion ? 'auto' : 'smooth' });
                    direction = target >= track.scrollLeft ? 1 : -1;
                });
                pagination.appendChild(dot);
                return dot;
            });

            updatePagination();
        };

        const sizeCards = () => {
            const width = track.clientWidth;
            if (!width) return;

            const isMobile = window.matchMedia('(max-width: 639px)').matches;
            const cardMinWidth = isMobile && mobileMinCardWidth ? mobileMinCardWidth : minCardWidth;
            const cardMaxVisible = isMobile && mobileMaxVisible ? mobileMaxVisible : maxVisible;
            const gap = gapSize();
            const visible = Math.max(1, Math.min(cardMaxVisible, Math.floor((width + gap) / (cardMinWidth + gap))));
            const cardWidth = Math.max(1, (width - gap * (visible - 1)) / visible);

            items.forEach((item) => {
                item.style.flex = `0 0 ${cardWidth}px`;
                item.style.width = `${cardWidth}px`;
            });

            requestAnimationFrame(() => {
                const fits = track.scrollWidth <= track.clientWidth + 2;
                track.classList.toggle('justify-center', centerWhenFits && fits);
                track.scrollLeft = Math.min(track.scrollLeft, maxScroll());
                renderPagination();
                updateButtons();
            });
        };

        const clearReverse = () => {
            if (reverseTimer) {
                window.clearTimeout(reverseTimer);
                reverseTimer = null;
            }
        };

        const moveOneCard = (moveDirection) => {
            const max = maxScroll();
            if (max <= 2) return;

            const current = track.scrollLeft;
            const step = stepSize();
            const atStart = current <= 2;
            const atEnd = current >= max - 2;

            if ((moveDirection > 0 && atEnd) || (moveDirection < 0 && atStart)) {
                direction = -moveDirection;
                return;
            }

            const target = Math.max(0, Math.min(max, current + moveDirection * step));
            track.scrollTo({ left: target, behavior: reduceMotion ? 'auto' : 'smooth' });
        };

        const scheduleReverse = () => {
            clearReverse();
            reverseTimer = window.setTimeout(() => {
                direction *= -1;
                reverseTimer = null;
            }, reversePauseMs);
        };

        const start = () => {
            if (timer) window.clearInterval(timer);

            timer = window.setInterval(() => {
                if (hovering || maxScroll() <= 2) return;

                const max = maxScroll();
                const atEdge = direction > 0
                    ? track.scrollLeft >= max - 3
                    : track.scrollLeft <= 2;

                if (atEdge) {
                    if (!reverseTimer) {
                        scheduleReverse();
                    }
                    return;
                }

                clearReverse();
                moveOneCard(direction);
            }, 3000);
        };

        const manualMove = (moveDirection) => {
            clearReverse();
            direction = moveDirection;
            moveOneCard(moveDirection);
        };

        prev?.addEventListener('click', () => manualMove(-1));
        next?.addEventListener('click', () => manualMove(1));

        track.addEventListener('mouseenter', () => {
            hovering = true;
        });

        track.addEventListener('mouseleave', () => {
            hovering = false;
        });

        track.addEventListener('touchstart', () => {
            hovering = true;
            window.clearTimeout(touchPauseTimer);
        }, { passive: true });

        track.addEventListener('touchend', () => {
            touchPauseTimer = window.setTimeout(() => {
                hovering = false;
            }, 1400);
        }, { passive: true });

        track.addEventListener('scroll', updateButtons, { passive: true });
        window.addEventListener('resize', sizeCards);

        sizeCards();
        start();
    };

    setupCarousel({
        trackSelector: '[data-category-track]',
        prevSelector: '[data-category-prev]',
        nextSelector: '[data-category-next]',
        minCardWidth: 96,
        maxVisible: 8,
        mobileMinCardWidth: 64,
        mobileMaxVisible: 3,
        centerWhenFits: true,
    });

    setupCarousel({
        trackSelector: '[data-product-track]',
        paginationSelector: '[data-product-pagination]',
        minCardWidth: 220,
        maxVisible: 5,
        mobileMinCardWidth: 120,
        mobileMaxVisible: 2,
        initialDirection: 1,
        reversePauseMs: 2300,
    });

    document.querySelectorAll('[data-category-product-track]').forEach((track) => {
        const slug = track.getAttribute('data-category-product-track');

        setupCarousel({
            trackSelector: `[data-category-product-track="${slug}"]`,
            paginationSelector: `[data-category-product-pagination="${slug}"]`,
            minCardWidth: 220,
            maxVisible: 5,
            mobileMinCardWidth: 120,
            mobileMaxVisible: 2,
            initialDirection: 1,
            reversePauseMs: 2300,
        });
    });

    renderCart();
    loadCart();
});
