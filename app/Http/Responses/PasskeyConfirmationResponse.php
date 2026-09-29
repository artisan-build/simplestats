<?php

namespace App\Http\Responses;

use App\Actions\ConfirmPasswordOrPasskey;
use Laravel\Passkeys\Http\Responses\PasskeyConfirmationResponse as BasePasskeyConfirmationResponse;

class PasskeyConfirmationResponse extends BasePasskeyConfirmationResponse
{
    public function toResponse($request)
    {
        $request->session()->put(ConfirmPasswordOrPasskey::SESSION_KEY, time());

        return parent::toResponse($request);
    }
}
