<?php

declare(strict_types=1);

namespace Cortex\Tags\Providers;

use Illuminate\Routing\Router;
use Rinvex\Tags\Contracts\TagContract;
use Illuminate\Support\ServiceProvider;
use Cortex\Tags\Console\Commands\SeedCommand;
use Cortex\Tags\Console\Commands\InstallCommand;
use Cortex\Tags\Console\Commands\MigrateCommand;
use Cortex\Tags\Console\Commands\PublishCommand;

class TagsServiceProvider extends ServiceProvider
{
    /**
     * The commands to be registered.
     *
     * @var array
     */
    protected $commands = [
        MigrateCommand::class => 'command.cortex.tags.migrate',
        PublishCommand::class => 'command.cortex.tags.publish',
        InstallCommand::class => 'command.cortex.tags.install',
        SeedCommand::class => 'command.cortex.tags.seed',
    ];

    /**
     * Register any application services.
     *
     * This service provider is a great spot to register your various container
     * bindings with the application. As you can see, we are registering our
     * "Registrar" implementation here. You can add your own bindings too!
     *
     * @return void
     */
    public function register()
    {
        // Register console commands
        ! $this->app->runningInConsole() || $this->registerCommands();
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot(Router $router)
    {
        // Bind route models and constrains
        $router->pattern('tag', '[a-z0-9-]+');
        $router->model('tag', TagContract::class);

        // Load resources
        require __DIR__.'/../../routes/breadcrumbs.php';
        $this->loadRoutesFrom(__DIR__.'/../../routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../../resources/views', 'cortex/tags');
        $this->loadTranslationsFrom(__DIR__.'/../../resources/lang', 'cortex/tags');
        $this->app->afterResolving('blade.compiler', function () {
            require __DIR__.'/../../routes/menus.php';
        });

        // Publish Resources
        ! $this->app->runningInConsole() || $this->publishResources();
    }

    /**
     * Publish resources.
     *
     * @return void
     */
    protected function publishResources()
    {
        $this->publishes([realpath(__DIR__.'/../../resources/lang') => resource_path('lang/vendor/cortex/tags')], 'cortex-tags-lang');
        $this->publishes([realpath(__DIR__.'/../../resources/views') => resource_path('views/vendor/cortex/tags')], 'cortex-tags-views');
    }

    /**
     * Register console commands.
     *
     * @return void
     */
    protected function registerCommands()
    {
        // Register artisan commands
        foreach ($this->commands as $key => $value) {
            $this->app->singleton($value, function ($app) use ($key) {
                return new $key();
            });
        }

        $this->commands(array_values($this->commands));
    }
}
