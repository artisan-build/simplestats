<?php

namespace App\Http\Controllers;

use App\Enums\FeedbackType;
use App\Http\Requests\FeedbackRequest;
use App\Mail\FeedbackMail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Mail;

class FeedbackController extends Controller
{
    public function __invoke(FeedbackRequest $request): RedirectResponse
    {
        Mail::to(config('app.admin_mail'))->send(new FeedbackMail(
            user: $request->user(),
            type: FeedbackType::from($request->validated('type')),
            feedbackMessage: $request->validated('message'),
        ));

        return redirect()->back()->banner('Thank you for your feedback!');
    }
}
