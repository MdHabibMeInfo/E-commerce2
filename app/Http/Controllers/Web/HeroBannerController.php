<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\HeroBanner;
use App\Services\SecurityEventService;
use App\Services\SiteSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

class HeroBannerController extends Controller
{
    public function store(Request $request, SiteSettings $settings, SecurityEventService $events): RedirectResponse
    {
        $validated = $request->validate([
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192', 'dimensions:max_width=4000,max_height=3000'],
            'target' => ['nullable', 'string', 'max:40', 'regex:/^(category|product|variant):[1-9][0-9]*$/'],
            'sort_order' => ['nullable', 'integer', 'between:0,65535'],
        ]);

        if (filled($validated['target'] ?? null)
            && $settings->destinationFor($validated['target']) === null) {
            throw ValidationException::withMessages([
                'target' => 'Choose an active category, product, or available variant.',
            ]);
        }

        $path = $request->file('image')->store('hero-banners', 'public');

        if (! is_string($path)) {
            throw ValidationException::withMessages([
                'image' => 'The image could not be saved. Please try again.',
            ]);
        }

        try {
            HeroBanner::query()->create([
                'image_path' => $path,
                'target' => $validated['target'] ?? null,
                'sort_order' => $validated['sort_order'] ?? (HeroBanner::query()->max('sort_order') ?? -1) + 1,
            ]);
        } catch (Throwable $exception) {
            Storage::disk('public')->delete($path);

            throw $exception;
        }

        $events->record($request, $request->user(), 'hero_banner_created');

        return back()->with('success', 'Hero image added.');
    }

    public function update(Request $request, HeroBanner $heroBanner, SiteSettings $settings, SecurityEventService $events): RedirectResponse
    {
        $validated = $request->validate([
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192', 'dimensions:max_width=4000,max_height=3000'],
            'target' => ['nullable', 'string', 'max:40', 'regex:/^(category|product|variant):[1-9][0-9]*$/'],
            'sort_order' => ['required', 'integer', 'between:0,65535'],
        ]);

        if (filled($validated['target'] ?? null)
            && $settings->destinationFor($validated['target']) === null) {
            throw ValidationException::withMessages([
                'target' => 'Choose an active category, product, or available variant.',
            ]);
        }

        $oldPath = $heroBanner->image_path;
        $newPath = null;

        if ($request->hasFile('image')) {
            $newPath = $request->file('image')->store('hero-banners', 'public');

            if (! is_string($newPath)) {
                throw ValidationException::withMessages([
                    'image' => 'The image could not be saved. Please try again.',
                ]);
            }
        }

        try {
            $heroBanner->update([
                'image_path' => $newPath ?? $oldPath,
                'target' => $validated['target'] ?? null,
                'sort_order' => $validated['sort_order'],
            ]);
        } catch (Throwable $exception) {
            if ($newPath) {
                Storage::disk('public')->delete($newPath);
            }

            throw $exception;
        }

        if ($newPath) {
            Storage::disk('public')->delete($oldPath);
        }

        $events->record($request, $request->user(), 'hero_banner_updated', [
            'hero_banner_id' => $heroBanner->getKey(),
        ]);

        return back()->with('success', 'Hero image updated.');
    }

    public function destroy(Request $request, HeroBanner $heroBanner, SecurityEventService $events): RedirectResponse
    {
        $path = $heroBanner->image_path;
        $heroBanner->delete();
        Storage::disk('public')->delete($path);

        $events->record($request, $request->user(), 'hero_banner_deleted', [
            'hero_banner_id' => $heroBanner->getKey(),
        ]);

        return back()->with('success', 'Hero image deleted.');
    }
}
