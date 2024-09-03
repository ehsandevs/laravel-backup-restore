<?php

namespace Ehsandevs\LaravelBackupRestore\Exceptions;

use Ehsandevs\LaravelBackupRestore\PendingRestore;
use Exception;

class NoDatabaseDumpsFound extends Exception
{
    public static function notFoundInBackup(PendingRestore $pendingRestore): self
    {
        return new static("No database dumps found in backup `{$pendingRestore->backup}`.");
    }
}
