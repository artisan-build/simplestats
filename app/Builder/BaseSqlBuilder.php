<?php

namespace App\Builder;

abstract class BaseSqlBuilder
{
    protected string $sql = '';

    abstract public function build(): string;

    protected function applySql(string $sql, bool $addComma = true): void
    {
        $this->sql .= $sql;

        if ($addComma) {
            $this->sql .= ',';
        }

        // break
        $this->sql .= '
        ';
    }
}
