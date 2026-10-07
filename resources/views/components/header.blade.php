@if (request()->routeIs('admin*') || (auth()->check() && auth()->user()->can('access-admin') && request()->routeIs('account.orders*', 'account.security*')))
    <header class="relative z-50 border-b border-[#e7e4d8] bg-[#fffef9]/95 backdrop-blur">
        <div class="mx-auto flex h-[78px] max-w-[1600px] items-center gap-4 px-4 sm:px-6 lg:px-8">
            <a href="{{ route('home') }}" @if (request()->routeIs('home')) aria-current="page" @endif class="relative flex shrink-0 items-center after:absolute after:-bottom-2 after:left-1/2 after:h-0.5 after:w-5 after:-translate-x-1/2 after:rounded-full after:bg-[#145943] {{ request()->routeIs('home') ? '' : 'after:hidden' }}" aria-label="{{ config('app.name') }} home">
                <img
                    src="{{ ! empty($siteBranding['logo_path']) ? \Illuminate\Support\Facades\Storage::disk('public')->url($siteBranding['logo_path']) : asset('images/brand/ziyanmart-logo.png') }}"
                    alt="{{ config('app.name') }}"
                    class="h-12 w-auto object-contain sm:h-14"
                >
            </a>

            <nav class="ml-auto flex items-center gap-2 sm:gap-3" aria-label="Admin account">
                @canany(['orders.view', 'reviews.view'])
                    <a href="{{ route('admin.notifications') }}" data-admin-notifications data-count-url="{{ route('admin.notifications.unread') }}" class="relative grid h-10 w-10 place-items-center rounded-full text-[#145943] transition hover:bg-[#eef4e8]" aria-label="Admin notifications">
                    <svg data-admin-notification-bell class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                        <path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9"></path>
                        <path d="M10 21h4"></path>
                    </svg>
                    <span data-admin-notification-count class="absolute -right-0.5 -top-0.5 hidden h-5 min-w-5 place-items-center rounded-full bg-[#a48335] px-1 text-[10px] font-bold text-white" aria-live="polite"></span>
                    </a>
                @endcan

                <details class="group relative">
                    <summary title="Account options" aria-label="Open profile and logout menu" class="grid h-10 w-10 cursor-pointer list-none place-items-center overflow-hidden rounded-full border border-[#dce6ce] bg-[#edf2e4] text-sm font-bold text-[#145943] transition hover:bg-[#dfead4] [&::-webkit-details-marker]:hidden">
                        @if (auth()->user()?->profile_photo_path)
                            <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url(auth()->user()->profile_photo_path) }}" alt="" class="h-full w-full object-cover">
                        @else
                            {{ strtoupper(substr(auth()->user()?->name ?? 'SO', 0, 1)) }}
                        @endif
                    </summary>
                    <div class="absolute right-0 top-full z-[70] mt-1 w-48 overflow-hidden rounded-[4px] border border-[#e7e7e1] bg-white py-1 shadow-lg">
                        <a href="{{ route('admin.profile') }}" class="flex items-center gap-3 px-4 py-2.5 text-sm text-[#454c48] hover:bg-[#edf2e4] hover:text-[#145943]">
                            <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><circle cx="12" cy="8" r="3.3" /><path d="M5.5 20a6.5 6.5 0 0 1 13 0" /></svg>
                            <span>Profile</span>
                        </a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="flex w-full items-center gap-3 px-4 py-2.5 text-left text-sm text-[#454c48] hover:bg-[#edf2e4] hover:text-[#145943]">
                                <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M10 17l5-5-5-5M15 12H3" /><path d="M12 3h6a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-6" /></svg>
                                <span>Logout</span>
                            </button>
                        </form>
                    </div>
                </details>
            </nav>
        </div>
    </header>
@else
@php
    $profileRoute = auth()->user()?->can('access-admin') ? route('admin.profile') : route('account.profile');
    $whatsappNumber = $siteBranding['whatsapp_number'] ?? null;
    $whatsappDigits = preg_replace('/\D+/', '', (string) $whatsappNumber) ?? '';

    if (str_starts_with($whatsappDigits, '0')) {
        $whatsappDigits = '880'.substr($whatsappDigits, 1);
    }

    $whatsappUrl = filled($siteBranding['whatsapp_url'] ?? null)
        ? $siteBranding['whatsapp_url']
        : (filled($whatsappDigits) ? 'https://wa.me/'.$whatsappDigits : null);
    $callUrl = filled($whatsappDigits) ? 'tel:+'.$whatsappDigits : null;
@endphp
<header class="sticky top-0 z-50 lg:relative">
    {{-- Announcement bar --}}
    <div class="hidden">
        <div class="mx-auto flex max-w-[1600px] items-center justify-between gap-4 px-4 py-2 text-[11px] font-medium sm:px-6 sm:text-xs lg:px-8">
            <p class="flex items-center gap-2">
                <span class="text-[#d7b45a]">✦</span>
                Fresh &amp; Healthy Products
                <span class="hidden text-white/50 sm:inline">•</span>
                <span class="hidden sm:inline">Free Shipping on Orders Over ৳1000</span>
            </p>

            <div class="hidden items-center gap-4 md:flex">
                <a href="{{ route('account.orders') }}" class="transition hover:text-[#e5cf8b]">Track Order</a>
                <span class="text-white/30">|</span>
                <a href="#" class="transition hover:text-[#e5cf8b]">Help</a>
                <span class="text-white/30">|</span>
                @auth
                    <a href="{{ $profileRoute }}" class="grid h-9 w-9 place-items-center overflow-hidden rounded-full border border-white/30 bg-white/10 text-xs font-bold text-white transition hover:bg-white/20" aria-label="Customer profile">
                        @if (auth()->user()->profile_photo_path)
                            <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url(auth()->user()->profile_photo_path) }}" alt="Profile photo" class="h-full w-full object-cover">
                        @else
                            {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
                        @endif
                    </a>
                    <a href="{{ $profileRoute }}" class="max-w-40 truncate transition hover:text-[#e5cf8b]">{{ auth()->user()->name }}</a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="transition hover:text-[#e5cf8b]">Sign out</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="transition hover:text-[#e5cf8b]">Login</a>
                    <a href="{{ route('register') }}" class="transition hover:text-[#e5cf8b]">Register</a>
                @endauth
            </div>
        </div>
    </div>

    {{-- Main navigation --}}
    <div class="border-b border-[#e7e4d8] bg-white">
        <div class="mx-auto flex h-[78px] max-w-[1600px] items-center gap-4 px-4 sm:px-6 lg:px-8">

            <a href="{{ route('home') }}" class="flex shrink-0 items-center" aria-label="{{ config('app.name') }} home">
                <img
                    src="{{ ! empty($siteBranding['logo_path']) ? \Illuminate\Support\Facades\Storage::disk('public')->url($siteBranding['logo_path']) : asset('images/brand/ziyanmart-logo.png') }}"
                    alt="{{ config('app.name') }}"
                    class="h-12 w-auto object-contain sm:h-14"
                >
            </a>

            {{-- Search --}}
            <div class="hidden min-w-0 flex-1 lg:block lg:max-w-[480px] lg:mx-auto">
                <form action="{{ route('shop') }}" method="GET" class="relative" role="search" data-storefront-search data-suggestions-url="{{ route('shop.suggestions') }}">
                    <label for="header-search" class="sr-only">Search products</label>
                    <input
                        id="header-search"
                        type="search"
                        name="q"
                        value="{{ is_string(request()->query('q')) ? request()->query('q') : '' }}"
                        placeholder="Search in..."
                        autocomplete="off"
                        maxlength="100"
                        aria-autocomplete="list"
                        aria-controls="header-search-suggestions"
                        aria-expanded="false"
                        data-storefront-search-input
                        class="h-11 w-full rounded-[5px] border border-transparent bg-[#f1f1f1] pl-4 pr-12 text-base text-[#173b32] outline-none transition placeholder:text-[#7c8580] focus:border-[#145943] focus:ring-2 focus:ring-[#145943]/10"
                    >
                    <div id="header-search-suggestions" data-storefront-search-results role="listbox" class="absolute left-0 right-0 top-full z-50 mt-2 hidden max-h-80 overflow-y-auto rounded-[4px] border border-[#e3e0d6] bg-white py-1 shadow-lg"></div>
                    <button type="submit" aria-label="Search products" class="absolute right-1 top-1/2 grid h-10 w-10 -translate-y-1/2 place-items-center rounded-[4px] text-[#4d5c56] transition hover:bg-[#edf2e4] hover:text-[#145943]">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <circle cx="11" cy="11" r="6.5"></circle>
                            <path d="m16 16 4.2 4.2"></path>
                        </svg>
                    </button>
                </form>
            </div>

            <nav class="ml-auto flex shrink-0 items-center gap-1 sm:gap-2" aria-label="Store actions">
                <a href="{{ route('shop') }}" title="Shop" aria-label="Shop" @if (request()->routeIs('shop')) aria-current="page" @endif class="flex h-10 w-10 flex-col items-center justify-center gap-0.5 rounded-full transition hover:bg-[#edf2e4] hover:text-[#145943] max-[374px]:hidden sm:h-12 sm:w-auto sm:min-w-[56px] {{ request()->routeIs('shop') ? 'bg-[#edf2e4] font-semibold text-[#145943]' : 'text-[#26352f]' }}">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M3 10h18l-1.5-6h-15L3 10Z" /><path d="M5 10v10h14V10M9 20v-6h6v6" /><path d="M3 10a2.5 2.5 0 0 0 5 0 2.5 2.5 0 0 0 5 0 2.5 2.5 0 0 0 5 0 2.5 2.5 0 0 0 5 0" /></svg>
                    <span class="hidden text-[10px] leading-3 sm:block">Shop</span>
                </a>

                <a href="{{ route('account.orders') }}" title="Track order" aria-label="Track order" @if (request()->routeIs('account.orders*')) aria-current="page" @endif class="flex h-10 w-10 flex-col items-center justify-center gap-0.5 rounded-full transition hover:bg-[#edf2e4] hover:text-[#145943] max-[374px]:hidden sm:h-12 sm:w-auto sm:min-w-[56px] {{ request()->routeIs('account.orders*') ? 'bg-[#edf2e4] font-semibold text-[#145943]' : 'text-[#26352f]' }}">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M12 21s7-5.2 7-11a7 7 0 1 0-14 0c0 5.8 7 11 7 11Z" /><circle cx="12" cy="10" r="2.2" /><path d="M19 20h3" /></svg>
                    <span class="hidden text-[10px] leading-3 sm:block">Track Order</span>
                </a>

                @auth
                    <details class="group relative">
                        <summary title="Profile" aria-label="Open profile and logout menu" @if (request()->routeIs('account.profile*', 'admin.profile')) aria-current="page" @endif class="flex h-10 w-10 cursor-pointer list-none flex-col items-center justify-center gap-0.5 rounded-full transition hover:bg-[#edf2e4] hover:text-[#145943] sm:h-12 sm:w-auto sm:min-w-[56px] [&::-webkit-details-marker]:hidden {{ request()->routeIs('account.profile*', 'admin.profile') ? 'bg-[#edf2e4] font-semibold text-[#145943]' : 'text-[#26352f]' }}">
                            @if (auth()->user()->profile_photo_path)
                                <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url(auth()->user()->profile_photo_path) }}" alt="" class="h-7 w-7 rounded-full border-2 border-white object-cover shadow-sm">
                            @else
                                <span role="img" aria-label="Avatar for {{ auth()->user()->name }}" class="grid h-7 w-7 place-items-center rounded-full border-2 border-white bg-[#145943] text-xs font-bold text-white shadow-sm">{{ strtoupper(substr(trim(auth()->user()->name), 0, 1)) }}</span>
                            @endif
                            <span class="hidden text-[10px] leading-3 sm:block">Profile</span>
                        </summary>
                        <div class="absolute right-0 top-full z-[70] mt-1 w-48 overflow-hidden rounded-[4px] border border-[#e7e7e1] bg-white py-1 shadow-lg">
                            <a href="{{ $profileRoute }}" class="flex items-center gap-3 px-4 py-2.5 text-sm text-[#454c48] hover:bg-[#edf2e4] hover:text-[#145943]">
                                <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><circle cx="12" cy="8" r="3.3" /><path d="M5.5 20a6.5 6.5 0 0 1 13 0" /></svg>
                                <span>Profile</span>
                            </a>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="flex w-full items-center gap-3 px-4 py-2.5 text-left text-sm text-[#454c48] hover:bg-[#edf2e4] hover:text-[#145943]">
                                    <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M10 17l5-5-5-5M15 12H3" /><path d="M12 3h6a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-6" /></svg>
                                    <span>Logout</span>
                                </button>
                            </form>
                        </div>
                    </details>
                @else
                    <a href="{{ route('login') }}" title="Sign in" aria-label="Sign in" @if (request()->routeIs('login', 'register')) aria-current="page" @endif class="flex h-10 w-10 flex-col items-center justify-center gap-0.5 rounded-full transition hover:bg-[#edf2e4] hover:text-[#145943] sm:h-12 sm:w-auto sm:min-w-[56px] {{ request()->routeIs('login', 'register') ? 'bg-[#edf2e4] font-semibold text-[#145943]' : 'text-[#26352f]' }}">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><circle cx="12" cy="8" r="3.3" /><path d="M5.5 20a6.5 6.5 0 0 1 13 0" /></svg>
                        <span class="hidden text-[10px] leading-3 sm:block">Sign In</span>
                    </a>
                @endauth

                <a href="#" data-open-cart title="Cart" aria-label="Shopping cart" class="relative flex h-10 w-10 flex-col items-center justify-center gap-0.5 rounded-full text-[#26352f] transition hover:bg-[#edf2e4] hover:text-[#145943] sm:h-12 sm:w-auto sm:min-w-[56px]">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M3 4h2l2.1 10.1a2 2 0 0 0 2 1.6h7.7a2 2 0 0 0 1.9-1.4L20 8H6" /><circle cx="9" cy="19" r="1.2" /><circle cx="18" cy="19" r="1.2" /></svg>
                    <span data-cart-count-badge class="absolute -right-0.5 -top-0.5 grid h-5 min-w-5 place-items-center rounded-full bg-[#145943] px-1 text-[10px] font-bold text-white">0</span>
                    <span class="hidden text-[10px] leading-3 sm:block">Cart</span>
                </a>

                <details class="group relative">
                    <summary title="More" aria-label="More links" class="flex h-10 w-10 cursor-pointer list-none flex-col items-center justify-center gap-0.5 rounded-full transition hover:bg-[#edf2e4] hover:text-[#145943] sm:h-12 sm:w-auto sm:min-w-[56px] [&::-webkit-details-marker]:hidden {{ request()->routeIs('about') ? 'bg-[#edf2e4] font-semibold text-[#145943]' : 'text-[#26352f]' }}">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h16" /></svg>
                        <span class="hidden text-[10px] leading-3 sm:block">More</span>
                    </summary>
                    <div class="absolute right-0 top-full z-[70] mt-1 w-56 overflow-hidden rounded-xl border border-[#e7e7e1] bg-white py-1 shadow-lg">
                        <a href="{{ route('shop') }}" @if (request()->routeIs('shop')) aria-current="page" @endif class="hidden max-[374px]:flex items-center gap-3 px-4 py-2.5 text-sm text-[#454c48] hover:bg-[#edf2e4] hover:text-[#145943]">
                            <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M3 10h18l-1.5-6h-15L3 10Z" /><path d="M5 10v10h14V10M9 20v-6h6v6" /></svg>
                            <span>Shop</span>
                        </a>
                        <a href="{{ route('account.orders') }}" @if (request()->routeIs('account.orders*')) aria-current="page" @endif class="hidden max-[374px]:flex items-center gap-3 px-4 py-2.5 text-sm text-[#454c48] hover:bg-[#edf2e4] hover:text-[#145943]">
                            <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M12 21s7-5.2 7-11a7 7 0 1 0-14 0c0 5.8 7 11 7 11Z" /><circle cx="12" cy="10" r="2.2" /><path d="M19 20h3" /></svg>
                            <span>Track Order</span>
                        </a>
                        <a href="{{ route('about') }}" @if (request()->routeIs('about')) aria-current="page" @endif class="flex items-center gap-3 px-4 py-2.5 text-sm hover:bg-[#edf2e4] hover:text-[#145943] {{ request()->routeIs('about') ? 'bg-[#edf2e4] font-semibold text-[#145943]' : 'text-[#454c48]' }}">
                            <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><circle cx="12" cy="12" r="9" /><path d="M12 11v5M12 8h.01" /></svg>
                            <span>About Us</span>
                        </a>
                        @if (filled($callUrl))
                            <a href="{{ $callUrl }}" aria-label="Call Us" class="flex items-center gap-3 px-4 py-2.5 text-sm text-[#454c48] hover:bg-[#edf2e4] hover:text-[#145943]">
                                <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.8 19.8 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.12 4.2 2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.12.96.35 1.9.69 2.8a2 2 0 0 1-.45 2.11L8.08 9.92a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.9.34 1.84.57 2.8.69A2 2 0 0 1 22 16.92Z" /></svg>
                                <span>Call Us · {{ $whatsappNumber }}</span>
                            </a>
                        @endif
                        @if (filled($whatsappUrl))
                            <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener noreferrer" aria-label="WhatsApp" class="flex items-center gap-3 px-4 py-2.5 text-sm text-[#454c48] hover:bg-[#edf2e4] hover:text-[#145943]">
                                <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="11" fill="#25D366" /><path fill="#fff" d="M17.1 14.2c-.28-.14-1.65-.81-1.9-.9-.26-.1-.44-.14-.63.14-.19.28-.72.9-.88 1.08-.16.19-.32.21-.6.07-.28-.14-1.18-.44-2.25-1.39-.83-.74-1.39-1.65-1.55-1.93-.16-.28-.02-.43.12-.57.13-.12.28-.32.42-.48.14-.16.19-.28.28-.47.1-.19.05-.35-.02-.49-.07-.14-.63-1.52-.86-2.08-.22-.54-.45-.47-.63-.48h-.54c-.19 0-.49.07-.75.35-.26.28-.98.95-.98 2.31s1 2.68 1.14 2.87c.14.19 1.97 3.01 4.78 4.22.67.29 1.2.46 1.61.59.68.22 1.3.19 1.79.12.55-.08 1.65-.67 1.89-1.32.23-.65.23-1.2.16-1.32-.07-.12-.26-.19-.54-.33ZM12 2.5a9.47 9.47 0 0 0-8.12 14.34L2.5 21.5l4.78-1.25A9.5 9.5 0 1 0 12 2.5Zm0 17.2a7.67 7.67 0 0 1-3.91-1.07l-.28-.17-2.84.74.76-2.77-.18-.29A7.7 7.7 0 1 1 12 19.7Z" /></svg>
                                <span>WhatsApp</span>
                            </a>
                        @endif
                    </div>
                </details>

                <button type="button" data-mobile-menu-toggle title="Search" class="relative z-[80] grid h-10 w-10 place-items-center rounded-full text-[#26352f] transition hover:bg-[#f3f3f0] hover:text-[#dc762b] lg:hidden" aria-expanded="false" aria-controls="mobile-navigation" aria-label="Open search">
                    <svg data-menu-open-icon class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="11" cy="11" r="6.5" /><path d="m16 16 4.2 4.2" /></svg>
                    <svg data-menu-close-icon class="hidden h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18" /></svg>
                </button>
            </nav>
        </div>

        {{-- Mobile navigation --}}
        <div id="mobile-navigation" data-mobile-menu class="hidden border-t border-[#e7e4d8] bg-[#fffef9] xl:hidden">
            <div class="mx-auto max-w-[1600px] px-4 py-4 sm:px-6">
                <form action="{{ route('shop') }}" method="GET" class="relative mb-4" role="search" data-storefront-search data-suggestions-url="{{ route('shop.suggestions') }}">
                    <label for="mobile-search" class="sr-only">Search products</label>
                    <input
                        id="mobile-search"
                        type="search"
                        name="q"
                        value="{{ is_string(request()->query('q')) ? request()->query('q') : '' }}"
                        placeholder="Search in..."
                        autocomplete="off"
                        maxlength="100"
                        aria-autocomplete="list"
                        aria-controls="mobile-search-suggestions"
                        aria-expanded="false"
                        data-storefront-search-input
                        class="h-11 w-full rounded-[5px] border border-transparent bg-[#f1f1f1] pl-4 pr-12 text-base text-[#173b32] outline-none transition placeholder:text-[#7c8580] focus:border-[#145943] focus:ring-2 focus:ring-[#145943]/10"
                    >
                    <div id="mobile-search-suggestions" data-storefront-search-results role="listbox" class="absolute left-0 right-0 top-full z-50 mt-2 hidden max-h-80 overflow-y-auto rounded-[4px] border border-[#e3e0d6] bg-white py-1 shadow-lg"></div>
                    <button type="submit" aria-label="Search products" class="absolute right-1 top-1/2 grid h-10 w-10 -translate-y-1/2 place-items-center rounded-[4px] text-[#4d5c56] transition hover:bg-[#e7ebdf] hover:text-[#145943]">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <circle cx="11" cy="11" r="6.5"></circle>
                            <path d="m16 16 4.2 4.2"></path>
                        </svg>
                    </button>
                </form>

            </div>
        </div>
    </div>
</header>
@endif
