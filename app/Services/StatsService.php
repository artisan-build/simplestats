<?php

namespace App\Services;

use App\Models\Instance;
use App\Models\Project;
use Carbon\Carbon;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class StatsService
{
    public function getProjectsOverview()
    {
        $projects = $this->getStats();

        $this->groupStats($projects);
        $this->calcStatsTotalPerProject($projects);
        $this->applyStatsDetails($projects);

        return $projects;
    }

    public function getStats()
    {
//        ray()->showQueries();
        return Project::orderBy('created_at', 'desc')->with([
            'instances' => function (Builder $query) {
                $query->withCount([
                    // ! ==== Lifetime (lt) ==== !
                    'statsUsers as lt_REG',
                    'statsUsers as lt_PU' => function (Builder $query) {
                        $query->whereHas('statsPayments');
                    },
                    // total active users
                    'statsUsers as lt_TAU' => function (Builder $query) {
                        $query->whereHas('statsLogins', function (Builder $query) {
                            $query->whereColumn('instance_id', 'instances.id');
                            $query->where('counter', 2);
                        });
                    },
                    'statsPayments as lt_REV' => function (Builder $query) {
                        $query->select(DB::raw("sum(net)"));
                    },

                    // ! ==== Yesterday (yd) ==== !
                    'statsUsers as yd_REG' => function (Builder $query) {
                        $query->whereDate('time', Carbon::yesterday());
                    },
                    'statsUsers as yd_NAU' => function (Builder $query) {
                        // added counter, because: https://github.com/laracraft-tech/simplestats/issues/42#nau
                        $query->whereHas('statsLogins', function (Builder $query) {
                            $query->whereColumn('instance_id', 'instances.id');
                            $query->whereDate('time', Carbon::yesterday())
                                ->where('counter', 2);
                        });
                    },
                    'statsUsers as yd_DAU' => function (Builder $query) {
                        //TODO refactor same queries (own function)
                        $query->whereHas('statsLogins', function (Builder $query) {
                            $query->whereColumn('instance_id', 'instances.id');
                            $query->whereDate('time', '=', Carbon::yesterday())
                                ->whereNot('counter', 1);
                        });
                    },
                    'statsUsers as yd_DAPU' => function (Builder $query) {
                        //TODO refactor same queries (own function)
                        $query->whereHas('statsLogins', function (Builder $query) {
                            $query->whereColumn('instance_id', 'instances.id');
                            $query->whereDate('time', '=', Carbon::yesterday());
                        })->whereHas('statsPayments', function (Builder $query) {
                            $query->whereColumn('instance_id', 'instances.id');
                        });
                    },
                    'statsUsers as yd_DAAPU' => function (Builder $query) {
                        //TODO refactor same queries (own function)
                        $query->whereHas('statsLogins', function (Builder $query) {
                            $query->whereColumn('instance_id', 'instances.id');
                            $query->whereDate('time', '=', Carbon::yesterday())
                                ->whereNot('counter', 1);
                        })->whereHas('statsPayments', function (Builder $query) {
                            $query->whereColumn('instance_id', 'instances.id');
                            $query->whereDate('time', '>=', Carbon::yesterday()->subDays(20-1));
                        });
                    },
                    'statsUsers as yd_WAU' => function (Builder $query) {
                        //TODO refactor same queries (own function)
                        $query->whereHas('statsLogins', function (Builder $query) {
                            $query->whereColumn('instance_id', 'instances.id');
                            $query->whereDate('time', '>=', Carbon::yesterday()->subDays(7-1))
                                ->whereNot('counter', 1);
                        });
                    },
                    'statsUsers as yd_MAU' => function (Builder $query) {
                        //TODO refactor same queries (own function)
                        $query->whereHas('statsLogins', function (Builder $query) {
                            $query->whereColumn('instance_id', 'instances.id');
                            $query->whereDate('time', '>=', Carbon::yesterday()->subDays(30-1))
                                ->whereNot('counter', 1);
                        });
                    },
                    'statsUsers as yd_MAPU' => function (Builder $query) {
                        //TODO refactor same queries (own function)
                        $query->whereHas('statsLogins', function (Builder $query) {
                            $query->whereColumn('instance_id', 'instances.id');
                            $query->whereDate('time', '>=', Carbon::yesterday()->subDays(30));
                        })->whereHas('statsPayments', function (Builder $query) {
                            $query->whereColumn('instance_id', 'instances.id');
                        });
                    },
                    'statsUsers as yd_MAAPU' => function (Builder $query) {
                        //TODO refactor same queries (own function)
                        $query->whereHas('statsLogins', function (Builder $query) {
                            $query->whereColumn('instance_id', 'instances.id');
                            $query->whereDate('time', '>=', Carbon::yesterday()->subDays(30))
                                ->whereNot('counter', 1);
                        })->whereHas('statsPayments', function (Builder $query) {
                            $query->whereColumn('instance_id', 'instances.id');
                            $query->whereDate('time', '>=', Carbon::yesterday()->subDays(20));
                        });
                    },
                    'statsPayments as yd_REV' => function (Builder $query) {
                        $query->select(DB::raw("sum(net)"))->whereDate('time', Carbon::yesterday());
                    },
                ]);
            }
        ])->get();
    }

    private function groupStats(Collection &$projects): void
    {
        $projects->transform(function (Project $project) {
            $project->instances->transform(function (Instance $instance) {
                $result = ['stats' => []];
                $statsPrefixes = ['lt_' => 'lifetime', 'yd_' => 'yesterday'];

                foreach ($instance->getAttributes() as $key => $value) {
                    if (Str::startsWith($key, array_keys($statsPrefixes))) {
                        $parts = explode('_', $key);
                        $statsKey = $statsPrefixes[$parts[0].'_'];
                        $result['stats'][$statsKey][$parts[1]] = $value;
                    } else {
                        $result[$key] = $value;
                    }
                }

                return $result;
            });

            return $project;
        });

    }

    private function calcStatsTotalPerProject(Collection &$projects): void
    {
        $projects->transform(function (Project $project) {
            $result = [];
            $project->instances->each(function ($instance) use (&$result) {
                foreach ($instance['stats'] as $statCategory => $stats) {
                    foreach ($stats as $key => $value) {
                        $result[$statCategory][$key] = ($result[$statCategory][$key] ?? 0) + $value;
                    }
                }
            });

            $project->stats = $result;

            return $project;
        });
    }

    private function applyStatsDetails(Collection $projects): void
    {
        $projects->transform(function (Project $project) {
            $project['stats'] = array_merge(['name' => 'Total'], $this->addStatsDetails($project['stats']));

            $project->instances->transform(function ($instance) {
                $instance['stats'] = array_merge(['name' => $instance['name']], $this->addStatsDetails($instance['stats']));
                return $instance;
            });

            return $project;
        });
    }

    private function addStatsDetails(array $stats): array
    {
        // Lifetime
        $stats['lifetime']['REV'] = round($stats['lifetime']['REV'], 2);
        $stats['lifetime']['REG2DAU'] = round($this->safeDivision($stats['yesterday']['DAU'], $stats['lifetime']['REG']) * 100, 2).'%';
        $stats['lifetime']['REG2PU'] = round($this->safeDivision($stats['lifetime']['PU'], $stats['lifetime']['REG']) * 100, 2).'%';
        $stats['lifetime']['REG2TAU'] = round($this->safeDivision($stats['lifetime']['TAU'], $stats['lifetime']['REG']) * 100, 2).'%';
        $stats['lifetime']['ARPU'] = round($this->safeDivision($stats['lifetime']['REV'], $stats['lifetime']['REG']) * 100, 2);
        $stats['lifetime']['ARPPU'] = round($this->safeDivision($stats['lifetime']['REV'], $stats['lifetime']['PU']) * 100, 2);

        // Yesterday
        $stats['yesterday']['REV'] = round($stats['yesterday']['REV'], 2);

        return $stats;
    }

    public function safeDivision($numerator, $denominator): float|int
    {
        return $denominator == 0 ? 0 : $numerator / $denominator;
    }
}
