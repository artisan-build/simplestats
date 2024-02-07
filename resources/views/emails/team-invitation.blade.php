@php
$teamName = $invitation->team->name;
$teamOwner = $invitation->team->owner()->first()->name;
@endphp

@component('mail::message')
{{ __('You have been invited by :owner to join the team: :team!', [
'team' => $teamName,
'owner' => $teamOwner,
]) }}

@if ($invitedUserAlreadyHasAccount)
@component('mail::button', ['url' => $acceptUrl])
{{ __('Join Team') }}
@endcomponent
@else
@component('mail::button', ['url' => route('register', [
'invitation_mail' => $invitation->email,
'accept_invitation_link' => $acceptUrl,
'team_name' => urlencode($teamName),
'team_owner' => urlencode($teamOwner),
])])
{{ __('Join Team') }}
@endcomponent
@endif

{{ __('If you did not expect to receive an invitation to this team, you may discard this email.') }}
@endcomponent
