<?php

namespace App\Builder\Drivers;

interface AggregationBuilderType
{
    /**
     * Retrieves the select statement for date.
     *
     * @return string
     */
    public function getDateSelect(): string;

    /**
     * @param  string  $calc
     * @param  string  $date
     * @param  int  $amount
     * @param  string  $interval
     * @return string
     */
    public function getDateInterval(string $calc, string $date, int $amount, string $interval): string;

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
     *
     * @return array
     */
    public function getDateFormats(): array;

    /**
     * Retrieve an array of cleared date formats.
     *
     * @return array
     */
    public function getDateFormatsClear(): array;

    /**
     * Get the grouped order by clause based on the given sort parameter.
     *
     * @param  string  $sort  The sorting parameter to group order by.
     * @return string The grouped order by clause.
     */
    public function getGroupedOrderBy(string $sort): string;

}
