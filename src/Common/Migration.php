<?php
declare(strict_types=1);

namespace Hyperf\Support\Common;

use \Hyperf\Database\Migrations\Migration as HyperfMigration;
use Hyperf\DbConnection\Db;

abstract class Migration extends HyperfMigration
{
    protected string $prefix;

    public function __construct()
    {
        $this->prefix = (string)config('databases.default.prefix');
    }

    protected function comment(string $table, string $comment): void
    {
        Db::statement(/** @lang text */
            "ALTER TABLE `{$this->prefix}{$table}` comment '{$comment}'"
        );
    }
}