<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductReviewRequest;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;

class ProductReviewController extends Controller
{
    public function __invoke(StoreProductReviewRequest $request, string $product): RedirectResponse
    {
        $product = Product::query()
            ->where('slug', $product)
            ->where('is_active', true)
            ->firstOrFail();

        $validated = $request->validated();
        $imagePaths = [];

        foreach ($validated['images'] ?? [] as $image) {
            if ($image instanceof UploadedFile) {
                $path = $image->store('product-reviews', 'public');

                if ($path !== false) {
                    $imagePaths[] = $path;
                }
            }
        }

        $product->reviews()->create([
            'name' => $request->user()->name,
            'email' => $request->user()->email,
            'rating' => $validated['rating'],
            'body' => $validated['body'],
            'images' => $imagePaths,
        ]);

        return redirect()->to(route('products.show', ['product' => $product->slug]) . '#customer-reviews')
            ->with('status', 'Thanks, your review has been submitted and is awaiting approval.');
    }
}
