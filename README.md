# peppermint/changelog

File-based Changelog-System for Laravel with per-user read tracking, modal notifications, and publishable frontend stubs for React and Vue (both with shadcn/ui).

## Features

- **Markdown-Dateien mit YAML Frontmatter** — Changelogs werden als `.md`-Dateien gespeichert, nicht in der Datenbank
- **Per-User Read-Tracking** — Trackt welche User welchen Changelog gelesen haben
- **Modal-Benachrichtigungen** — Zeigt neue Updates automatisch als Modal an
- **Admin CRUD** — Changelogs erstellen, bearbeiten, loeschen mit Leser-Statistiken
- **Konfigurierbar** — Routes, Middleware, Inertia Page-Pfade anpassbar
- **Multi-Stack** — Publishable Frontend-Stubs fuer React + shadcn/ui und Vue + Reka UI

## Installation

### 1. Composer

**Als lokales Paket (Entwicklung):**

In der `composer.json` des Projekts ein Path-Repository hinzufuegen:

```json
{
    "repositories": [
        {
            "type": "path",
            "url": "/pfad/zu/packages/peppermint-changelog"
        }
    ]
}
```

Dann installieren:

```bash
composer require "peppermint/changelog:dev-master"
```

### 2. Config publishen

```bash
php artisan vendor:publish --tag=changelog-config
```

Erstellt `config/changelog.php` mit Einstellungen fuer Pfade, Middleware und Inertia Page-Pfade.

### 3. Frontend-Stubs publishen

**React + shadcn/ui:**
```bash
php artisan vendor:publish --tag=changelog-react
```

**Vue + Reka UI (shadcn/vue):**
```bash
php artisan vendor:publish --tag=changelog-vue
```

Dies kopiert Seiten und Komponenten in dein `resources/js/` Verzeichnis.

### 4. Migration ausfuehren

```bash
php artisan migrate
```

Erstellt die `changelog_reads` Tabelle.

### 5. Changelogs-Verzeichnis erstellen

```bash
mkdir changelogs
```

## Konfiguration

### config/changelog.php

```php
return [
    // Pfad zu den Markdown-Dateien
    'path' => base_path('changelogs'),

    // User Model
    'user_model' => \App\Models\User::class,

    // Route-Konfiguration
    'routes' => [
        'enabled' => true,
        'public' => [
            'prefix' => 'changelogs',
            'middleware' => ['web', 'auth', 'verified'],
            'name_prefix' => 'changelogs.',
        ],
        'admin' => [
            'prefix' => 'admin/changelogs',
            'middleware' => ['web', 'auth', 'verified', 'admin'],
            'name_prefix' => 'admin.changelogs.',
        ],
    ],

    // Inertia Page-Pfade (an deine Dateistruktur anpassen)
    'pages' => [
        'public' => [
            'index' => 'changelogs/index',
            'show' => 'changelogs/show',
        ],
        'admin' => [
            'index' => 'admin/changelogs/index',
            'create' => 'admin/changelogs/create',
            'show' => 'admin/changelogs/show',
            'edit' => 'admin/changelogs/edit',
        ],
    ],
];
```

## Changelog-Dateien

Changelogs sind Markdown-Dateien mit YAML Frontmatter im `changelogs/` Verzeichnis:

```
changelogs/
  v1-0-0.md
  v1-1-0.md
  v2-0-0.md
```

### Dateiformat

```markdown
---
version: "1.2.0"
title: "Neue Gutscheinfunktion"
published_at: 2026-03-19
show_modal: true
is_active: true
---

## Neue Features

- Feature 1
- Feature 2

## Verbesserungen

- Verbesserung 1
```

### Frontmatter-Felder

| Feld | Typ | Beschreibung |
|------|-----|-------------|
| `version` | string | Versionsnummer (z.B. "1.2.0") |
| `title` | string | Titel des Changelogs |
| `published_at` | date | Veroeffentlichungsdatum (Zukunft = geplant) |
| `show_modal` | boolean | Als Modal-Popup anzeigen |
| `is_active` | boolean | Changelog sichtbar |

## Integration

### Modal-Benachrichtigungen (HandleInertiaRequests)

In deiner `HandleInertiaRequests` Middleware das `pendingChangelog` Prop teilen:

```php
use Peppermint\Changelog\Services\ChangelogService;

public function share(Request $request): array
{
    return [
        ...parent::share($request),
        'pendingChangelog' => $this->getPendingChangelog($request),
    ];
}

private function getPendingChangelog(Request $request): ?array
{
    $user = $request->user();
    if (!$user) return null;

    $changelog = app(ChangelogService::class)->getPendingModalChangelog($user);
    if ($changelog && isset($changelog['published_at'])) {
        $changelog['published_at'] = $changelog['published_at']->format('d.m.Y');
    }
    return $changelog;
}
```

### Layout-Integration (React)

```tsx
import ChangelogModal from '@/components/changelog-modal';
import { usePage } from '@inertiajs/react';

export default function AppLayout({ children }) {
    const { pendingChangelog } = usePage().props;

    return (
        <>
            {children}
            <ChangelogModal changelog={pendingChangelog} />
        </>
    );
}
```

### Layout-Integration (Vue)

```vue
<script setup>
import ChangelogModal from '@/components/ChangelogModal.vue'
import { usePage } from '@inertiajs/vue3'

const page = usePage()
</script>

<template>
    <slot />
    <ChangelogModal :changelog="page.props.pendingChangelog" />
</template>
```

## Routes

### Public (authentifizierte User)

| Method | URL | Beschreibung |
|--------|-----|-------------|
| GET | `/changelogs` | Alle Changelogs anzeigen |
| GET | `/changelogs/{slug}` | Einzelnen Changelog anzeigen |
| POST | `/changelogs/{slug}/read` | Als gelesen markieren |
| POST | `/changelogs/{slug}/dismiss-modal` | Modal schliessen |

### Admin

| Method | URL | Beschreibung |
|--------|-----|-------------|
| GET | `/admin/changelogs` | Alle Changelogs verwalten |
| GET | `/admin/changelogs/create` | Erstellen-Formular |
| POST | `/admin/changelogs` | Changelog speichern |
| GET | `/admin/changelogs/{slug}` | Detail mit Leser-Statistik |
| GET | `/admin/changelogs/{slug}/edit` | Bearbeiten-Formular |
| PUT | `/admin/changelogs/{slug}` | Changelog aktualisieren |
| DELETE | `/admin/changelogs/{slug}` | Changelog loeschen |

## ChangelogService API

```php
use Peppermint\Changelog\Services\ChangelogService;

$service = app(ChangelogService::class);

// Alle Changelogs
$service->all();

// Nur veroeffentlichte
$service->published();

// Einzelnen finden
$service->find('v1-0-0');

// Pending Modal fuer User
$service->getPendingModalChangelog($user);

// Read-Tracking
$service->isReadBy('v1-0-0', $user);
$service->markAsReadBy('v1-0-0', $user);

// Modal-Tracking
$service->isModalDismissedBy('v1-0-0', $user);
$service->dismissModalFor('v1-0-0', $user);

// CRUD
$service->create(['title' => '...', 'content' => '...']);
$service->update('v1-0-0', ['title' => '...']);
$service->delete('v1-0-0');
```

## Datenbank

Das Paket erstellt eine einzige Tabelle:

### changelog_reads

| Spalte | Typ | Beschreibung |
|--------|-----|-------------|
| id | bigint | Primary Key |
| user_id | FK | Welcher User |
| changelog_slug | string | Welcher Changelog (Dateiname) |
| read_at | datetime | Wann gelesen |
| modal_dismissed | boolean | Modal geschlossen |

## Anforderungen

- PHP >= 8.2
- Laravel 11 oder 12
- Inertia.js v2
- symfony/yaml (wird automatisch installiert)

## Lizenz

MIT
