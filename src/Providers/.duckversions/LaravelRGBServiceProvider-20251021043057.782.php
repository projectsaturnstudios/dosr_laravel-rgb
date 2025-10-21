<?php

namespace DeptOfScrapyardRobotics\LaravelRGB\Providers;

use RDMIntegrations\Customers\Projectors\SoldToCustomerProjector;
use Spatie\EventSourcing\Facades\Projectionist;
use RDMIntegrations\Customers\Projectors\BusinessAssociateProjector;
use RDMIntegrations\Customers\Projectors\SuperBusinessAssociateProjector;
use ProjectSaturnStudios\LaravelDesignPatterns\Providers\BaseServiceProvider;

class LaravelRGBServiceProvider extends BaseServiceProvider
{
    protected string $short_name = 'customers';
    protected array $config = [
        'rgb-lighting' => __DIR__ . '/../../config/rgb-lighting.php',
    ];

    protected array $publishable_config = [
        ['key' => 'rgb-lighting', 'file_path' => __DIR__ . '/../../config/rgb-lighting.php', 'groups' => ['rgb-lighting']],
    ];

    protected array $routes = [];

    protected array $commands = [];

    protected array $bootables = [];

    protected array $migrations = [
        'customer_snapshots',
        'customer_stored_events',
        'super_business_associates',
        'business_associates',
        'sold_to_customers',
        'customers_numeric_address',
        'motiva_customers_numeric_address',
    ];

    protected array $projections = [
        SuperBusinessAssociateProjector::class,
        BusinessAssociateProjector::class,
        SoldToCustomerProjector::class,
    ];

    public function register(): void
    {
        parent::register();
        $this->publishMigrations();
    }

    protected function mainBooted(): void
    {
        Projectionist::addProjectors($this->projections);
    }

    public function publishMigrations() : void
    {
        foreach ($this->migrations as $module_table_name) {
            $modules = \RDMIntegrations\Customers\Providers\collect(scandir(base_path('database/migrations')))->filter(function($item) use($module_table_name) {
                return str_contains($item, "create_{$module_table_name}_table");
            })->toArray();

            if(empty($modules))
            {
                $timestamp = date('Y_m_d_His', time());
                $stub = __DIR__."/../../database/migrations/create_{$module_table_name}_table.php";
                $target = $this->app->databasePath().'/migrations/'.$timestamp."_create_{$module_table_name}_table.php";

                $this->publishes([$stub => $target], "rdm.{$this->short_name}.migrations.all");
                $this->publishes([$stub => $target], "rdm.{$this->short_name}.migrations.{$module_table_name}");
            }
        }
    }
}
