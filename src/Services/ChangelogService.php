<?php

namespace Peppermint\Changelog\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
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
     *
     * Sortiert nach Datum, bei Gleichstand nach Version — beides absteigend.
     *
     * Das zweite Kriterium ist nicht Zierrat: `published_at` ist datumsgenau, also
     * haben zwei Veröffentlichungen desselben Tages denselben Schlüssel. Ohne
     * Tie-Break entschied die Reihenfolge des Datei-Globs, und die ältere Version
     * stand über der neueren. Betroffen war auch {@see getPendingModalChangelog()},
     * das schlicht den ersten Treffer nimmt: Bei zwei Modal-Einträgen eines Tages
     * bekam der Benutzer den älteren zu sehen, und der neuere galt danach als
     * abgehandelt.
     *
     * Eine Uhrzeit im Frontmatter wäre die Alternative gewesen — sie müsste aber
     * jeder Eintrag mitführen, und der Bestand hat sie nicht.
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
            ->sortByDesc(fn (array $changelog): array => [
                $changelog['published_at']->getTimestamp(),
                $this->versionSortKey((string) ($changelog['version'] ?? '')),
            ])
            ->values();
    }

    /**
     * Version als vergleichbarer Schlüssel: `1.104.0` → `00001.00104.00000`.
     *
     * Nötig, weil ein Zeichenketten-Vergleich sonst `1.99.0` über `1.104.0` stellte
     * — Ziffer für Ziffer gelesen ist `9` grösser als `1`. Jeder Zahlenblock wird
     * deshalb auf fünf Stellen aufgefüllt; alles Nicht-Numerische (Vorsilben wie
     * `v`, Zusätze wie `-beta`) trennt die Blöcke und fällt dabei weg.
     */
    protected function versionSortKey(string $version): string
    {
        $teile = preg_split('/\D+/', $version, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        $teile = array_map(
            fn (string $zahl): string => str_pad(substr($zahl, 0, 5), 5, '0', STR_PAD_LEFT),
            array_slice($teile, 0, 4),
        );

        return implode('.', array_pad($teile, 4, '00000'));
    }

    /**
     * Get only active and published changelogs.
     *
     * `values()` ist hier PFLICHT, nicht Kosmetik. `filter()` behaelt die
     * Schluessel, und ein PHP-Array mit Luecken wird von `json_encode` zum
     * **Objekt** statt zum Array.
     *
     * Am 13.09.2026 hat das im Peppermint Manager die gesamte
     * Versionshistorie unsichtbar gemacht: Zwei Eintraege standen auf
     * `is_active: false` und rissen Loecher in die Schluessel. Im Browser war
     * `changelogs.length` damit `undefined`, `undefined > 0` ergab `false`,
     * und die Seite zeigte freundlich „Keine Changelogs" — bei 261
     * vorhandenen. Kein Absturz, keine Meldung: der schlimmere Ausfall.
     */
    public function published(): Collection
    {
        return $this->all()
            ->filter(fn ($changelog) => $changelog['is_active'] && $changelog['is_published'])
            ->values();
    }

    /**
     * Get published changelogs visible to the given user.
     *
     * Filters out changelogs that were published before the user was created
     * to avoid flooding new users with legacy history.
     */
    public function publishedFor(Authenticatable $user): Collection
    {
        $userCreatedAt = $user->created_at ?? null;

        if (! $userCreatedAt) {
            return $this->published();
        }

        $cutoff = $userCreatedAt->startOfDay();

        return $this->published()
            ->filter(fn ($changelog) => $changelog['published_at']->greaterThanOrEqualTo($cutoff))
            // Auch hier: der zweite Filter reisst neue Luecken, selbst wenn
            // `published()` sauber beginnt.
            ->values();
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

        $kandidaten = $this->published()
            ->filter(fn ($changelog) => $changelog['show_modal'])
            ->filter(fn ($changelog) => ! $userCreatedAt || $changelog['published_at']->greaterThanOrEqualTo($userCreatedAt->startOfDay()));

        if ($kandidaten->isEmpty()) {
            return null;
        }

        // EINE Abfrage fuer alle Kandidaten statt einer je Eintrag.
        //
        // Diese Methode laeuft ueber `HandleInertiaRequests` bei JEDEM Aufruf
        // der Anwendung. Mit `isModalDismissedBy()` je Eintrag kostete ein
        // Seitenaufruf so viele zusaetzliche Abfragen, wie es unbestaetigte
        // Modal-Eintraege gibt — und das waechst mit jeder Veroeffentlichung,
        // nicht mit der Nutzung. Gemessen am 17.09.2026 in der Peppermint
        // Verwaltung: vier zusaetzliche `exists`-Abfragen auf jeder Seite,
        // nachdem an einem Tag vier Modal-Eintraege dazugekommen waren.
        //
        // Das Gegenstueck fuer den Lesestand gibt es mit
        // {@see self::getReadSlugsForUser()} schon; fuer das Wegklicken fehlte
        // es.
        $weggeklickt = $this->getDismissedModalSlugsForUser($user, $kandidaten->pluck('slug')->all());

        return $kandidaten
            ->reject(fn ($changelog) => in_array($changelog['slug'], $weggeklickt, true))
            ->first();
    }

    /**
     * Die Eintraege, deren Modal dieser Nutzer weggeklickt hat — in einer Abfrage.
     *
     * @param  list<string>|null  $slugs  Auf diese Eintraege einschraenken; null fragt alle.
     * @return list<string>
     */
    public function getDismissedModalSlugsForUser(Authenticatable $user, ?array $slugs = null): array
    {
        return ChangelogRead::where('user_id', $user->getAuthIdentifier())
            ->where('modal_dismissed', true)
            ->when($slugs !== null, fn ($query) => $query->whereIn('changelog_slug', $slugs))
            ->pluck('changelog_slug')
            ->all();
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
        $slug = pathinfo($filePath, PATHINFO_FILENAME);

        // Eine einzelne fehlerhafte Changelog-Datei (z.B. kaputte YAML-Frontmatter)
        // darf NIE die ganze App lahmlegen — getAll() wird bei jedem Inertia-Render
        // gelesen. Defekte Datei → überspringen + loggen statt Exception nach oben.
        try {
            $content = File::get($filePath);

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
        } catch (\Throwable $e) {
            Log::warning('[changelog] Datei übersprungen (Parse-Fehler): '.basename($filePath).' — '.$e->getMessage());

            return null;
        }
    }
}
