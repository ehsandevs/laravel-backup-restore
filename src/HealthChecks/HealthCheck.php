<?php

namespace Ehsandevs\LaravelBackupRestore\HealthChecks;

use Ehsandevs\LaravelBackupRestore\PendingRestore;

abstract class HealthCheck
{
  abstract public function run(PendingRestore $pendingRestore): Result;

  public static function new(): self
  {
    return app(static::class);
  }
}