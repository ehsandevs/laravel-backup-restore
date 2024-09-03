<?php

namespace Ehsandevs\LaravelBackupRestore\Events;
use Ehsandevs\LaravelBackupRestore\PendingRestore;

class DatabaseRestored
{
    public function __construct(readonly public PendingRestore $pendingRestore)
    {
    }
}
