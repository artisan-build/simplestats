<?php

namespace App\Builder\Drivers;

use App\Enums\TrackablePerson;

interface AggregationBuilderType
{
    /**
     * Retrieves the select statement for date.
     */
    public function getDateSelect(): string;

    public function getDateInterval(string $calc, string $date, int $amount, string $interval): string;

    public function getDistinctFields(array $fields): string;

    /**
     * Formats the given date string into the specified format.
     *
     * @param  string  $date  The date string to be formatted.
     * @param  string  $format  The desired format for the date.
     * @return string The formatted date string.
     */
    public function getDateFormat(string $date, string $format): string;

    /**
     * Retrieve the available date formats.
     */
    public function getDateFormats(): array;

    /**
     * Retrieve an array of cleared date formats.
     */
    public function getDateFormatsClear(): array;

    /**
     * Get the grouped order by clause based on the given sort parameter.
     *
     * @param  string  $sort  The sorting parameter to group order by.
     * @param  TrackablePerson  $trackablePerson  The trackable person enum value.
     * @return string The grouped order by clause.
     */
    public function getGroupedOrderBy(string $sort, TrackablePerson $trackablePerson): string;

    public function getEventsGroupedOrderBy(string $sort): string;
}
