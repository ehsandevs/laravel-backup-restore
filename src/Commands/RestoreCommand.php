<?php

declare(strict_types=1);

namespace Ehsandevs\LaravelBackupRestore\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Ehsandevs\LaravelBackupRestore\Actions\CleanupLocalBackupAction;
use Ehsandevs\LaravelBackupRestore\Actions\DecompressBackupAction;
use Ehsandevs\LaravelBackupRestore\Actions\DownloadBackupAction;
use Ehsandevs\LaravelBackupRestore\Actions\ImportDumpAction;
use Ehsandevs\LaravelBackupRestore\Actions\ResetDatabaseAction;
use Ehsandevs\LaravelBackupRestore\Exceptions\CannotCreateDbImporter;
use Ehsandevs\LaravelBackupRestore\Exceptions\DecompressionFailed;
use Ehsandevs\LaravelBackupRestore\Exceptions\ImportFailed;
use Ehsandevs\LaravelBackupRestore\Exceptions\NoBackupsFound;
use Ehsandevs\LaravelBackupRestore\Exceptions\NoDatabaseDumpsFound;
use Ehsandevs\LaravelBackupRestore\HealthChecks\HealthCheck;
use Ehsandevs\LaravelBackupRestore\HealthChecks\Result;
use Ehsandevs\LaravelBackupRestore\PendingRestore;

class RestoreCommand extends Command
{
    public $signature = 'backup:restore
                        {--disk= : The disk from where to restore the backup from. Defaults to the first disk in config/backup.php.}
                        {--backup= : The backup to restore. Defaults to the latest backup.}
                        {--connection= : The database connection to restore the backup to. Defaults to the first connection in config/backup.php.}
                        {--password= : The password to decrypt the backup.}
                        {--reset : Drop all tables in the database before restoring the backup.}';

    public $description = 'Restore a database backup dump from a given disk to a database connection.';

    /**
     * @throws NoDatabaseDumpsFound
     * @throws NoBackupsFound
     * @throws CannotCreateDbImporter
     * @throws DecompressionFailed
     * @throws ImportFailed
     */
    public function handle(
        DownloadBackupAction $downloadBackupAction,
        DecompressBackupAction $decompressBackupAction,
        ResetDatabaseAction $resetDatabaseAction,
        ImportDumpAction $importDumpAction,
        CleanupLocalBackupAction $cleanupLocalBackupAction
    ): int {
        $connection = $this->option('connection') ?? config('backup.backup.source.databases')[0];

        $pendingRestore = PendingRestore::make(
            disk: $this->getDestinationDiskToRestoreFrom(),
            backup: $this->getBackupToRestore($this->getDestinationDiskToRestoreFrom()),
            connection: $connection,
            backupPassword: $this->getPassword(),
        );

        if (!$this->confirmRestoreProcess($pendingRestore)) {
            $this->warn('Abort.');

            return self::INVALID;
        }

        consoleOutput()->setCommand($this);

        $downloadBackupAction->execute($pendingRestore);
        $decompressBackupAction->execute($pendingRestore);

        if ($this->option('reset')) {
            $resetDatabaseAction->execute($pendingRestore);
        }

        $importDumpAction->execute($pendingRestore);

        $this->info('Cleaning up …');
        $cleanupLocalBackupAction->execute($pendingRestore);

        return $this->runHealthChecks($pendingRestore);
    }

    private function getDestinationDiskToRestoreFrom(): string
    {
        // Use disk from --disk option if provided
        if ($this->option('disk')) {
            return $this->option('disk');
        }

        $availableDestinations = config('backup.backup.destination.disks');

        // If there is only one disk configured, use it
        if (count($availableDestinations) === 1) {
            return $availableDestinations[0];
        }

        // Ask user to choose a disk
        return $this->choice(
            'From which disk should the backup be restored?',
            $availableDestinations,
            head($availableDestinations)
        );
    }

    /**
     * @throws NoBackupsFound
     */
    private function getBackupToRestore(string $disk): string
    {
        $name = config('backup.backup.name');

        $this->info("Fetch list of backups from $disk …");
        $listOfBackups = collect(Storage::disk($disk)->allFiles($name))
            ->filter(fn($file) => Str::endsWith($file, '.zip'));

        if ($listOfBackups->count() === 0) {
            if (isset($disk)) {
                $this->error("No backups found on {$disk}.");
            }
            throw NoBackupsFound::onDisk($disk);
        }

        if ($this->option('backup') === 'latest') {
            return $listOfBackups->last();
        }

        if ($this->option('backup')) {
            return $this->option('backup');
        }

        return $this->choice(
            'Which backup should be restored?',
            $listOfBackups->toArray(),
            $listOfBackups->last()
        );
    }

    private function getPassword(): ?string
    {
        if ($this->option('password')) {
            $password = $this->option('password');
        } elseif ($this->option('no-interaction')) {
            $password = config('backup.backup.password');
        } elseif ($this->confirm('Use encryption password from config?', true)) {
            $password = config('backup.backup.password');
        } else {
            $password = $this->secret('What is the password to decrypt the backup? (leave empty if not encrypted)');
        }

        return $password;
    }

    private function runHealthChecks(PendingRestore $pendingRestore): int
    {
        $failedResults = collect(config('backup-restore.health-checks'))
            ->map(fn($check) => $check::new ())
            ->map(fn(HealthCheck $check) => $check->run($pendingRestore))
            ->filter(fn(Result $result) => $result->status === self::FAILURE);

        if ($failedResults->count() > 0) {
            $failedResults->each(fn(Result $result) => $this->error($result->message));

            return self::FAILURE;
        }

        $this->info('All health checks passed.');

        return self::SUCCESS;
    }

    private function confirmRestoreProcess(PendingRestore $pendingRestore): bool
    {
        $connectionConfig = config("database.connections.{$pendingRestore->connection}");
        $connectionInformationForConfirmation = collect([
            'Host' => Arr::get($connectionConfig, 'host'),
            'Database' => Arr::get($connectionConfig, 'database'),
            'username' => Arr::get($connectionConfig, 'username'),
        ])->filter()->map(fn($value, $key) => "{$key}: {$value}")->implode(', ');

        return $this->confirm(
            sprintf(
                'Proceed to restore "%s" using the "%s" database connection. (%s)',
                $pendingRestore->backup,
                $pendingRestore->connection,
                $connectionInformationForConfirmation
            ),
            true
        );
    }
}
