<?php
namespace LaravelJobTrackable\Providers;

use LaravelJobTrackable\TrackedJobController;

use Illuminate\Support\ServiceProvider;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Queue\QueueManager;
use Illuminate\Support\Facades\Redis;

class LaravelJobTrackableServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/job-trackable.php', 'job-trackable');

        $this->publishes([
            __DIR__ . '/../../config/' => config_path(),
        ], 'config');

        $this->app->singleton(TrackedJobController::class, function ($app) {
            return new TrackedJobController(
                Redis::connection(),
                config('job-trackable.prefix'),
                config('job-trackable.expire')
            );
        });
    }

    public function boot()
    {
        $controller = app(TrackedJobController::class);

        // Add Event listeners
        app(QueueManager::class)->before(function (JobProcessing $event) use ($controller) {
            $controller->before($event);
        });
        app(QueueManager::class)->after(function (JobProcessed $event) use ($controller) {
            $controller->after($event);
        });
        app(QueueManager::class)->failing(function (JobFailed $event) use ($controller) {
            $controller->failing($event);
        });
        app(QueueManager::class)->exceptionOccurred(function (JobExceptionOccurred $event) use ($controller) {
            $controller->exceptionOccurred($event);
        });
    }
}
