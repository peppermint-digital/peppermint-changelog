<?php

namespace Peppermint\Changelog\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inertia\Response;
use Peppermint\Changelog\Models\ChangelogRead;
use Peppermint\Changelog\Services\ChangelogService;

class AdminChangelogController extends Controller
{
    public function __construct(
        protected ChangelogService $changelogService
    ) {}

    public function index(): Response
    {
        $changelogs = $this->changelogService->all()
            ->map(function ($changelog) {
                $readCount = ChangelogRead::where('changelog_slug', $changelog['slug'])
                    ->whereNotNull('read_at')
                    ->count();

                return [
                    'slug' => $changelog['slug'],
                    'version' => $changelog['version'],
                    'title' => $changelog['title'],
                    'is_markdown' => $changelog['is_markdown'],
                    'published_at' => $changelog['published_at']->format('d.m.Y H:i'),
                    'show_modal' => $changelog['show_modal'],
                    'is_active' => $changelog['is_active'],
                    'is_published' => $changelog['is_published'],
                    'reads_count' => $readCount,
                ];
            });

        return Inertia::render(config('changelog.pages.admin.index'), [
            'changelogs' => $changelogs,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render(config('changelog.pages.admin.create'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'slug' => ['required', 'string', 'max:100', 'regex:/^[a-z0-9-]+$/'],
            'version' => ['nullable', 'string', 'max:50'],
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'published_at' => ['nullable', 'date'],
            'show_modal' => ['boolean'],
            'is_active' => ['boolean'],
        ]);

        $this->changelogService->create($validated);

        return redirect()->route(config('changelog.routes.admin.name_prefix').'index')
            ->with('success', 'Changelog wurde erfolgreich erstellt.');
    }

    public function show(string $slug): Response
    {
        $changelog = $this->changelogService->find($slug);

        if (! $changelog) {
            abort(404);
        }

        $reads = ChangelogRead::where('changelog_slug', $slug)
            ->whereNotNull('read_at')
            ->with('user:id,name')
            ->orderByDesc('read_at')
            ->get();

        $readers = $reads->map(fn ($read) => [
            'id' => $read->user->id,
            'name' => $read->user->name,
            'read_at' => $read->read_at->format('d.m.Y H:i'),
            'modal_dismissed' => $read->modal_dismissed,
        ]);

        return Inertia::render(config('changelog.pages.admin.show'), [
            'changelog' => [
                'slug' => $changelog['slug'],
                'version' => $changelog['version'],
                'title' => $changelog['title'],
                'content' => $changelog['content'],
                'html_content' => $changelog['html_content'],
                'is_markdown' => $changelog['is_markdown'],
                'published_at' => $changelog['published_at']->format('d.m.Y H:i'),
                'show_modal' => $changelog['show_modal'],
                'is_active' => $changelog['is_active'],
                'is_published' => $changelog['is_published'],
                'reads_count' => $reads->count(),
                'readers' => $readers,
            ],
        ]);
    }

    public function edit(string $slug): Response
    {
        $changelog = $this->changelogService->find($slug);

        if (! $changelog) {
            abort(404);
        }

        return Inertia::render(config('changelog.pages.admin.edit'), [
            'changelog' => [
                'slug' => $changelog['slug'],
                'version' => $changelog['version'],
                'title' => $changelog['title'],
                'content' => $changelog['content'],
                'published_at' => $changelog['published_at']->format('Y-m-d\TH:i'),
                'show_modal' => $changelog['show_modal'],
                'is_active' => $changelog['is_active'],
            ],
        ]);
    }

    public function update(Request $request, string $slug): RedirectResponse
    {
        $validated = $request->validate([
            'version' => ['nullable', 'string', 'max:50'],
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'published_at' => ['nullable', 'date'],
            'show_modal' => ['boolean'],
            'is_active' => ['boolean'],
        ]);

        $this->changelogService->update($slug, $validated);

        return redirect()->route(config('changelog.routes.admin.name_prefix').'index')
            ->with('success', 'Changelog wurde erfolgreich aktualisiert.');
    }

    public function destroy(string $slug): RedirectResponse
    {
        $this->changelogService->delete($slug);

        return redirect()->route(config('changelog.routes.admin.name_prefix').'index')
            ->with('success', 'Changelog wurde erfolgreich gelöscht.');
    }
}
