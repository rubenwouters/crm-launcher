<?php

namespace Rubenwouters\CrmLauncher;

use Illuminate\Support\ServiceProvider;
use Illuminate\Foundation\AliasLoader;

class CrmLauncherServiceProvider extends ServiceProvider
{
    /**
     * @var array
     */
    protected $providers = [
        'Laravel\Socialite\SocialiteServiceProvider',
        'Collective\Html\HtmlServiceProvider',
    ];

    /**
     * @var array
     */
    protected $aliases = [
        'Socialite'	=> 'Laravel\Socialite\Facades\Socialite',
        'Form' => 'Collective\Html\FormFacade',
        'Html' => 'Collective\Html\HtmlFacade',
        'Input' => 'Illuminate\Support\Facades\Input',
    ];

    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        if (!$this->app->routesAreCached()) {
            require __DIR__ . '/routes.php';
        }

        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'crm-launcher');
        $this->loadTranslationsFrom(__DIR__ . '/../lang', 'crm-launcher');
        $this->publish();
        $this->registerCommands();
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->registerServiceProviders();
        $this->registerAliases();
        $this->registerMiddleware();
        $this->registerCrons();
    }

    /**
     * Registers service providers
     * @return void
     */
    private function registerServiceProviders()
    {
        foreach ($this->providers as $provider) {
            $this->app->register($provider);
        }
    }

    /**
     * Registers aliases
     * @return void
     */
    private function registerAliases()
    {
        $loader = AliasLoader::getInstance();
        foreach ($this->aliases as $key => $alias) {
            $loader->alias($key, $alias);
        }
    }

    /**
     * Registers middleware
     * @return void
     */
    private function registerMiddleware()
    {
        $this->app['router']->middleware('CanViewCRM', 'Rubenwouters\CrmLauncher\Middleware\CanViewCRM');
    }

    private function registerCommands()
    {
        $this->commands([
            'Rubenwouters\CrmLauncher\Commands\MigrateDatabase',
            'Rubenwouters\CrmLauncher\Commands\GrantAccess',
            'Rubenwouters\CrmLauncher\Commands\UpdateCases',
            'Rubenwouters\CrmLauncher\Commands\UpdatePublishmentStats',
            'Rubenwouters\CrmLauncher\Commands\UpdateDashboardStats',
        ]);
    }

    /**
     * Publish views, assets & config
     * @return void
     */
    private function publish()
    {
        $this->publishes([
            __DIR__ . '/../resources/views' => resource_path('views/vendor/crm-launcher'),
        ]);

        $this->publishes([
            __DIR__ . '/../public' => base_path('public/crm-launcher/'),
        ]);

        $this->publishes([
            __DIR__ . '/../config/crm-launcher.php' => config_path('crm-launcher.php'),
        ]);
    }

    /**
     * Register cron jobs
     * @return void
     */
    private function registerCrons()
    {
        $this->app->singleton('rubenwouters.crm-launcher.src.console.kernel', function($app) {
            $dispatcher = $app->make(\Illuminate\Contracts\Events\Dispatcher::class);

            return new \Rubenwouters\CrmLauncher\Console\Kernel($app, $dispatcher);
        });

        $this->app->make('rubenwouters.crm-launcher.src.console.kernel');
    }
}
