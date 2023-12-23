<?php

namespace App\Http\Controllers;

use App\Http\Requests\InstanceStoreRequest;
use App\Http\Requests\InstanceUpdateRequest;
use App\Models\Instance;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InstanceController extends Controller
{
    public function index(Request $request): Response
    {
        $instances = Instance::all();

        return Inertia::render('instance.index', compact('instances'));
    }

    public function create(Request $request): Response
    {
        return Inertia::render('instance.create');
    }

    public function store(InstanceStoreRequest $request): RedirectResponse
    {
        $instance = Instance::create($request->validated());

        $request->session()->flash('instance.id', $instance->id);

        return redirect()->route('instance.index');
    }

    public function show(Request $request, Instance $instance): Response
    {
        return Inertia::render('instance.show', compact('instance'));
    }

    public function edit(Request $request, Instance $instance): Response
    {
        return Inertia::render('instance.edit', compact('instance'));
    }

    public function update(InstanceUpdateRequest $request, Instance $instance): RedirectResponse
    {
        $instance->update($request->validated());

        $request->session()->flash('instance.id', $instance->id);

        return redirect()->route('instance.index');
    }

    public function destroy(Request $request, Instance $instance): RedirectResponse
    {
        $instance->delete();

        return redirect()->route('instance.index');
    }
}
