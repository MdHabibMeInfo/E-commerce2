<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductReview;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminReviewController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'status' => ['nullable', Rule::in(['pending', 'published', 'rejected', 'all'])],
        ]);
        $status = $validated['status'] ?? 'pending';
        $query = ProductReview::query()->with('product:id,name,slug');

        if ($status === 'pending') {
            $query->pending();
        } elseif ($status === 'published') {
            $query->where('is_approved', true);
        } elseif ($status === 'rejected') {
            $query->whereNotNull('rejected_at');
        }

        $reviews = $query->latest()->paginate(20)->withQueryString();
        $counts = [
            'pending' => ProductReview::pending()->count(),
            'published' => ProductReview::query()->where('is_approved', true)->count(),
            'rejected' => ProductReview::query()->whereNotNull('rejected_at')->count(),
        ];

        return view('admin.reviews.index', compact('reviews', 'status', 'counts'));
    }

    public function show(ProductReview $review): View
    {
        $review->load('product:id,name,slug');

        return view('admin.reviews.show', compact('review'));
    }

    public function update(Request $request, ProductReview $review): RedirectResponse
    {
        $validated = $request->validate([
            'decision' => ['required', Rule::in(['publish', 'reject'])],
        ]);

        abort_unless(! $review->is_approved && $review->rejected_at === null, 409);

        $isPublished = $validated['decision'] === 'publish';
        $review->update([
            'is_approved' => $isPublished,
            'rejected_at' => $isPublished ? null : now(),
        ]);

        return redirect()->route('admin.reviews.show', $review)
            ->with('status', $isPublished ? 'Review published on the storefront.' : 'Review rejected and kept off the storefront.');
    }

    public function updateProductApproval(Request $request, Product $product, ProductReview $review): RedirectResponse
    {
        abort_unless($review->product_id === $product->id, 404);

        $validated = $request->validate([
            'is_approved' => ['required', 'boolean'],
        ]);
        $isApproved = (bool) $validated['is_approved'];

        $review->update([
            'is_approved' => $isApproved,
            'rejected_at' => $isApproved ? null : now(),
        ]);

        return back()->with('status', $isApproved ? 'Review approved.' : 'Review hidden from the storefront.');
    }
}
