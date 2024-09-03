<?php

namespace Ehsandevs\LaravelBackupRestore\Actions;

use Illuminate\Support\Facades\Storage;
use Ehsandevs\LaravelBackupRestore\DbImporterFactory;
use Ehsandevs\LaravelBackupRestore\Events\DatabaseRestored;
use Ehsandevs\LaravelBackupRestore\Exceptions\CannotCreateDbImporter;
use Ehsandevs\LaravelBackupRestore\Exceptions\ImportFailed;
use Ehsandevs\LaravelBackupRestore\Exceptions\NoDatabaseDumpsFound;
use Ehsandevs\LaravelBackupRestore\PendingRestore;
use Str;

class ImportDumpAction
{
    /**
     * @throws NoDatabaseDumpsFound
     * @throws CannotCreateDbImporter
     * @throws ImportFailed
     */
    public function execute(PendingRestore $pendingRestore): void
    {
        if ($pendingRestore->hasNoDbDumpsDirectory()) {
            throw NoDatabaseDumpsFound::notFoundInBackup($pendingRestore);
        }

        $importer = DbImporterFactory::createFromConnection($pendingRestore->connection);

        $dbDumps = $pendingRestore->getAvailableDbDumps();

        consoleOutput()->info('Importing database ' . Str::plural('dump', $dbDumps) . ' …');

        $dbDumps->each(function ($dbDump) use ($pendingRestore, $importer) {
            consoleOutput()->info('Importing ' . Str::afterLast($dbDump, '/'));
            $absolutePathToDump = Storage::disk($pendingRestore->restoreDisk)->path($dbDump);
            $importer->importToDatabase($absolutePathToDump);
        });

        event(new DatabaseRestored($pendingRestore));
    }
}
