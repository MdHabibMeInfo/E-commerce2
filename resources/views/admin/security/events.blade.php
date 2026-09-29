@extends('admin.layout')

@section('title', 'Security Audit Log')

@section('content')
    <header>
        <p class="text-xs font-bold uppercase tracking-[0.2em] text-[#a48335]">Access control</p>
        <h1 class="mt-2 text-2xl font-extrabold text-[#145943]">Security Audit Log</h1>
    </header>

    <div class="overflow-x-auto border border-[#e5e2d9] bg-white">
        <table class="w-full min-w-[720px] text-left text-sm">
            <thead class="border-b border-[#e5e2d9] bg-[#f5f4ec] text-xs uppercase text-[#4d5c56]">
                <tr>
                    <th scope="col" class="px-4 py-3">Event</th>
                    <th scope="col" class="px-4 py-3">Actor</th>
                    <th scope="col" class="px-4 py-3">Target</th>
                    <th scope="col" class="px-4 py-3">IP address</th>
                    <th scope="col" class="px-4 py-3">Time</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-[#ece8dc]">
                @forelse ($securityEvents as $event)
                    <tr>
                        <td class="px-4 py-3 font-semibold text-[#173b32]">{{ str($event->action)->replace('_', ' ')->title() }}</td>
                        <td class="px-4 py-3 text-[#4d5c56]">{{ $event->user?->email ?? 'Unknown account' }}</td>
                        <td class="px-4 py-3 text-[#4d5c56]">{{ $event->context['target_email'] ?? $event->context['role_name'] ?? 'Self' }}</td>
                        <td class="px-4 py-3 font-mono text-xs text-[#4d5c56]">{{ $event->ip_address ?? 'Unknown' }}</td>
                        <td class="px-4 py-3 text-[#4d5c56]">{{ $event->created_at->format('Y-m-d H:i:s') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-8 text-center text-sm text-[#66736d]">No security events recorded.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection