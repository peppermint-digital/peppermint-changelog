<?php

namespace Peppermint\Changelog\Console;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class InstallCommand extends Command
{
    protected $signature = 'changelog:install
        {--frontend= : Frontend stack (react|vue). Defaults to interactive choice.}
        {--with-npm : Also run npm install for the markdown-editor dependency.}
        {--force : Overwrite existing published files.}';

    protected $description = 'Install the peppermint/changelog package: publish config, publish frontend stubs, run migrations, and surface the required npm dependency.';

    public function handle(): int
    {
        $this->components->info('peppermint/changelog — install');

        $this->publishConfig();
        $frontend = $this->resolveFrontend();
        if ($frontend !== null) {
            $this->publishStubs($frontend);
        }
        $this->runMigrations();
        $this->ensureChangelogsDirectory();
        $this->surfaceNpmDependency();

        $this->components->success('Setup abgeschlossen. Viel Spass mit deinen Changelogs.');

        return self::SUCCESS;
    }

    private function publishConfig(): void
    {
        $args = ['--tag' => 'changelog-config'];
        if ($this->option('force')) {
            $args['--force'] = true;
        }

        $this->call('vendor:publish', $args);
    }

    private function resolveFrontend(): ?string
    {
        $frontend = $this->option('frontend');

        if ($frontend === null) {
            $frontend = $this->choice(
                'Welche Frontend-Stubs sollen publiziert werden?',
                ['react', 'vue', 'none'],
                'react',
            );
        }

        return in_array($frontend, ['react', 'vue'], true) ? $frontend : null;
    }

    private function publishStubs(string $frontend): void
    {
        $tag = $frontend === 'react' ? 'changelog-react' : 'changelog-vue';
        $args = ['--tag' => $tag];
        if ($this->option('force')) {
            $args['--force'] = true;
        }

        $this->call('vendor:publish', $args);
    }

    private function runMigrations(): void
    {
        $this->components->task('Migrationen ausfuehren', function (): bool {
            $this->callSilently('migrate', ['--force' => true]);

            return true;
        });
    }

    private function ensureChangelogsDirectory(): void
    {
        $path = base_path('changelogs');

        if (is_dir($path)) {
            return;
        }

        $this->components->task('changelogs/ Verzeichnis anlegen', fn () => mkdir($path, 0755, true));
    }

    private function surfaceNpmDependency(): void
    {
        $command = ['npm', 'install', 'github:peppermint-digital/markdown-editor'];

        if ($this->option('with-npm')) {
            $this->components->task('npm install @peppermint-digital/markdown-editor', function () use ($command): bool {
                $process = new Process($command, base_path());
                $process->setTimeout(180);
                $process->run(fn ($_, $buffer) => $this->output->write($buffer));

                return $process->isSuccessful();
            });

            return;
        }

        $this->newLine();
        $this->components->warn('NPM Pflicht-Dependency:');
        $this->components->bulletList([
            'Das changelog-System benoetigt @peppermint-digital/markdown-editor fuer die Create-/Edit-Pages.',
            'Installation: npm install github:peppermint-digital/markdown-editor',
            'Wiederholungslauf mit --with-npm fuehrt den npm install automatisch aus.',
        ]);
    }
}
