<?php

namespace App\Services;

use App\Models\Instance;
use App\Models\Project;
use App\Models\Team;
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
        $team = $this->getRaw();

        $this->groupStats($team);
        $this->applyStatsTotal($team);
        $this->applyStatsDetailsGroup($team);

        return $team;
    }

    public function getRaw()
    {
        return auth()->user()->currentTeam()->with('projects', function (Builder $query) {
            $query->orderBy('created_at', 'desc')->with('instances', function (Builder $query) {
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
            });
        })->get()->first();
    }

    private function groupStats(Team $team): void
    {
        $team->projects->transform(function (Project $project) {
            $project->instances->transform(function (Instance $instance) {
                return (object) $this->statsService->groupStats($instance->getAttributes());
            });

            return $project;
        });

    }

    private function applyStatsTotal(Team $team): void
    {
        $statsTeamTotal = [];

        $team->projects->transform(function (Project $project) use (&$statsTeamTotal) {
            $statsProjectTotal = [];

            $project->instances->each(function (object $instance) use (&$statsProjectTotal) {
                $this->statsService->calcStatsTotal($instance->stats, $statsProjectTotal);
            });

            $project->stats = $statsProjectTotal;


            $this->statsService->calcStatsTotal($project->stats, $statsTeamTotal);

            return $project;
        });

        $team->stats = $statsTeamTotal;
    }

    private function applyStatsDetailsGroup(Team $team): void
    {
        $team->name = 'Total';
        $this->applyStatsDetails($team, 0, 'Total');

        $team->projects->transform(function (Project $project) {
            $this->applyStatsDetails($project, $project->id, 'Total');

            $project->instances->transform(function (object $instance) {
                $instance->instance_id = $instance->id;
                $this->applyStatsDetails($instance, $instance->project_id, $instance->name);

                return $instance;
            });

            return $project;
        });
    }

    private function applyStatsDetails(object $item, int $projectId, string $name)
    {
        $item->project_id = $projectId;

        if (isset($item->stats)) {
            $item->stats = array_merge([
                'name' => $name,
                'project_id' => $projectId,
            ], $this->statsService->addStatsDetails($item->stats));
        }

        unset($item->id);
    }
}
