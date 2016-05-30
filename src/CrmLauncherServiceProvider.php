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
        include __DIR__.'/routes.php';


        $this->loadViewsFrom(__DIR__.'/../resources/views', 'crm-launcher');
        $this->loadTranslationsFrom(__DIR__.'/../lang', 'crm-launcher');
        $this->registerCommands();

        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/crm-launcher'),
        ]);

        $this->publishes([
            __DIR__.'/../config/crm-launcher.php' => config_path('crm-launcher.php'),
        ]);
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
        ]);
    }

}
