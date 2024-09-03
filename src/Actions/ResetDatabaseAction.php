<?php

namespace Ehsandevs\LaravelBackupRestore\Actions;

use Ehsandevs\LaravelBackupRestore\PendingRestore;
use Illuminate\Support\Facades\DB;
use Ehsandevs\LaravelBackupRestore\Events\DatabaseReset;

class ResetDatabaseAction
{
    public function execute(PendingRestore $pendingRestore)
    {
        consoleOutput()->info('Reset database …');

        DB::connection($pendingRestore->connection)
            ->getSchemaBuilder()
            ->dropAllTables();

        event(new DatabaseReset($pendingRestore));
    }
}
