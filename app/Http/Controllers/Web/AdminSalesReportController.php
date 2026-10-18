<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\ProductVariant;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Carbon\CarbonPeriod;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminSalesReportController extends Controller
{
  public function index(Request $request): View|StreamedResponse
  {
    $validated = $request->validate([
      'start_date' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:today'],
      'end_date' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:today'],
      'category_id' => ['nullable', 'integer', 'exists:categories,id'],
      'product_id' => ['nullable', 'integer', 'exists:products,id'],
      'variant_id' => ['nullable', 'integer', 'exists:product_variants,id'],
      'export' => ['nullable', 'in:csv'],
    ]);

    if (isset($validated['variant_id'])) {
      $variant = ProductVariant::withTrashed()->find($validated['variant_id']);

      if (! isset($validated['product_id']) || $variant?->product_id !== (int) $validated['product_id']) {
        throw ValidationException::withMessages([
          'variant_id' => 'Choose a variant that belongs to the selected product.',
        ]);
      }
    }

    $startDate = CarbonImmutable::parse($validated['start_date'] ?? now()->subDays(29)->toDateString())->startOfDay();
    $endDate = CarbonImmutable::parse($validated['end_date'] ?? today()->toDateString())->endOfDay();

    if ($endDate->lt($startDate)) {
      throw ValidationException::withMessages([
        'end_date' => 'The end date must be on or after the start date.',
      ]);
    }

    if ($startDate->diffInDays($endDate) > 365) {
      throw ValidationException::withMessages([
        'end_date' => 'Choose a reporting period of 366 days or less.',
      ]);
    }

    $filters = [
      'start_date' => $startDate->toDateString(),
      'end_date' => $endDate->toDateString(),
      'category_id' => $validated['category_id'] ?? null,
      'product_id' => $validated['product_id'] ?? null,
      'variant_id' => $validated['variant_id'] ?? null,
    ];
    $salesItems = $this->salesItems($startDate, $endDate, $filters);

    if (($validated['export'] ?? null) === 'csv') {
      return $this->downloadCsv($salesItems, $startDate, $endDate);
    }

    $salesSummary = (clone $salesItems)
      ->selectRaw('COUNT(DISTINCT o.id) as paid_orders, COALESCE(SUM(oi.quantity), 0) as items_sold, COALESCE(SUM(oi.price * oi.quantity), 0) as gross_sales')
      ->first();
    $grossSales = (float) $salesSummary->gross_sales;
    $paidOrders = (int) $salesSummary->paid_orders;
    $itemsSold = (int) $salesSummary->items_sold;

    $dailySales = (clone $salesItems)
      ->selectRaw('DATE(COALESCE(o.payment_reviewed_at, o.created_at)) as sales_date, SUM(oi.price * oi.quantity) as gross_sales')
      ->groupBy('sales_date')
      ->orderBy('sales_date')
      ->get()
      ->keyBy('sales_date');
    $periodDays = CarbonPeriod::create($startDate->toDateString(), $endDate->toDateString());
    $periodLength = $startDate->diffInDays($endDate) + 1;
    $salesTrend = collect($periodDays)->map(function (CarbonInterface $date) use ($dailySales, $periodLength): array {
      $dateString = $date->toDateString();

      return [
        'date' => $dateString,
        'label' => $periodLength > 60 && $date->dayOfWeek !== 1 ? '' : $date->format('M j'),
        'gross_sales' => (float) ($dailySales->get($dateString)->gross_sales ?? 0),
      ];
    });
    $maxDailySales = max(1, (float) ($salesTrend->max('gross_sales') ?? 0));

    $categorySales = (clone $salesItems)
      ->selectRaw("p.category_id, COALESCE(c.name, 'Uncategorized') as category_name, SUM(oi.quantity) as items_sold, SUM(oi.price * oi.quantity) as gross_sales")
      ->groupBy('p.category_id', 'c.name')
      ->orderByDesc('gross_sales')
      ->paginate(10, ['*'], 'categories_page')
      ->withQueryString();
    $productSales = (clone $salesItems)
      ->selectRaw("oi.product_id, oi.name as product_name, p.sku, p.category_id, p.stock, p.is_out_of_stock, COALESCE(c.name, 'Uncategorized') as category_name, SUM(oi.quantity) as items_sold, SUM(oi.price * oi.quantity) as gross_sales")
      ->groupBy('oi.product_id', 'oi.name', 'p.sku', 'p.category_id', 'p.stock', 'p.is_out_of_stock', 'c.name')
      ->orderByDesc('gross_sales')
      ->paginate(15, ['*'], 'products_page')
      ->withQueryString();
    $variantSales = (clone $salesItems)
      ->selectRaw("oi.product_id, oi.name as product_name, oi.product_variant_id, COALESCE(oi.variant_name, 'Standard product') as variant_name, oi.variant_sku, SUM(oi.quantity) as items_sold, SUM(oi.price * oi.quantity) as gross_sales")
      ->groupBy('oi.product_id', 'oi.name', 'oi.product_variant_id', 'oi.variant_name', 'oi.variant_sku')
      ->orderByDesc('gross_sales')
      ->paginate(15, ['*'], 'variants_page')
      ->withQueryString();

    $reviewsQuery = ProductReview::query()
      ->with(['product' => fn($query) => $query->withTrashed()->select('id', 'name', 'slug')])
      ->whereBetween('created_at', [$startDate, $endDate]);

    if ($filters['product_id'] !== null || $filters['category_id'] !== null) {
      $reviewsQuery->whereHas('product', function ($query) use ($filters): void {
        $query->withTrashed()
          ->when($filters['product_id'] !== null, fn($productQuery) => $productQuery->whereKey($filters['product_id']))
          ->when($filters['category_id'] !== null, fn($productQuery) => $productQuery->where('category_id', $filters['category_id']));
      });
    }

    $reviewMetrics = (clone $reviewsQuery)
      ->selectRaw('COUNT(*) as total_reviews, AVG(rating) as average_rating, SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) as rating_5, SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) as rating_4, SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) as rating_3, SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END) as rating_2, SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) as rating_1')
      ->first();
    $totalReviews = (int) ($reviewMetrics->total_reviews ?? 0);
    $ratingDistribution = collect(range(5, 1))->map(fn(int $rating): array => [
      'rating' => $rating,
      'count' => (int) ($reviewMetrics->{'rating_' . $rating} ?? 0),
      'percentage' => $totalReviews > 0 ? (int) round(((int) ($reviewMetrics->{'rating_' . $rating} ?? 0) / $totalReviews) * 100) : 0,
    ]);
    $reviews = $reviewsQuery
      ->orderBy('rating')
      ->orderByDesc('created_at')
      ->paginate(20, ['*'], 'reviews_page')
      ->withQueryString();

    $categories = Category::query()->withTrashed()->orderBy('name')->get(['id', 'name']);
    $selectedProduct = $filters['product_id'] !== null
      ? Product::query()->withTrashed()->find($filters['product_id'], ['id', 'name', 'sku'])
      : null;
    $variants = $filters['product_id'] !== null
      ? ProductVariant::query()->withTrashed()->where('product_id', $filters['product_id'])->orderBy('name')->get(['id', 'name', 'sku', 'product_id'])
      : collect();

    return view('admin.reports.sales', compact(
      'categories',
      'endDate',
      'filters',
      'grossSales',
      'itemsSold',
      'maxDailySales',
      'paidOrders',
      'selectedProduct',
      'categorySales',
      'productSales',
      'ratingDistribution',
      'reviews',
      'salesTrend',
      'startDate',
      'totalReviews',
      'variantSales',
      'variants',
      'reviewMetrics',
    ));
  }

  public function productSuggestions(Request $request): JsonResponse
  {
    $validated = $request->validate([
      'search' => ['nullable', 'string', 'max:100'],
    ]);
    $search = trim($validated['search'] ?? '');
    $products = Product::query()->withTrashed();

    if ($search === '') {
      $matches = $products->latest('id')->limit(10)->get(['id', 'name', 'sku']);
    } else {
      $prefix = $search . '%';
      $matches = $products
        ->where(function ($query) use ($prefix): void {
          $query->where('name', 'like', $prefix)
            ->orWhere('sku', 'like', $prefix)
            ->orWhere('slug', 'like', $prefix);
        })
        ->orderBy('name')
        ->limit(20)
        ->get(['id', 'name', 'sku']);
    }

    return response()->json($matches->map(fn(Product $product): array => [
      'id' => $product->id,
      'label' => $product->name,
      'detail' => $product->sku,
    ])->values());
  }

  private function salesItems(CarbonImmutable $startDate, CarbonImmutable $endDate, array $filters): Builder
  {
    return DB::table('order_items as oi')
      ->join('orders as o', 'o.id', '=', 'oi.order_id')
      ->leftJoin('products as p', 'p.id', '=', 'oi.product_id')
      ->leftJoin('categories as c', 'c.id', '=', 'p.category_id')
      ->where('o.payment_status', 'paid')
      ->where(function (Builder $query) use ($startDate, $endDate): void {
        $query->whereBetween('o.payment_reviewed_at', [$startDate, $endDate])
          ->orWhere(function (Builder $fallbackQuery) use ($startDate, $endDate): void {
            $fallbackQuery->whereNull('o.payment_reviewed_at')
              ->whereBetween('o.created_at', [$startDate, $endDate]);
          });
      })
      ->when($filters['category_id'] !== null, fn(Builder $query) => $query->where('p.category_id', $filters['category_id']))
      ->when($filters['product_id'] !== null, fn(Builder $query) => $query->where('oi.product_id', $filters['product_id']))
      ->when($filters['variant_id'] !== null, fn(Builder $query) => $query->where('oi.product_variant_id', $filters['variant_id']));
  }

  private function downloadCsv(Builder $salesItems, CarbonImmutable $startDate, CarbonImmutable $endDate): StreamedResponse
  {
    return response()->streamDownload(function () use ($salesItems): void {
      $output = fopen('php://output', 'w');
      fwrite($output, "\xEF\xBB\xBF");
      fputcsv($output, ['Sale date', 'Order number', 'Product', 'SKU', 'Variant', 'Quantity', 'Unit price', 'Merchandise sales'], ',', '"', '');

      $rows = (clone $salesItems)
        ->selectRaw('DATE(COALESCE(o.payment_reviewed_at, o.created_at)) as sale_date')
        ->addSelect('o.order_number', 'oi.name as product_name', 'oi.variant_sku', 'oi.variant_name', 'oi.quantity', 'oi.price')
        ->orderByRaw('COALESCE(o.payment_reviewed_at, o.created_at)')
        ->orderBy('oi.id')
        ->cursor();

      foreach ($rows as $row) {
        fputcsv($output, [
          $row->sale_date,
          $this->safeCsvCell($row->order_number),
          $this->safeCsvCell($row->product_name),
          $this->safeCsvCell($row->variant_sku),
          $this->safeCsvCell($row->variant_name),
          $row->quantity,
          $row->price,
          number_format((float) $row->price * (int) $row->quantity, 2, '.', ''),
        ], ',', '"', '');
      }

      fclose($output);
    }, 'sales-report-' . $startDate->toDateString() . '-to-' . $endDate->toDateString() . '.csv', [
      'Content-Type' => 'text/csv; charset=UTF-8',
      'Cache-Control' => 'private, no-store',
      'X-Content-Type-Options' => 'nosniff',
    ]);
  }

  private function safeCsvCell(?string $value): string
  {
    $value ??= '';

    return preg_match('/^[\s\x00-\x1F]*[=+\-@]/u', $value) === 1 ? "'" . $value : $value;
  }
}
