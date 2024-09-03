<?php

namespace Ehsandevs\LaravelBackupRestore\HealthChecks\Checks;

use Illuminate\Support\Facades\DB;
use Ehsandevs\LaravelBackupRestore\HealthChecks\HealthCheck;
use Ehsandevs\LaravelBackupRestore\HealthChecks\Result;
use Ehsandevs\LaravelBackupRestore\PendingRestore;

class DatabaseHasTables extends HealthCheck
{
    public function run(PendingRestore $pendingRestore): Result
    {
        $result = Result::make($this);

        $tables = DB::connection($pendingRestore->connection)
            ->getSchemaBuilder()
            ->getAllTables();

        if (count($tables) === 0) {
            return $result->failed('Database has not tables after restore.');
        }

        return $result->ok();
    }
}
