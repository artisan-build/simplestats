<?php

namespace App\Builder;

use App\Enums\TrackablePerson;

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

    protected function getDateSelect(): string
    {
        return "':startDateStart'::timestamp";
    }

    protected function getDateInterval(string $calc, string $date, int $amount, string $interval): string
    {
        $func = ($calc === 'add') ? '+' : '-';

        return "$date $func INTERVAL '$amount $interval'";
    }

    protected function getDistinctFields(array $fields): string
    {
        return '('.implode(', ', $fields).')';
    }

    protected function getDateFormat(string $date, string $format): string
    {
        return "TO_CHAR($date, '$format')";
    }

    protected function getGroupedOrderBy(string $sort, TrackablePerson $trackablePerson): string
    {
        $oder = "ORDER BY $sort DESC NULLS LAST, net DESC NULLS LAST";

        if ($trackablePerson === TrackablePerson::USER) {
            $oder .= ', reg DESC NULLS LAST';
        }

        $oder .= ', visitors DESC;';

        return $oder;
    }

    protected function getEventsGroupedOrderBy(string $sort): string
    {
        return "ORDER BY {$sort} DESC NULLS LAST, triggered DESC NULLS LAST, visitors DESC;";
    }
}
