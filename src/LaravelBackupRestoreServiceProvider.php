<?php

declare(strict_types=1);

namespace Ehsandevs\LaravelBackupRestore;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Ehsandevs\LaravelBackupRestore\Commands\RestoreCommand;

class LaravelBackupRestoreServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-backup-restore')
            ->hasConfigFile('backup-restore')
            ->hasCommand(RestoreCommand::class);
    }
}
