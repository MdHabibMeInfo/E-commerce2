<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\ProductReview;
use App\Models\User;
use App\Notifications\SecurityAlertNotification;
use App\Services\SecurityEventService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AdminCustomerController extends Controller
{
    public function index(Request $request): View
    {
        $query = User::query()
            ->where('account_type', 'customer')
            ->withCount('orders');
        $search = $request->string('search')->trim()->toString();

        if ($search !== '') {
            $query->where(function ($customers) use ($search): void {
                $customers->where('name', 'like', '%'.$search.'%')
                    ->orWhere('email', 'like', '%'.$search.'%')
                    ->orWhere('phone', 'like', '%'.$search.'%');
            });
        }

        if (in_array($request->input('status'), ['active', 'suspended'], true)) {
            $query->where('status', $request->input('status'));
        }

        $customers = $query->latest()->paginate(25)->withQueryString();
        $stats = [
            'active' => User::query()->where('account_type', 'customer')->where('status', 'active')->count(),
            'suspended' => User::query()->where('account_type', 'customer')->where('status', 'suspended')->count(),
        ];

        return view('admin.customers.index', compact('customers', 'search', 'stats'));
    }

    public function show(User $user): View
    {
        abort_unless($user->account_type === 'customer', 404);

        $orders = $user->orders()
            ->latest()
            ->paginate(10, ['*'], 'orders_page')
            ->withQueryString();
        $reviews = ProductReview::query()
            ->where('email', $user->email)
            ->with('product:id,name,slug')
            ->latest()
            ->paginate(10, ['*'], 'reviews_page')
            ->withQueryString();
        $stats = [
            'order_count' => $orders->total(),
            'order_value' => $user->orders()->sum('total'),
            'review_count' => $reviews->total(),
        ];

        return view('admin.customers.show', compact('user', 'orders', 'reviews', 'stats'));
    }

    public function updateStatus(Request $request, User $user, SecurityEventService $events): RedirectResponse
    {
        abort_unless($user->account_type === 'customer', 404);
        $validated = $request->validate([
            'status' => ['required', Rule::in(['active', 'suspended'])],
        ]);

        $previousStatus = $user->status;
        $user->forceFill(['status' => $validated['status']])->save();

        if ($previousStatus !== $validated['status']) {
            if ($validated['status'] === 'suspended') {
                $user->tokens()->delete();

                if (config('session.driver') === 'database') {
                    DB::table(config('session.table'))->where('user_id', $user->getKey())->delete();
                }
            }

            $user->notify(new SecurityAlertNotification(
                'Customer account status changed',
                'Your account status was changed to '.$validated['status'].'.',
            ));
        }

        $events->record($request, $request->user(), 'customer_status_updated', [
            'target_user_id' => $user->getKey(),
            'target_email' => $user->email,
            'previous_status' => $previousStatus,
            'new_status' => $user->status,
        ]);

        return redirect()->route('admin.customers.index')->with('success', 'Customer account status updated.');
    }
}
