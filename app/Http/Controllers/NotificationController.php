<?php

namespace App\Http\Controllers;

use App\NotificationCategory;
use App\NotificationPriority;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NotificationController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'status' => ['nullable', 'in:all,unread,read'],
            'category' => ['nullable', 'in:'.collect(NotificationCategory::cases())->pluck('value')->implode(',')],
            'priority' => ['nullable', 'in:'.collect(NotificationPriority::cases())->pluck('value')->implode(',')],
        ]);
        $query = $request->user()->notifications()->latest();

        match ($filters['status'] ?? 'all') {
            'unread' => $query->whereNull('read_at'),
            'read' => $query->whereNotNull('read_at'),
            default => null,
        };
        if (filled($filters['category'] ?? null)) {
            $query->where('data->category', $filters['category']);
        }
        if (filled($filters['priority'] ?? null)) {
            $query->where('data->priority', $filters['priority']);
        }

        return Inertia::render('notifications/Index', [
            'notifications' => $query->paginate(20)->withQueryString()->through(fn ($notification): array => [
                'id' => $notification->id,
                ...$notification->data,
                'read_at' => $notification->read_at?->toIso8601String(),
                'created_at' => $notification->created_at?->toIso8601String(),
            ]),
            'filters' => [
                'status' => $filters['status'] ?? 'all',
                'category' => $filters['category'] ?? '',
                'priority' => $filters['priority'] ?? '',
            ],
            'categories' => collect(NotificationCategory::cases())->map(fn (NotificationCategory $category): array => ['value' => $category->value, 'label' => $category->label()]),
            'priorities' => collect(NotificationPriority::cases())->map(fn (NotificationPriority $priority): array => ['value' => $priority->value, 'label' => $priority->label()]),
        ]);
    }

    public function read(Request $request, string $notification): RedirectResponse
    {
        $request->user()->notifications()->whereKey($notification)->firstOrFail()->markAsRead();

        return back();
    }

    public function readAll(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return back();
    }
}
