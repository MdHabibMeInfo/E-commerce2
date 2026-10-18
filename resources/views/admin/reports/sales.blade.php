@extends('admin.layout')

@section('title', 'Sales Report | Admin Dashboard')

@section('content')
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.18em] text-[#a48335]">Business intelligence</p>
            <h1 class="mt-2 text-3xl font-extrabold text-[#145943]">Sales &amp; customer insight</h1>
            <p class="mt-2 text-sm text-[#59675f]">Paid merchandise performance and customer feedback for {{ $startDate->format('M j, Y') }} to {{ $endDate->format('M j, Y') }}.</p>
        </div>
        <a href="{{ route('admin.reports.sales', [...$filters, 'export' => 'csv']) }}" class="inline-flex items-center gap-2 rounded-sm bg-[#145943] px-4 py-2.5 text-sm font-bold text-white transition hover:bg-[#0f4936]">
            <span aria-hidden="true">↓</span> Export CSV
        </a>
    </div>

    <form method="GET" action="{{ route('admin.reports.sales') }}" class="grid gap-3 border-y border-[#e5e2d9] bg-white p-4 sm:grid-cols-2 lg:grid-cols-6">
        <label class="text-xs font-bold text-[#4d5c56]">
            Start date
            <input type="date" name="start_date" value="{{ $filters['start_date'] }}" max="{{ today()->toDateString() }}" class="mt-1 h-10 w-full rounded-sm border border-[#dfe3cf] bg-white px-3 text-sm text-[#173b32]">
        </label>
        <label class="text-xs font-bold text-[#4d5c56]">
            End date
            <input type="date" name="end_date" value="{{ $filters['end_date'] }}" max="{{ today()->toDateString() }}" class="mt-1 h-10 w-full rounded-sm border border-[#dfe3cf] bg-white px-3 text-sm text-[#173b32]">
        </label>
        <label class="text-xs font-bold text-[#4d5c56]">
            Category
            <select name="category_id" class="mt-1 h-10 w-full rounded-sm border border-[#dfe3cf] bg-white px-3 text-sm text-[#173b32]">
                <option value="">All categories</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" @selected((string) $filters['category_id'] === (string) $category->id)>{{ $category->name }}</option>
                @endforeach
            </select>
        </label>
        <div class="relative min-w-0">
            <label for="sales-product-search" class="block text-xs font-bold text-[#4d5c56]">Product</label>
            <div class="relative mt-1">
                <input id="sales-product-search" data-admin-filter-input data-filter-suggestions-url="{{ route('admin.reports.sales.products') }}" type="search" value="{{ $selectedProduct?->name }}" placeholder="All products · search name or SKU" autocomplete="off" aria-autocomplete="list" aria-controls="sales-product-suggestions" aria-expanded="false" role="combobox" class="h-10 w-full rounded-sm border border-[#dfe3cf] bg-white px-3 text-sm text-[#173b32]">
                <input data-filter-value type="hidden" name="product_id" value="{{ $filters['product_id'] }}">
                <div id="sales-product-suggestions" data-admin-filter-results role="listbox" aria-label="Product suggestions" class="absolute inset-x-0 top-full z-20 mt-1 hidden max-h-72 overflow-y-auto rounded-sm border border-[#dfe3cf] bg-white py-1 shadow-lg"></div>
            </div>
        </div>
        <label class="text-xs font-bold text-[#4d5c56]">
            Variant
            <select name="variant_id" @disabled($variants->isEmpty()) class="mt-1 h-10 w-full rounded-sm border border-[#dfe3cf] bg-white px-3 text-sm text-[#173b32] disabled:bg-[#f5f4ec]">
                <option value="">All variants</option>
                @foreach ($variants as $variant)
                    <option value="{{ $variant->id }}" @selected((string) $filters['variant_id'] === (string) $variant->id)>{{ $variant->name ?: $variant->sku }}</option>
                @endforeach
            </select>
        </label>
        <div class="flex items-end gap-2">
            <button type="submit" class="h-10 flex-1 rounded-sm bg-[#145943] px-3 text-sm font-bold text-white">Apply</button>
            <a href="{{ route('admin.reports.sales') }}" class="grid h-10 w-10 place-items-center rounded-sm border border-[#dfe3cf] text-sm font-bold text-[#4d5c56]" aria-label="Reset filters" title="Reset filters">↺</a>
        </div>
        @error('start_date')<p class="text-sm text-[#a13e2d] sm:col-span-2 lg:col-span-6">{{ $message }}</p>@enderror
        @error('end_date')<p class="text-sm text-[#a13e2d] sm:col-span-2 lg:col-span-6">{{ $message }}</p>@enderror
        @error('variant_id')<p class="text-sm text-[#a13e2d] sm:col-span-2 lg:col-span-6">{{ $message }}</p>@enderror
    </form>

    <section aria-label="Sales summary" class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <article class="border-l-4 border-[#145943] bg-white p-5">
            <p class="text-xs font-bold uppercase tracking-wide text-[#59675f]">Gross merchandise sales</p>
            <p class="mt-2 text-2xl font-extrabold text-[#173b32]">৳{{ number_format($grossSales, 2) }}</p>
            <p class="mt-1 text-xs text-[#7b857e]">Before order-level discounts and delivery</p>
        </article>
        <article class="border-l-4 border-[#a48335] bg-white p-5">
            <p class="text-xs font-bold uppercase tracking-wide text-[#59675f]">Paid orders</p>
            <p class="mt-2 text-2xl font-extrabold text-[#173b32]">{{ number_format($paidOrders) }}</p>
            <p class="mt-1 text-xs text-[#7b857e]">Orders contributing selected items</p>
        </article>
        <article class="border-l-4 border-[#3f7e8d] bg-white p-5">
            <p class="text-xs font-bold uppercase tracking-wide text-[#59675f]">Items sold</p>
            <p class="mt-2 text-2xl font-extrabold text-[#173b32]">{{ number_format($itemsSold) }}</p>
            <p class="mt-1 text-xs text-[#7b857e]">Units in paid order lines</p>
        </article>
        <article class="border-l-4 border-[#b85c43] bg-white p-5">
            <p class="text-xs font-bold uppercase tracking-wide text-[#59675f]">Average order value</p>
            <p class="mt-2 text-2xl font-extrabold text-[#173b32]">৳{{ number_format($paidOrders > 0 ? $grossSales / $paidOrders : 0, 2) }}</p>
            <p class="mt-1 text-xs text-[#7b857e]">Selected merchandise sales per paid order</p>
        </article>
    </section>

    <section class="border border-[#e5e2d9] bg-white p-5 sm:p-6">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h2 class="text-lg font-extrabold text-[#173b32]">Daily sales trend</h2>
                <p class="mt-1 text-sm text-[#59675f]">Counted on payment confirmation date where available.</p>
            </div>
            <p class="text-sm font-bold text-[#145943]">৳{{ number_format($grossSales, 2) }} total</p>
        </div>
        <div class="mt-6 overflow-x-auto pb-2">
            <div class="flex h-44 min-w-full items-end gap-1 border-b border-[#e5e2d9]" style="min-width: {{ max(100, $salesTrend->count() * 18) }}px">
                @foreach ($salesTrend as $day)
                    @php($barHeight = $day['gross_sales'] > 0 ? max(4, (int) round(($day['gross_sales'] / $maxDailySales) * 100)) : 1)
                    <div class="group flex h-full min-w-0 flex-1 flex-col justify-end" title="{{ $day['date'] }}: ৳{{ number_format($day['gross_sales'], 2) }}">
                        <div class="w-full rounded-t-xs {{ $day['gross_sales'] > 0 ? 'bg-[#145943] group-hover:bg-[#a48335]' : 'bg-[#e8ece5]' }}" style="height: {{ $barHeight }}%"></div>
                        <span class="mt-2 h-4 text-center text-[9px] text-[#7b857e]">{{ $day['label'] }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <section class="grid gap-6 xl:grid-cols-2">
        <article class="border border-[#e5e2d9] bg-white">
            <div class="flex items-center justify-between gap-3 border-b border-[#e5e2d9] px-5 py-4">
                <div><h2 class="font-extrabold text-[#173b32]">Category performance</h2><p class="mt-1 text-xs text-[#7b857e]">Ranked by gross merchandise sales</p></div>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-110 text-left text-sm">
                    <thead class="bg-[#f7f8f3] text-xs uppercase text-[#59675f]"><tr><th class="px-5 py-3">Category</th><th class="px-3 py-3 text-right">Units</th><th class="px-5 py-3 text-right">Sales</th></tr></thead>
                    <tbody class="divide-y divide-[#eceee8]">
                        @forelse ($categorySales as $row)
                            <tr><td class="px-5 py-3 font-semibold text-[#173b32]">{{ $row->category_name }}</td><td class="px-3 py-3 text-right text-[#59675f]">{{ number_format($row->items_sold) }}</td><td class="px-5 py-3 text-right font-bold text-[#145943]">৳{{ number_format($row->gross_sales, 2) }}</td></tr>
                        @empty
                            <tr><td colspan="3" class="px-5 py-8 text-center text-sm text-[#7b857e]">No paid sales in this selection.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($categorySales->hasPages())<div class="border-t border-[#e5e2d9] px-5 py-3">{{ $categorySales->links() }}</div>@endif
        </article>

        <article class="border border-[#e5e2d9] bg-white">
            <div class="border-b border-[#e5e2d9] px-5 py-4"><h2 class="font-extrabold text-[#173b32]">Product performance</h2><p class="mt-1 text-xs text-[#7b857e]">Best-selling products for the selected period</p></div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-135 text-left text-sm">
                    <thead class="bg-[#f7f8f3] text-xs uppercase text-[#59675f]"><tr><th class="px-5 py-3">Product</th><th class="px-3 py-3">Category</th><th class="px-3 py-3 text-right">Units</th><th class="px-3 py-3 text-right">Stock</th><th class="px-5 py-3 text-right">Sales</th></tr></thead>
                    <tbody class="divide-y divide-[#eceee8]">
                        @forelse ($productSales as $row)
                            <tr><td class="px-5 py-3"><span class="font-semibold text-[#173b32]">{{ $row->product_name }}</span><span class="mt-1 block text-xs text-[#7b857e]">{{ $row->sku ?: 'No SKU' }}</span></td><td class="px-3 py-3 text-[#59675f]">{{ $row->category_name }}</td><td class="px-3 py-3 text-right text-[#59675f]">{{ number_format($row->items_sold) }}</td><td class="px-3 py-3 text-right {{ $row->is_out_of_stock || ($row->stock !== null && $row->stock <= 5) ? 'font-bold text-[#a13e2d]' : 'text-[#59675f]' }}">{{ $row->is_out_of_stock ? 'Out of stock' : ($row->stock ?? 'Not tracked') }}</td><td class="px-5 py-3 text-right font-bold text-[#145943]">৳{{ number_format($row->gross_sales, 2) }}</td></tr>
                        @empty
                            <tr><td colspan="5" class="px-5 py-8 text-center text-sm text-[#7b857e]">No paid sales in this selection.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($productSales->hasPages())<div class="border-t border-[#e5e2d9] px-5 py-3">{{ $productSales->links() }}</div>@endif
        </article>
    </section>

    <section class="border border-[#e5e2d9] bg-white">
        <div class="border-b border-[#e5e2d9] px-5 py-4"><h2 class="font-extrabold text-[#173b32]">Variant performance</h2><p class="mt-1 text-xs text-[#7b857e]">Variant labels and SKUs use the order-time snapshot.</p></div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-150 text-left text-sm">
                <thead class="bg-[#f7f8f3] text-xs uppercase text-[#59675f]"><tr><th class="px-5 py-3">Product</th><th class="px-3 py-3">Variant</th><th class="px-3 py-3">SKU</th><th class="px-3 py-3 text-right">Units</th><th class="px-5 py-3 text-right">Sales</th></tr></thead>
                <tbody class="divide-y divide-[#eceee8]">
                    @forelse ($variantSales as $row)
                        <tr><td class="px-5 py-3 font-semibold text-[#173b32]">{{ $row->product_name }}</td><td class="px-3 py-3 text-[#59675f]">{{ $row->variant_name }}</td><td class="px-3 py-3 text-[#59675f]">{{ $row->variant_sku ?: '—' }}</td><td class="px-3 py-3 text-right text-[#59675f]">{{ number_format($row->items_sold) }}</td><td class="px-5 py-3 text-right font-bold text-[#145943]">৳{{ number_format($row->gross_sales, 2) }}</td></tr>
                    @empty
                        <tr><td colspan="5" class="px-5 py-8 text-center text-sm text-[#7b857e]">No paid sales in this selection.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($variantSales->hasPages())<div class="border-t border-[#e5e2d9] px-5 py-3">{{ $variantSales->links() }}</div>@endif
    </section>

    <section class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_minmax(0,1.7fr)]">
        <article class="border border-[#e5e2d9] bg-white p-5">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div><h2 class="font-extrabold text-[#173b32]">Customer review pulse</h2><p class="mt-1 text-sm text-[#59675f]">{{ number_format($totalReviews) }} reviews in the selected period</p></div>
                <p class="text-right text-2xl font-extrabold text-[#a48335]">{{ $totalReviews > 0 ? number_format((float) $reviewMetrics->average_rating, 1) : '—' }}<span class="text-sm text-[#7b857e]"> / 5</span></p>
            </div>
            <div class="mt-5 space-y-3">
                @foreach ($ratingDistribution as $rating)
                    <div class="grid grid-cols-[40px_minmax(0,1fr)_32px] items-center gap-3 text-xs">
                        <span class="font-semibold text-[#59675f]">{{ $rating['rating'] }} star</span>
                        <div class="h-2 overflow-hidden rounded-full bg-[#edf0e9]"><div class="h-full bg-[#a48335]" style="width: {{ $rating['percentage'] }}%"></div></div>
                        <span class="text-right text-[#59675f]">{{ $rating['count'] }}</span>
                    </div>
                @endforeach
            </div>
            @php($lowRatingCount = $ratingDistribution->where('rating', '<=', 2)->sum('count'))
            <div class="mt-5 border-t border-[#e5e2d9] pt-4 text-sm {{ $lowRatingCount > 0 ? 'text-[#a13e2d]' : 'text-[#145943]' }}">
                @if ($lowRatingCount > 0)
                    <strong>{{ $lowRatingCount }} low-rated {{ \Illuminate\Support\Str::plural('review', $lowRatingCount) }} need attention.</strong>
                @else
                    No 1- or 2-star feedback in this period.
                @endif
            </div>
            @if ($filters['variant_id'] !== null)<p class="mt-3 text-xs text-[#7b857e]">Review analysis follows category/product filters; reviews are not variant-specific.</p>@endif
        </article>

        <article class="border border-[#e5e2d9] bg-white">
            <div class="border-b border-[#e5e2d9] px-5 py-4"><h2 class="font-extrabold text-[#173b32]">Customer feedback details</h2><p class="mt-1 text-xs text-[#7b857e]">Lowest ratings appear first to surface product issues.</p></div>
            <div class="divide-y divide-[#eceee8]">
                @forelse ($reviews as $review)
                    <div class="px-5 py-4">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div><p class="font-bold text-[#173b32]">{{ $review->name }} <span class="font-normal text-[#7b857e]">· {{ $review->email }}</span></p><p class="mt-1 text-xs text-[#59675f]">{{ $review->product?->name ?? 'Deleted product' }} · {{ $review->created_at->format('M j, Y g:i A') }}</p></div>
                            <div class="flex items-center gap-3">
                                <span class="rounded-[3px] px-2 py-1 text-[10px] font-bold uppercase {{ $review->rejected_at ? 'bg-[#fcebe7] text-[#a13e2d]' : ($review->is_approved ? 'bg-[#edf8f1] text-[#145943]' : 'bg-[#fef0d8] text-[#a15b1d]') }}">{{ $review->rejected_at ? 'Rejected' : ($review->is_approved ? 'Published' : 'Pending') }}</span>
                                <span class="whitespace-nowrap text-sm font-extrabold {{ $review->rating <= 2 ? 'text-[#a13e2d]' : 'text-[#a48335]' }}">{{ $review->rating }} / 5</span>
                            </div>
                        </div>
                        <p class="mt-3 whitespace-pre-line text-sm leading-6 text-[#35443d]">{{ $review->body }}</p>
                        @if ($review->images)
                            <div class="mt-3 flex flex-wrap gap-2">
                                @foreach ($review->images as $image)
                                    <a href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($image) }}" target="_blank" rel="noopener" class="block overflow-hidden rounded-[3px] border border-[#e5e2d9]">
                                        <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($image) }}" alt="Photo submitted by {{ $review->name }}" loading="lazy" class="h-16 w-16 object-cover">
                                    </a>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @empty
                    <p class="px-5 py-8 text-center text-sm text-[#7b857e]">No customer reviews in this selection.</p>
                @endforelse
            </div>
            @if ($reviews->hasPages())<div class="border-t border-[#e5e2d9] px-5 py-3">{{ $reviews->links() }}</div>@endif
        </article>
    </section>
@endsection