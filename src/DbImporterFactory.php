<?php

declare(strict_types=1);

namespace Ehsandevs\LaravelBackupRestore;

use Illuminate\Support\Str;
use Ehsandevs\LaravelBackupRestore\Databases\DbImporter;
use Ehsandevs\LaravelBackupRestore\Databases\MySql;
use Ehsandevs\LaravelBackupRestore\Databases\PostgreSql;
use Ehsandevs\LaravelBackupRestore\Databases\Sqlite;
use Ehsandevs\LaravelBackupRestore\Exceptions\CannotCreateDbImporter;

class DbImporterFactory
{
    protected static array $custom = [];

    /**
     * @throws CannotCreateDbImporter
     */
    public static function createFromConnection(string $dbConnectionName): DbImporter
    {
        $config = config("database.connections.$dbConnectionName");

        if ($config === null) {
            throw CannotCreateDbImporter::configNotFound($dbConnectionName);
        }

        return static::forDriver($config['driver']);
    }

    /**
     * @throws CannotCreateDbImporter
     */
    protected static function forDriver(string $driver): DbImporter
    {
        $driver = Str::lower($driver);

        if (isset(static::$custom[$driver])) {
            return new static::$custom[$driver]();
        }

        return match ($driver) {
            'mysql' => new MySql(),
            'pgsql' => new PostgreSql(),
            'sqlite' => new Sqlite(),
            default => throw CannotCreateDbImporter::unsupportedDriver($driver),
        };
    }
}
