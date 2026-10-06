<x-layouts.account>
    <x-slot:title>My Profile | {{ config('app.name') }}</x-slot:title>

    <div class="space-y-6">
        <header>
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-[#a48335]">Your account</p>
            <h1 class="mt-2 text-3xl font-extrabold text-[#145943]">My Profile</h1>
        </header>

        @if (session('status'))
            <p class="rounded-[4px] border border-green-200 bg-green-50 px-4 py-3 text-sm font-semibold text-green-800">{{ session('status') }}</p>
        @endif

        <div class="space-y-6">
                    <section class="rounded-[22px] border border-[#e4e3d8] bg-white p-6 shadow-sm">
                        <h2 class="text-xl font-extrabold text-[#145943]">Profile details</h2>

                        <form method="POST" action="{{ route('account.profile.update') }}" enctype="multipart/form-data" class="mt-5 space-y-4">
                            @csrf
                            @method('PUT')

                            <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
                                @if ($user->profile_photo_path)
                                    <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($user->profile_photo_path) }}" alt="Current profile photo" class="h-20 w-20 rounded-full border border-[#dce6ce] object-cover">
                                @else
                                    <div class="grid h-20 w-20 place-items-center rounded-full bg-[#edf2e4] text-2xl font-bold text-[#145943]" aria-hidden="true">
                                        {{ strtoupper(substr($user->name ?? 'U', 0, 1)) }}
                                    </div>
                                @endif
                                <div data-profile-photo-field class="flex w-full flex-col gap-3 sm:flex-row sm:items-end">
                                    <label class="block min-w-0 flex-1 text-sm font-semibold text-[#4d5c56]">
                                        Update photo
                                        <input type="file" name="profile_photo" accept="image/jpeg,image/png,image/webp" data-profile-photo-input class="mt-1 block w-full rounded-[4px] border border-[#dfe3cf] bg-white text-sm text-[#4d5c56] file:mr-3 file:border-0 file:bg-[#edf2e4] file:px-3 file:py-2.5 file:text-sm file:font-semibold file:text-[#145943]">
                                    </label>
                                    <img data-profile-photo-preview alt="Selected profile photo" hidden class="h-14 w-14 shrink-0 rounded-full border border-[#dce6ce] object-cover">
                                    @if ($user->profile_photo_path)
                                        <button type="submit" form="account-profile-photo-remove" class="shrink-0 rounded-[4px] border border-[#dfe3cf] px-4 py-2.5 text-sm font-bold text-[#145943]">Remove photo</button>
                                    @endif
                                </div>
                            </div>

                            <div class="grid gap-4 md:grid-cols-2">
                                <label class="block text-sm font-semibold text-[#4d5c56]">
                                    Full name
                                    <input type="text" name="name" value="{{ old('name', $user->name) }}" required maxlength="255" class="mt-1 h-11 w-full rounded-[4px] border border-[#dfe3cf] px-3 text-sm text-[#173b32]">
                                </label>
                                <label class="block text-sm font-semibold text-[#4d5c56]">
                                    Phone number
                                    <input type="tel" name="phone" value="{{ old('phone', $user->phone) }}" maxlength="20" class="mt-1 h-11 w-full rounded-[4px] border border-[#dfe3cf] px-3 text-sm text-[#173b32]">
                                </label>
                            </div>

                            <div class="flex flex-wrap gap-3">
                                <button type="submit" class="rounded-[4px] bg-[#145943] px-5 py-2.5 text-sm font-bold text-white">Save profile</button>
                            </div>
                        </form>

                        @if ($user->profile_photo_path)
                            <form id="account-profile-photo-remove" method="POST" action="{{ route('account.profile.photo.destroy') }}" class="hidden">
                                @csrf
                                @method('DELETE')
                            </form>
                        @endif

                        <form method="POST" action="{{ route('logout') }}" class="mt-3">
                            @csrf
                            <button type="submit" class="rounded-[4px] border border-[#dfe3cf] px-5 py-2.5 text-sm font-bold text-[#145943]">Sign out</button>
                        </form>
                    </section>

                    <section class="rounded-[22px] border border-[#e4e3d8] bg-white p-6 shadow-sm">
                        <div class="flex items-center justify-between gap-3">
                            <h2 class="text-xl font-extrabold text-[#145943]">Active orders</h2>
                            <a href="{{ route('account.orders') }}" class="text-sm font-bold text-[#145943]">View all</a>
                        </div>

                        @if ($activeOrders->isEmpty())
                            <p class="mt-4 text-sm text-[#738078]">No active orders right now.</p>
                        @else
                            <div class="mt-5 space-y-4">
                                @foreach ($activeOrders as $order)
                                    @php $shipment = $order->shipments->last(); @endphp
                                    <div class="rounded-[16px] border border-[#eef0ea] bg-[#fafaf7] p-4">
                                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                            <div>
                                                <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-[#a48335]">Order</p>
                                                <p class="mt-1 text-lg font-extrabold text-[#173b32]">{{ $order->order_number }}</p>
                                            </div>
                                            <div class="text-left sm:text-right">
                                                <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-[#a48335]">Status</p>
                                                <p class="mt-1 text-sm font-semibold text-[#145943]">{{ $order->status }}</p>
                                            </div>
                                        </div>
                                        <div class="mt-4 flex flex-wrap gap-3">
                                            <a href="{{ route('account.orders.show', $order) }}" class="rounded-[4px] bg-[#145943] px-4 py-2 text-sm font-bold text-white">View details</a>
                                            @if ($shipment)
                                                <a href="{{ route('account.orders.show', $order) }}#tracking" class="rounded-[4px] border border-[#dfe3cf] bg-white px-4 py-2 text-sm font-bold text-[#145943]">Track order</a>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </section>

        </div>
    </div>
</x-layouts.account>
