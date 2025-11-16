<?php

namespace Badrshs\DynamicImageComposer\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Artisan;

class InstallCommand extends Command
{
    protected $signature = 'dynamic-image-composer:install
                            {--force : Overwrite existing files}
                            {--with-fonts : Include default fonts}';

    protected $description = 'Install the Dynamic Image Composer package';

    public function handle()
    {
        $this->info('Installing Dynamic Image Composer...');
        $this->newLine();

        // 1. Publish config
        $this->comment('Publishing configuration...');
        $this->callSilent('vendor:publish', [
            '--tag' => 'dynamic-image-composer-config',
            '--force' => $this->option('force'),
        ]);
        $this->info('✓ Configuration published');

        // 2. Publish migrations
        $this->comment('Publishing migrations...');
        $this->callSilent('vendor:publish', [
            '--tag' => 'dynamic-image-composer-migrations',
            '--force' => $this->option('force'),
        ]);
        $this->info('✓ Migrations published');

        // 3. Publish views
        $this->comment('Publishing views...');
        $this->callSilent('vendor:publish', [
            '--tag' => 'dynamic-image-composer-views',
            '--force' => $this->option('force'),
        ]);
        $this->info('✓ Views published');

        // 4. Publish fonts
        if ($this->option('with-fonts')) {
            $this->comment('Publishing default fonts...');
            $this->callSilent('vendor:publish', [
                '--tag' => 'dynamic-image-composer-fonts',
                '--force' => $this->option('force'),
            ]);
            $this->info('✓ Default fonts published');
        }

        // 5. Create storage directories
        $this->comment('Creating storage directories...');
        $this->createDirectories();
        $this->info('✓ Directories created');

        // 6. Run migrations
        if ($this->confirm('Do you want to run migrations now?', true)) {
            $this->comment('Running migrations...');
            $this->call('migrate');
            $this->info('✓ Migrations completed');
        }

        // 7. Create storage link
        if (!File::exists(public_path('storage'))) {
            $this->comment('Creating storage link...');
            $this->call('storage:link');
            $this->info('✓ Storage linked');
        }

        $this->newLine();
        $this->info('🎉 Dynamic Image Composer installed successfully!');
        $this->newLine();

        $this->line('Next steps:');
        $this->line('1. Configure fonts and colors in config/dynamic-image-composer.php');
        $this->line('2. Add your custom fonts to storage/app/public/fonts/');
        $this->line('3. Create your first template via Filament or database');
        $this->line('4. Access the designer at /image-template/{id}/designer');
        $this->newLine();

        if (!$this->option('with-fonts')) {
            $this->warn('⚠ Default fonts were not installed. Add --with-fonts to include them.');
            $this->warn('   Or manually add fonts to: storage/app/public/fonts/');
        }

        return self::SUCCESS;
    }

    protected function createDirectories(): void
    {
        $disk = config('dynamic-image-composer.disk', 'public');
        $directories = [
            config('dynamic-image-composer.templates_directory', 'image-templates'),
            config('dynamic-image-composer.elements_directory', 'image-elements'),
            config('dynamic-image-composer.generated_directory', 'generated-images'),
            config('dynamic-image-composer.fonts_directory', 'fonts'),
        ];

        foreach ($directories as $directory) {
            $path = storage_path("app/{$disk}/{$directory}");
            if (!File::exists($path)) {
                File::makeDirectory($path, 0755, true);
            }
        }
    }
}
