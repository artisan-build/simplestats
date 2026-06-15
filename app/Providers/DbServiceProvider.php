<?php

namespace App\Providers;

use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;

class DbServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->registerJsonIncrementMacro();
    }

    /**
     * Atomically increments a numeric value inside a Postgres json column by a
     * delta, e.g. StatsAggregation::where(...)->incrementJsonValue('data', 'pd_visitor', 1).
     * The read-add-write happens in a single jsonb_set UPDATE, so concurrent
     * events can't lose each other's increment (unlike read-modify-write in PHP).
     */
    private function registerJsonIncrementMacro(): void
    {
        QueryBuilder::macro('incrementJsonValue', function (string $column, string $key, int|float $amount) {
            /** @var QueryBuilder $this */
            $wrapped = $this->getGrammar()->wrap($column);

            // $key/$amount are interpolated because raw expressions in an update()
            // array carry no bindings; $key is sanitised, $amount is typed numeric.
            $key = preg_replace('/[^a-z0-9_]/i', '', $key);

            return $this->update([
                $column => DB::raw(
                    "jsonb_set({$wrapped}::jsonb, '{{$key}}'::text[], to_jsonb(coalesce(({$wrapped}->>'{$key}')::numeric, 0) + {$amount}::numeric), false)::json"
                ),
            ]);
        });
    }
}
