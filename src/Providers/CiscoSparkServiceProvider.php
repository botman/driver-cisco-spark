<?php

namespace BotMan\Drivers\CiscoSpark\Providers;

use Illuminate\Support\ServiceProvider;
use BotMan\BotMan\Drivers\DriverManager;
use BotMan\Drivers\CiscoSpark\CiscoSparkDriver;
use BotMan\Studio\Providers\StudioServiceProvider;

class CiscoSparkServiceProvider extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
        if (! $this->isRunningInBotManStudio()) {
            $this->loadDrivers();

            $this->publishes([
                __DIR__.'/../../stubs/cisco-spark.php' => config_path('botman/cisco-spark.php'),
            ]);

            $this->mergeConfigFrom(__DIR__.'/../../stubs/cisco-spark.php', 'botman.cisco-spark');
        }
    }

    /**
     * Load BotMan drivers.
     */
    protected function loadDrivers()
    {
        DriverManager::loadDriver(CiscoSparkDriver::class);
    }

    /**
     * @return bool
     */
    protected function isRunningInBotManStudio()
    {
        return class_exists(StudioServiceProvider::class);
    }
}
