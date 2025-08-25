<?php

namespace Mllexx\IFS;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Mllexx\IFS\Commands\IFSCommand;
use Illuminate\Support\Facades\Route;
use Mllexx\IFS\Http\Controllers\TestController;

class IFSServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('laravel-ifs')
            ->hasConfigFile()
            ->hasViews()
            //->hasMigration()
            ->hasCommands([
                IFSCommand::class,
            ]);
    }

    public function packageRegistered()
    {

        Route::get('ifs-test', [TestController::class, 'index']);
        /*
        $this->app->bind(IFS::class, function () {
            return new IFS();
        });
        */
    }
}
