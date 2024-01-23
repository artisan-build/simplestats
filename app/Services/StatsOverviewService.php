<?php

namespace App\Services;

use App\Models\Instance;
use App\Models\Project;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;

class StatsOverviewService
{
    public function __construct(
        private StatsService $statsService
    ) {}

    public function getStats()
    {
        $projects = $this->getRaw();

        $this->groupStats($projects);
        $this->calcStatsTotal($projects);
        $this->applyStatsDetails($projects);

        return $projects;
    }

    public function getRaw()
    {
//        ray()->showQueries();
        return Project::orderBy('created_at', 'desc')->with([
            'instances' => function (Builder $query) {
                $query->withCount([
                    // ! ==== Lifetime (lt) ==== !
                    'statsUsers as lt_REG',
                    'statsUsers as lt_PU' => function (Builder $query) {
                        $query->whereHas('statsPayments', function (Builder $query) {
                            $query->whereColumn('instance_id', 'instances.id');
                        });
                    },
                    // total active users
                    'statsUsers as lt_TAU' => function (Builder $query) {
                        $query->whereHas('statsLogins', function (Builder $query) {
                            $query->whereColumn('instance_id', 'instances.id');
                            $query->where('counter', 2);
                        });
                    },
                    'statsPayments as lt_NET' => function (Builder $query) {
                        $query->select(DB::raw("sum(net)"));
                    },

                    // ! ==== Yesterday (per date - pd short) ==== !
                    'statsUsers as pd_REG' => function (Builder $query) {
                        $query->whereDate('time', Date::yesterday());
                    },
                    'statsUsers as pd_NAU' => function (Builder $query) {
                        // added counter, because: https://github.com/laracraft-tech/simplestats/issues/42#nau
                        $query->whereHas('statsLogins', function (Builder $query) {
                            $query->whereColumn('instance_id', 'instances.id');
                            $query->whereDate('time', Date::yesterday())
                                ->where('counter', 2);
                        });
                    },
                    'statsUsers as pd_DAU' => function (Builder $query) {
                        //TODO refactor same queries (own function)
                        $query->whereHas('statsLogins', function (Builder $query) {
                            $query->whereColumn('instance_id', 'instances.id');
                            $query->whereDate('time', '=', Date::yesterday())
                                ->whereNot('counter', 1);
                        });
                    },
                    'statsUsers as pd_DAPU' => function (Builder $query) {
                        //TODO refactor same queries (own function)
                        $query->whereHas('statsLogins', function (Builder $query) {
                            $query->whereColumn('instance_id', 'instances.id');
                            $query->whereDate('time', '=', Date::yesterday());
                        })->whereHas('statsPayments', function (Builder $query) {
                            $query->whereColumn('instance_id', 'instances.id');
                        });
                    },
                    'statsUsers as pd_DAAPU' => function (Builder $query) {
                        //TODO refactor same queries (own function)
                        $query->whereHas('statsLogins', function (Builder $query) {
                            $query->whereColumn('instance_id', 'instances.id');
                            $query->whereDate('time', '=', Date::yesterday())
                                ->whereNot('counter', 1);
                        })->whereHas('statsPayments', function (Builder $query) {
                            $query->whereColumn('instance_id', 'instances.id');
                            $query->whereDate('time', '>=', Date::yesterday()->subDays(20-1));
                        });
                    },
                    'statsUsers as pd_WAU' => function (Builder $query) {
                        //TODO refactor same queries (own function)
                        $query->whereHas('statsLogins', function (Builder $query) {
                            $query->whereColumn('instance_id', 'instances.id');
                            $query->whereDate('time', '>=', Date::yesterday()->subDays(7-1))
                                ->whereNot('counter', 1);
                        });
                    },
                    'statsUsers as pd_MAU' => function (Builder $query) {
                        //TODO refactor same queries (own function)
                        $query->whereHas('statsLogins', function (Builder $query) {
                            $query->whereColumn('instance_id', 'instances.id');
                            $query->whereDate('time', '>=', Date::yesterday()->subDays(30-1))
                                ->whereNot('counter', 1);
                        });
                    },
                    'statsUsers as pd_MAPU' => function (Builder $query) {
                        //TODO refactor same queries (own function)
                        $query->whereHas('statsLogins', function (Builder $query) {
                            $query->whereColumn('instance_id', 'instances.id');
                            $query->whereDate('time', '>=', Date::yesterday()->subDays(30));
                        })->whereHas('statsPayments', function (Builder $query) {
                            $query->whereColumn('instance_id', 'instances.id');
                        });
                    },
                    'statsUsers as pd_MAAPU' => function (Builder $query) {
                        //TODO refactor same queries (own function)
                        $query->whereHas('statsLogins', function (Builder $query) {
                            $query->whereColumn('instance_id', 'instances.id');
                            $query->whereDate('time', '>=', Date::yesterday()->subDays(30))
                                ->whereNot('counter', 1);
                        })->whereHas('statsPayments', function (Builder $query) {
                            $query->whereColumn('instance_id', 'instances.id');
                            $query->whereDate('time', '>=', Date::yesterday()->subDays(20));
                        });
                    },
                    'statsPayments as pd_NET' => function (Builder $query) {
                        $query->select(DB::raw("sum(net)"))->whereDate('time', Date::yesterday());
                    },
                ]);
            }
        ])->get();
    }

    private function groupStats(Collection &$projects): void
    {
        $projects->transform(function (Project $project) {
            $project->instances->transform(function (Instance $instance) {
                return $this->statsService->groupStats($instance->getAttributes());
            });

            return $project;
        });

    }

    private function calcStatsTotal(Collection &$projects): void
    {
        $statsOverallTotal = [];
        $projects->transform(function (Project $project) use (&$statsOverallTotal) {
            $statsTotal = [];
            $project->instances->each(function ($instance) use (&$statsTotal) {
                $this->statsService->applyStatsTotal($instance['stats'], $statsTotal);
            });

            $project->stats = $statsTotal;

            $this->statsService->applyStatsTotal($project->stats, $statsOverallTotal);

            return $project;
        });

        if ($projects->count() > 1) {
            $this->addOverallProject($projects, $statsOverallTotal);
        }
    }

    private function addOverallProject(Collection $projects, array $statsOverallTotal): void
    {
        Project::unguard();

        $projects->push(new Project([
            'id' => 0,
            'overallTotal' => true,
            'name' => 'Total',
            'stats' => $statsOverallTotal,
        ]));

        Project::reguard();
    }

    private function applyStatsDetails(Collection $projects): void
    {
        $projects->transform(function (Project $project) {
            if (!empty($project['stats'])) {
                $project['stats'] = array_merge(['name' => 'Total'], $this->statsService->addStatsDetails($project['stats']));
            }

            $project->instances->transform(function ($instance) {
                $instance['stats'] = array_merge(['name' => $instance['name']], $this->statsService->addStatsDetails($instance['stats']));
                return $instance;
            });

            return $project;
        });
    }
}
