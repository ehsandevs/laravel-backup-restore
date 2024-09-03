<?php

namespace Ehsandevs\LaravelBackupRestore\Events;

class DatabaseDumpImportWasSuccessful
{
    public function __construct(readonly public string $absolutePathToDump)
    {
    }
}
