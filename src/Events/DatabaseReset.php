<?php

namespace Ehsandevs\LaravelBackupRestore\Events;
use Ehsandevs\LaravelBackupRestore\PendingRestore;


class DatabaseReset
{
    public function __construct(readonly public PendingRestore $pendingRestore)
    {
    }
}
