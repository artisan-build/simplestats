<?php

use App\Models\Instance;
use App\Models\Project;
use App\Models\User;

use function Spatie\RouteTesting\routeTesting;

$exclude = [
    '_ignition/*',
    'email/*',
    'impersonate/*',
    'livewire/*',
    'user-settings/*',
    'team*',
    'stats/*',
    'laravel-favicon/*',
    'oauth/*',
    'spark/*',
    'billing/*',
    'projects/{project}/instances/*', // token-view & token-regenerate routes
];

routeTesting('all GET routes')
    ->exclude($exclude)
    ->setUp(function (string $method, string $uri) {
        //if ($uri !== 'user/confirm-password') return; // only for tests

        $route = app('router')->getRoutes()->match(request()->create($uri, $method));

        if (in_array('auth:sanctum', $route->middleware()) || in_array('auth:web', $route->middleware())) {
            $this->seed();
            $project = Project::get()->first();
            $instance = Instance::get()->first();

            if ($uri === 'intro') {
                $project->delete();
            }

            $user = User::where('email', config('app.test.user')['email'])->firstOrFail();
            $this->actingAs($user);

            $this->bind('project', $project);
            $this->bind('instance', $instance);
        }
    })
    ->assertSuccessful();
