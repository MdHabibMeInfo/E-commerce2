<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('orders.view');
    }

    public function view(User $user, Order $order): bool
    {
        if ($user->can('access-admin') && $user->can('orders.view')) {
            return true;
        }

        return $order->user_id !== null && $order->user_id === $user->id;
    }

    public function createShipment(User $user, Order $order): bool
    {
        return $user->can('orders.manage') || $user->can('access-admin');
    }

    public function reviewPayment(User $user, Order $order): bool
    {
        return $user->can('orders.manage') || $user->can('access-admin');
    }

    public function cancel(User $user, Order $order): bool
    {
        return $order->user_id !== null
            && $order->user_id === $user->id
            && $order->status === 'pending'
            && ! $order->shipments()->exists();
    }
}
