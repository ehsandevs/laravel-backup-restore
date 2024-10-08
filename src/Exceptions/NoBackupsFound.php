<?php

namespace Ehsandevs\LaravelBackupRestore\Exceptions;

use Exception;

class NoBackupsFound extends Exception
{
    public static function onDisk(string $disk): self
    {
        return new static("No backups found on disk $disk.");
    }
}
