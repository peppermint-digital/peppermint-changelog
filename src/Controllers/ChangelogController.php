<?php

namespace Peppermint\Changelog\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inertia\Response;
use Peppermint\Changelog\Services\ChangelogService;

class ChangelogController extends Controller
{
    public function __construct(
        protected ChangelogService $changelogService
    ) {}

    public function index(): Response
    {
        $user = auth()->user();

        $changelogs = $this->changelogService->publishedFor($user)
            ->map(fn ($changelog) => [
                'slug' => $changelog['slug'],
                'version' => $changelog['version'],
                'title' => $changelog['title'],
                'html_content' => $changelog['html_content'],
                'published_at' => $changelog['published_at']->format('d.m.Y'),
                'is_read' => $this->changelogService->isReadBy($changelog['slug'], $user),
            ]);

        return Inertia::render(config('changelog.pages.public.index'), [
            'changelogs' => $changelogs,
        ]);
    }

    public function show(string $slug): Response|RedirectResponse
    {
        $user = auth()->user();
        $changelog = $this->changelogService->find($slug);

        if (! $changelog || ! $changelog['is_active'] || ! $changelog['is_published']) {
            return redirect()->route(config('changelog.routes.public.name_prefix').'index')
                ->with('error', 'Dieser Changelog ist nicht verfügbar.');
        }

        $this->changelogService->markAsReadBy($slug, $user);

        return Inertia::render(config('changelog.pages.public.show'), [
            'changelog' => [
                'slug' => $changelog['slug'],
                'version' => $changelog['version'],
                'title' => $changelog['title'],
                'content' => $changelog['content'],
                'html_content' => $changelog['html_content'],
                'is_markdown' => $changelog['is_markdown'],
                'published_at' => $changelog['published_at']->format('d.m.Y'),
            ],
        ]);
    }

    public function markAsRead(string $slug): RedirectResponse
    {
        $this->changelogService->markAsReadBy($slug, auth()->user());

        return redirect()->back();
    }

    public function dismissModal(string $slug): RedirectResponse
    {
        $this->changelogService->dismissModalFor($slug, auth()->user());

        return redirect()->back();
    }
}
