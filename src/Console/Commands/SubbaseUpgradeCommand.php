<?php

declare(strict_types=1);

namespace Nafiswatsiq\Subbase\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class SubbaseUpgradeCommand extends Command
{
    protected $signature = 'subbase:upgrade
                            {--force : Overwrite existing config and views}
                            {--config : Only republish config file}
                            {--views : Only republish views}
                            {--migrations : Only publish new migrations}';

    protected $description = 'Upgrade Subbase plugin assets (config, views, migrations)';

    public function handle(): int
    {
        $this->info('Upgrading Subbase...');

        $onlyConfig = $this->option('config');
        $onlyViews = $this->option('views');
        $onlyMigrations = $this->option('migrations');
        $force = $this->option('force');

        $runAll = ! $onlyConfig && ! $onlyViews && ! $onlyMigrations;

        if ($runAll || $onlyConfig) {
            $this->publishConfig($force);
        }

        if ($runAll || $onlyViews) {
            $this->publishViews($force);
        }

        if ($runAll || $onlyMigrations) {
            $this->publishMigrations();
        }

        $this->newLine();
        $this->info('Subbase upgrade completed.');
        $this->line('Run <comment>php artisan migrate</comment> to apply any new migrations.');

        return self::SUCCESS;
    }

    protected function publishConfig(bool $force): void
    {
        $this->line('');
        $this->line('Publishing config...');

        $params = ['--tag' => 'subbase-config'];

        if ($force) {
            $params['--force'] = true;
        }

        $this->call('vendor:publish', $params);
    }

    protected function publishViews(bool $force): void
    {
        $this->line('');
        $this->line('Publishing views...');

        $params = ['--tag' => 'subbase-views'];

        if ($force) {
            $params['--force'] = true;
        }

        $this->call('vendor:publish', $params);
    }

    protected function publishMigrations(): void
    {
        $this->line('');
        $this->line('Publishing new migrations...');

        $migrations = [
            'add_prices_to_plans_table.php',
            'add_featured_to_plans_table.php',
            'create_discounts_table.php',
            'upgrade_billing_decimal_columns.php',
            'create_license_catalog_and_providers_table.php',
            'create_license_orders_and_keys_table.php',
        ];

        $published = 0;

        foreach ($migrations as $migration) {
            $sourceMigration = dirname(__DIR__, 3).'/database/migrations/'.$migration;

            if (! File::exists($sourceMigration)) {
                $this->warn("  Skipping missing migration: {$migration}");

                continue;
            }

            if (! empty(File::glob(database_path('migrations/*_'.$migration)))) {
                $this->line("  <comment>Already published:</comment> {$migration}");

                continue;
            }

            $timestamp = now();
            $destinationMigration = database_path('migrations/'.$timestamp->format('Y_m_d_His').'_'.$migration);

            while (File::exists($destinationMigration)) {
                $timestamp = $timestamp->addSecond();
                $destinationMigration = database_path('migrations/'.$timestamp->format('Y_m_d_His').'_'.$migration);
            }

            File::copy($sourceMigration, $destinationMigration);
            $this->info("  Published: ".basename($destinationMigration));
            $published++;
        }

        if ($published === 0) {
            $this->line('  <comment>No new migrations to publish.</comment>');
        }
    }
}
