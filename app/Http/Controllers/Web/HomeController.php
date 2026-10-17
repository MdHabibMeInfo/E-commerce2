<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\HeroBanner;
use App\Models\Product;
use App\Models\ProductReview;
use App\Services\CouponService;
use App\Services\SiteSettings;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;

class HomeController extends Controller
{
    public function about(SiteSettings $settings): View
    {
        return view('storefront.about', [
            'aboutUsArticle' => $settings->get('about_us_article', ''),
        ]);
    }

    public function contact(SiteSettings $settings): View
    {
        $whatsappNumber = $settings->get('whatsapp_number');
        $whatsappDigits = preg_replace('/\D+/', '', (string) $whatsappNumber) ?? '';

        if (str_starts_with($whatsappDigits, '0')) {
            $whatsappDigits = '880' . substr($whatsappDigits, 1);
        }

        $configuredWhatsappUrl = $settings->get('whatsapp_url');

        return view('storefront.contact', [
            'contactContent' => $settings->get('contact_page_content', ''),
            'whatsappNumber' => $whatsappNumber,
            'callUrl' => filled($whatsappDigits) ? 'tel:+' . $whatsappDigits : null,
            'whatsappUrl' => filled($configuredWhatsappUrl)
                ? $configuredWhatsappUrl
                : (filled($whatsappDigits) ? 'https://wa.me/' . $whatsappDigits : null),
        ]);
    }

    public function terms(SiteSettings $settings): View
    {
        return $this->policyPage($settings, 'terms_conditions', 'Terms & Conditions', 'শর্তাবলী', 'terms');
    }

    public function privacyPolicy(SiteSettings $settings): View
    {
        return $this->policyPage($settings, 'privacy_policy', 'Privacy Policy', 'গোপনীয়তা নীতি', 'privacy');
    }

    public function returnRefundPolicy(SiteSettings $settings): View
    {
        return $this->policyPage($settings, 'return_refund_policy', 'Return / Refund Policy', 'রিটার্ন ও রিফান্ড পলিসি', 'returns');
    }

    private function policyPage(
        SiteSettings $settings,
        string $settingKey,
        string $title,
        string $banglaTitle,
        string $routeName,
    ): View {
        return view('storefront.policy', [
            'policyTitle' => $title,
            'policyBanglaTitle' => $banglaTitle,
            'policyContent' => $settings->get($settingKey, ''),
            'policyRoute' => $routeName,
        ]);
    }

    public function index(SiteSettings $settings, CouponService $promotions): View
    {
        $topSellingProducts = Product::query()
            ->where('is_active', true)
            ->select([
                'products.id',
                'products.category_id',
                'products.name',
                'products.slug',
                'products.type',
                'products.price',
                'products.compare_at_price',
                'products.is_out_of_stock',
            ])
            ->withSum('orderItems as units_sold', 'quantity')
            ->with([
                'primaryImage',
                'images:id,product_id,image_path,alt_text,is_primary,sort_order',
                'variants:id,product_id,price,is_active,is_out_of_stock',
            ])
            ->orderBy('is_out_of_stock')
            ->orderByDesc('units_sold')
            ->orderByDesc('products.id')
            ->limit(4)
            ->get();

        $categories = Category::query()
            ->whereNull('parent_id')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->limit(12)
            ->get([
                'id',
                'name',
                'slug',
                'image',
            ]);

        $categoryRows = Category::query()
            ->whereNull('parent_id')
            ->where('is_active', true)
            ->whereHas('products', function ($query) {
                $query->where('is_active', true);
            })
            ->with(['products' => function ($query) {
                $query
                    ->where('is_active', true)
                    ->with([
                        'primaryImage',
                        'images:id,product_id,image_path,alt_text,is_primary,sort_order',
                        'variants:id,product_id,price,is_active,is_out_of_stock',
                    ])
                    ->orderBy('is_out_of_stock')
                    ->orderByDesc('id')
                    ->limit(10);
            }])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->limit(5)
            ->get([
                'id',
                'name',
                'slug',
            ]);

        $newArrivals = $this->homeProductsQuery()
            ->latest('created_at')
            ->latest('id')
            ->limit(12)
            ->get();

        $justForYouProducts = $this->homeProductsQuery()
            ->where('is_featured', true)
            ->orderBy('is_out_of_stock')
            ->latest('id')
            ->limit(8)
            ->get();

        if ($justForYouProducts->isEmpty()) {
            $justForYouProducts = $newArrivals->take(8);
        }

        $promotions->attachCatalogOffers(
            $topSellingProducts
                ->concat($newArrivals)
                ->concat($justForYouProducts)
                ->concat($categoryRows->flatMap(fn(Category $category) => $category->products))
                ->unique('id'),
        );

        $approvedReviews = ProductReview::query()
            ->where('is_approved', true)
            ->whereHas('product', fn($query) => $query->where('is_active', true))
            ->with([
                'product:id,name,slug',
                'reviewer:id,email,profile_photo_path',
            ])
            ->latest()
            ->limit(6)
            ->get();

        $benefits = config('storefront.home_benefits', []);
        $heroBanners = HeroBanner::query()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
        $heroBannerDestinations = $heroBanners->mapWithKeys(fn(HeroBanner $heroBanner): array => [
            $heroBanner->id => $heroBanner->target
                ? $settings->destinationFor($heroBanner->target)
                : null,
        ]);

        return view('storefront.home', [
            'heroBanners' => $heroBanners,
            'heroBannerDestinations' => $heroBannerDestinations,
            'topSellingProducts' => $topSellingProducts,
            'categories' => $categories,
            'categoryRows' => $categoryRows,
            'newArrivals' => $newArrivals,
            'justForYouProducts' => $justForYouProducts,
            'approvedReviews' => $approvedReviews,
            'benefits' => $benefits,
        ]);
    }

    private function homeProductsQuery(): Builder
    {
        return Product::query()
            ->where('is_active', true)
            ->select([
                'products.id',
                'products.category_id',
                'products.name',
                'products.slug',
                'products.type',
                'products.short_description',
                'products.price',
                'products.compare_at_price',
                'products.is_out_of_stock',
            ])
            ->with([
                'category:id,name,slug',
                'primaryImage',
                'images:id,product_id,image_path,alt_text,is_primary,sort_order',
                'variants:id,product_id,price,is_active,is_out_of_stock',
            ]);
    }
}
