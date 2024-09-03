<?php

namespace Ehsandevs\LaravelBackupRestore\Databases;

use Spatie\Backup\Exceptions\CannotCreateDbDumper;
use Spatie\Backup\Tasks\Backup\DbDumperFactory;
use Str;

class PostgreSql extends DbImporter
{
    /**
     * @throws CannotCreateDbDumper
     */
    public function getImportCommand(string $dumpFile): string
    {
        /** @var \Spatie\DbDumper\Databases\PostgreSql $dumper */
        $dumper = DbDumperFactory::createFromConnection('pgsql');
        $dumper->getContentsOfCredentialsFile();

        // @todo: Improve detection of compressed files
        // @todo: Use $pendingRestore->connection
        if (Str::endsWith($dumpFile, 'gz')) {
            return 'gunzip -c ' . $dumpFile . ' | psql -U ' . config('database.connections.pgsql.username') . ' -d ' . config('database.connections.pgsql.database');
        }

        return 'psql -U ' . config('database.connections.pgsql.username') . ' -d ' . config('database.connections.pgsql.database') . ' < ' . $dumpFile;
    }
}
