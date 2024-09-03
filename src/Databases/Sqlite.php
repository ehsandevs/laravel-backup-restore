<?php

namespace Ehsandevs\LaravelBackupRestore\Databases;
use Str;

class Sqlite extends DbImporter
{
    public function getImportCommand(string $dumpFile): string
    {
        // @todo: Improve detection of compressed files
        // @todo: Use $pendingRestore->connection
        if (Str::endsWith($dumpFile, 'gz')) {
            // Shell command to import a gzipped SQL file to a sqlite database
            return 'gunzip -c ' . $dumpFile . ' | sqlite3 ' . config('database.connections.sqlite.database');
        }

        return 'sqlite3 ' . config('database.connections.sqlite.database') . ' < ' . $dumpFile;
    }
}
