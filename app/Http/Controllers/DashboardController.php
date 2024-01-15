<?php

namespace App\Http\Controllers;

use App\Models\Instance;
use App\Models\Project;
use Carbon\Carbon;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function __invoke()
    {
//        ray()->showQueries();
        $projects = Project::orderBy('created_at', 'desc')->with([
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
                            $query->whereDate('time', '=', Carbon::yesterday())
                                ->whereNot('counter', 1);
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
                            $query->whereDate('time', '>=', Carbon::yesterday()->subDays(30))
                                ->whereNot('counter', 1);
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

        // TODO move this functions
        function safeDivision($numerator, $denominator) {
            return $denominator == 0 ? 0 : $numerator / $denominator;
        }

        $projects->transform(function (Project $project) {
            $project->instances->transform(function (Instance $instance) {

                $instance->lt_REV = round($instance->lt_REV, 2);
                $instance->lt_REG2DAU = round(safeDivision($instance->yd_DAU, $instance->lt_REG) * 100, 2);
                $instance->lt_REG2PU = round(safeDivision($instance->lt_PU, $instance->lt_REG) * 100, 2);
                $instance->lt_REG2TAU = round(safeDivision($instance->lt_TAU, $instance->lt_REG) * 100, 2);
                $instance->lt_ARPU = round(safeDivision($instance->lt_REV, $instance->lt_REG), 2);
                $instance->lt_ARPPU = round(safeDivision($instance->lt_REV, $instance->lt_PU), 2);

                $instance->yd_REV = round($instance->yd_REV, 2);

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

//dd($projects->toArray());
//        ray()->stopShowingQueries();
//        $project = $projects->first();
////        dump($project->id);
//echo "<pre>"; print_r($project->instances->first());die;
        return Inertia::render('Dashboard', compact('projects'));
    }
}
