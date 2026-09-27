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
        emoji: item.emoji || '🛍️',
    });

    const cartUi = document.createElement('div');
    cartUi.innerHTML = `
        <button type="button" data-floating-cart aria-label="Open shopping cart" aria-expanded="false"
            class="fixed right-0 top-[58%] z-[80] flex w-[68px] -translate-y-1/2 flex-col items-center overflow-hidden rounded-l-xl border border-[#e7e4d8] bg-white text-center shadow-lg transition-transform hover:-translate-x-1 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#145943] sm:w-[76px]">
            <span class="flex w-full flex-col items-center bg-[#f58216] px-2 py-2.5 text-white">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                    <path d="M5 8h14l1 13H4L5 8Z"/><path d="M9 8V6a3 3 0 0 1 6 0v2"/>
                </svg>
                <span data-cart-count class="mt-1 text-[11px] font-semibold">0 Items</span>
            </span>
            <span data-cart-total class="w-full px-1 py-1.5 text-[11px] font-bold text-[#a48335]">৳0.00</span>
        </button>

        <div data-cart-overlay class="fixed inset-0 z-[89] hidden bg-[#102a22]/45 opacity-0 transition-opacity duration-200" aria-hidden="true"></div>

        <aside data-cart-drawer aria-label="Shopping cart" aria-modal="true" role="dialog"
            class="fixed inset-y-0 right-0 z-[90] flex w-full max-w-[390px] translate-x-full flex-col bg-[#fffef9] shadow-2xl transition-transform duration-300 ease-out" aria-hidden="true">
            <div class="flex items-center justify-between border-b border-[#e7e4d8] bg-white px-5 py-4">
                <div>
                    <p class="text-xs font-bold uppercase tracking-wider text-[#a48335]">Your basket</p>
                    <h2 class="mt-0.5 text-xl font-extrabold text-[#145943]">Shopping Cart <span data-drawer-count class="text-sm font-semibold text-[#738078]">(0)</span></h2>
                </div>
                <button type="button" data-close-cart aria-label="Close cart" class="grid h-9 w-9 place-items-center rounded-full text-xl text-[#145943] transition hover:bg-[#eef4e8]">&times;</button>
            </div>

            <div data-cart-items class="flex-1 space-y-3 overflow-y-auto p-4"></div>

            <div class="border-t border-[#e7e4d8] bg-white p-5">
                <div class="mb-3 flex items-center justify-between text-sm">
                    <span class="text-[#56665f]">Subtotal</span>
                    <strong data-cart-subtotal class="text-lg text-[#145943]">৳0.00</strong>
                </div>
                <p class="mb-3 text-xs text-[#738078]">Delivery and any applicable charges are calculated at checkout.</p>
                <button type="button" data-cart-checkout class="w-full rounded-lg bg-[#145943] px-4 py-3 text-sm font-bold text-white transition hover:bg-[#a48335]">Proceed to Checkout</button>
                <button type="button" data-close-cart class="mt-2 w-full rounded-lg border border-[#d9dfcd] px-4 py-2.5 text-sm font-semibold text-[#145943] transition hover:bg-[#f0f2e7]">Continue Shopping</button>
            </div>
        </aside>
    `;

    document.body.appendChild(cartUi);

    const floatingCart = cartUi.querySelector('[data-floating-cart]');
    const overlay = cartUi.querySelector('[data-cart-overlay]');
    const drawer = cartUi.querySelector('[data-cart-drawer]');
    const itemsContainer = cartUi.querySelector('[data-cart-items]');

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
        cartUi.querySelector('[data-drawer-count]').textContent = `(${quantity})`;
        cartUi.querySelector('[data-cart-subtotal]').textContent = money(total);

        document.querySelectorAll('[data-cart-count-badge]').forEach((badge) => {
            badge.textContent = String(quantity);
        });

        itemsContainer.replaceChildren();

        if (!cart.length) {
            const empty = document.createElement('div');
            empty.className = 'flex h-full min-h-48 flex-col items-center justify-center text-center';
            empty.innerHTML = `
                <span class="text-5xl" aria-hidden="true">🛒</span>
                <p class="mt-3 font-bold text-[#145943]">Your cart is empty</p>
                <p class="mt-1 text-sm text-[#738078]">Add some fresh products to get started.</p>
            `;
            itemsContainer.appendChild(empty);
            return;
        }

        cart.forEach((item) => {
            const row = document.createElement('div');
            row.className = 'flex gap-3 rounded-xl border border-[#e7e4d8] bg-white p-3';

            const emoji = document.createElement('div');
            emoji.className = 'grid h-14 w-14 shrink-0 place-items-center rounded-lg bg-[#f4f5e9] text-3xl';
            emoji.textContent = item.emoji || '🛍️';

            const detail = document.createElement('div');
            detail.className = 'min-w-0 flex-1';

            const name = document.createElement('p');
            name.className = 'line-clamp-2 text-sm font-bold text-[#173b32]';
            name.textContent = item.name;

            const price = document.createElement('p');
            price.className = 'mt-1 text-sm font-bold text-[#a48335]';
            price.textContent = money(item.price);

            const controls = document.createElement('div');
            controls.className = 'mt-2 flex items-center justify-between gap-2';

            const qty = document.createElement('div');
            qty.className = 'flex items-center overflow-hidden rounded-md border border-[#dfe3cf]';

            ['−', String(item.qty ?? item.quantity ?? 0), '+'].forEach((label, index) => {
                const delta = index === 0 ? -1 : index === 2 ? 1 : 0;
                const control = document.createElement('button');
                control.type = 'button';
                control.textContent = label;
                control.className = 'min-w-8 px-2 py-1 text-sm font-semibold text-[#145943] transition hover:bg-[#eef4e8]';

                if (delta === 0) {
                    control.disabled = true;
                    control.className += ' cursor-default';
                } else {
                    control.setAttribute('aria-label', delta > 0 ? 'Increase quantity' : 'Decrease quantity');
                    control.addEventListener('click', async () => {
                        const currentQty = Number(item.qty ?? item.quantity ?? 0);
                        const nextQty = currentQty + delta;

                        if (!item.id) return;

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

                        const response = await fetch('/api/cart', {
                            headers: apiHeaders,
                            credentials: 'same-origin',
                        });
                        const data = await response.json();

                        cart = (data.items || []).map(normalizeCartItem);
                        renderCart();
                    });
                }

                qty.appendChild(control);
            });

            const remove = document.createElement('button');
            remove.type = 'button';
            remove.textContent = 'Remove';
            remove.className = 'text-xs font-semibold text-[#a64c3c] underline-offset-2 hover:underline';
            remove.addEventListener('click', async () => {
                if (!item.id) return;

                await fetch(`/api/cart/items/${item.id}`, {
                    method: 'DELETE',
                    headers: { ...apiHeaders },
                    credentials: 'same-origin',
                });

                const response = await fetch('/api/cart', {
                    headers: apiHeaders,
                    credentials: 'same-origin',
                });
                const data = await response.json();

                cart = (data.items || []).map(normalizeCartItem);
                renderCart();
            });

            controls.append(qty, remove);
            detail.append(name, price, controls);
            row.append(emoji, detail);
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

    const addToCart = async (button) => {
        const productId = Number(button.dataset.productId || button.closest('[data-product-card]')?.dataset.productId || 0);
        const name = (button.dataset.productName || button.closest('[data-product-card]')?.dataset.productName || '').trim();
        const price = Number(button.dataset.productPrice || button.closest('[data-product-card]')?.dataset.productPrice || 0);

        if (!productId || !name || !Number.isFinite(price) || price <= 0) {
            return;
        }

        const originalLabel = button.textContent;
        button.disabled = true;
        button.textContent = 'Adding...';

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
            button.textContent = 'Added ✓';
        } catch (error) {
            button.textContent = 'Error';
        } finally {
            window.setTimeout(() => {
                button.textContent = originalLabel;
                button.disabled = false;
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
        const button = event.target.closest('button[aria-label^="Add "][aria-label$=" to cart"]');

        if (button) {
            event.preventDefault();
            event.stopPropagation();
            addToCart(button);
        }
    });

    cartUi.querySelector('[data-cart-checkout]').addEventListener('click', () => {
        alert('This demo cart is ready for checkout integration. No order has been placed.');
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

    syncPriceRangeInputs();

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
        prevSelector,
        nextSelector,
        paginationSelector = null,
        minCardWidth,
        maxVisible,
        initialDirection = 1,
        reversePauseMs = 2300,
    }) => {
        const track = document.querySelector(trackSelector);
        if (!track) return;

        const items = Array.from(track.querySelectorAll('[data-carousel-item]'));
        if (!items.length) return;

        const prev = document.querySelector(prevSelector);
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

            const gap = gapSize();
            const visible = Math.max(1, Math.min(maxVisible, Math.floor((width + gap) / (minCardWidth + gap))));
            const cardWidth = Math.max(1, (width - gap * (visible - 1)) / visible);

            items.forEach((item) => {
                item.style.flex = `0 0 ${cardWidth}px`;
                item.style.width = `${cardWidth}px`;
            });

            requestAnimationFrame(() => {
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
    });

    setupCarousel({
        trackSelector: '[data-product-track]',
        paginationSelector: '[data-product-pagination]',
        minCardWidth: 150,
        maxVisible: 5,
        initialDirection: 1,
        reversePauseMs: 2300,
    });

    renderCart();
    loadCart();
});
