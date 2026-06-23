<?php

namespace App\Http\Controllers;

use App\Http\Requests\AdSpendRequest;
use App\Models\AdSpend;
use Illuminate\Http\RedirectResponse;

class AdSpendController extends Controller
{
    public function store(AdSpendRequest $request): RedirectResponse
    {
        AdSpend::create($request->validated());

        return redirect()->back();
    }

    public function update(AdSpendRequest $request, AdSpend $adSpend): RedirectResponse
    {
        $adSpend->update($request->validated());

        return redirect()->back();
    }

    public function destroy(AdSpend $adSpend): RedirectResponse
    {
        $adSpend->delete();

        return redirect()->back();
    }
}
