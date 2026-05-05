<?php

namespace Peppermint\Changelog\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Peppermint\Changelog\Models\ChangelogRead;
use Symfony\Component\Yaml\Yaml;

class ChangelogService
{
    protected string $changelogPath;

    public function __construct()
    {
        $this->changelogPath = config('changelog.path', base_path('changelogs'));
    }

    /**
     * Get all changelogs from the filesystem.
     */
    public function all(): Collection
    {
        if (! File::isDirectory($this->changelogPath)) {
            return collect();
        }

        $files = File::glob($this->changelogPath.'/*.md');

        return collect($files)
            ->map(fn ($file) => $this->parseFile($file))
            ->filter()
            ->sortByDesc('published_at')
            ->values();
    }

    /**
     * Get only active and published changelogs.
     */
    public function published(): Collection
    {
        return $this->all()
            ->filter(fn ($changelog) => $changelog['is_active'] && $changelog['is_published']);
    }

    /**
     * Get a single changelog by slug.
     */
    public function find(string $slug): ?array
    {
        $file = $this->changelogPath.'/'.$slug.'.md';

        if (! File::exists($file)) {
            return null;
        }

        return $this->parseFile($file);
    }

    /**
     * Get the latest changelog with modal enabled that user hasn't dismissed.
     * Only shows changelogs published after the user was created.
     */
    public function getPendingModalChangelog(Authenticatable $user): ?array
    {
        $userCreatedAt = $user->created_at;

        return $this->published()
            ->filter(fn ($changelog) => $changelog['show_modal'])
            ->filter(fn ($changelog) => ! $userCreatedAt || $changelog['published_at']->greaterThanOrEqualTo($userCreatedAt->startOfDay()))
            ->filter(fn ($changelog) => ! $this->isModalDismissedBy($changelog['slug'], $user))
            ->first();
    }

    /**
     * Check if user has read a changelog.
     */
    public function isReadBy(string $slug, Authenticatable $user): bool
    {
        return ChangelogRead::where('changelog_slug', $slug)
            ->where('user_id', $user->getAuthIdentifier())
            ->whereNotNull('read_at')
            ->exists();
    }

    /**
     * Get all read changelog slugs for a user (single query).
     *
     * Useful when checking read-state for many changelogs at once to avoid
     * N+1 queries via repeated isReadBy() calls.
     *
     * @return array<int, string>
     */
    public function getReadSlugsForUser(Authenticatable $user): array
    {
        return ChangelogRead::where('user_id', $user->getAuthIdentifier())
            ->whereNotNull('read_at')
            ->pluck('changelog_slug')
            ->all();
    }

    /**
     * Check if user has dismissed the modal for a changelog.
     */
    public function isModalDismissedBy(string $slug, Authenticatable $user): bool
    {
        return ChangelogRead::where('changelog_slug', $slug)
            ->where('user_id', $user->getAuthIdentifier())
            ->where('modal_dismissed', true)
            ->exists();
    }

    /**
     * Mark a changelog as read by user.
     */
    public function markAsReadBy(string $slug, Authenticatable $user): void
    {
        ChangelogRead::updateOrCreate(
            ['changelog_slug' => $slug, 'user_id' => $user->getAuthIdentifier()],
            ['read_at' => now()]
        );
    }

    /**
     * Dismiss the modal for a changelog.
     */
    public function dismissModalFor(string $slug, Authenticatable $user): void
    {
        ChangelogRead::updateOrCreate(
            ['changelog_slug' => $slug, 'user_id' => $user->getAuthIdentifier()],
            ['modal_dismissed' => true, 'read_at' => now()]
        );
    }

    /**
     * Create a new changelog file.
     */
    public function create(array $data): string
    {
        $slug = $data['slug'] ?? Str::slug($data['version'] ?? $data['title']);
        $filePath = $this->changelogPath.'/'.$slug.'.md';

        if (! File::isDirectory($this->changelogPath)) {
            File::makeDirectory($this->changelogPath, 0755, true);
        }

        $content = $this->generateFileContent($data);
        File::put($filePath, $content);

        return $slug;
    }

    /**
     * Update an existing changelog file.
     */
    public function update(string $slug, array $data): bool
    {
        $filePath = $this->changelogPath.'/'.$slug.'.md';

        if (! File::exists($filePath)) {
            return false;
        }

        $content = $this->generateFileContent($data);
        File::put($filePath, $content);

        return true;
    }

    /**
     * Delete a changelog file.
     */
    public function delete(string $slug): bool
    {
        $filePath = $this->changelogPath.'/'.$slug.'.md';

        if (! File::exists($filePath)) {
            return false;
        }

        ChangelogRead::where('changelog_slug', $slug)->delete();

        return File::delete($filePath);
    }

    /**
     * Generate markdown file content with YAML frontmatter.
     */
    protected function generateFileContent(array $data): string
    {
        $frontmatter = [
            'version' => $data['version'] ?? null,
            'title' => $data['title'],
            'published_at' => $data['published_at'] ?? now()->format('Y-m-d'),
            'show_modal' => $data['show_modal'] ?? false,
            'is_active' => $data['is_active'] ?? true,
        ];

        $yaml = Yaml::dump($frontmatter);
        $content = $data['content'] ?? '';

        return "---\n{$yaml}---\n\n{$content}";
    }

    /**
     * Parse a markdown file with YAML frontmatter.
     */
    protected function parseFile(string $filePath): ?array
    {
        $content = File::get($filePath);
        $slug = pathinfo($filePath, PATHINFO_FILENAME);

        if (! preg_match('/^---\s*\n(.*?)\n---\s*\n(.*)$/s', $content, $matches)) {
            return [
                'slug' => $slug,
                'version' => $slug,
                'title' => $slug,
                'content' => $content,
                'html_content' => Str::markdown($content),
                'is_markdown' => true,
                'published_at' => now(),
                'show_modal' => false,
                'is_active' => true,
                'is_published' => true,
            ];
        }

        $frontmatter = Yaml::parse($matches[1]);
        $markdownContent = trim($matches[2]);

        $publishedAt = isset($frontmatter['published_at'])
            ? \Carbon\Carbon::parse($frontmatter['published_at'])
            : now();

        return [
            'slug' => $slug,
            'version' => $frontmatter['version'] ?? $slug,
            'title' => $frontmatter['title'] ?? $slug,
            'content' => $markdownContent,
            'html_content' => Str::markdown($markdownContent),
            'is_markdown' => true,
            'published_at' => $publishedAt,
            'show_modal' => $frontmatter['show_modal'] ?? false,
            'is_active' => $frontmatter['is_active'] ?? true,
            'is_published' => $publishedAt->isPast() || $publishedAt->isToday(),
        ];
    }
}
