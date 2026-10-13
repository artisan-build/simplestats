<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;
use Laravel\Jetstream\TeamInvitation as TeamInvitationModel;

class TeamInvitation extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * The team invitation instance.
     *
     * @var TeamInvitationModel
     */
    public $invitation;

    /**
     * Create a new message instance.
     */
    public function __construct(TeamInvitationModel $invitation)
    {
        $this->invitation = $invitation;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $invitedUserAlreadyHasAccount = (bool) User::where('email', $this->invitation->email)->first();

        $data = [
            'invitedUserAlreadyHasAccount' => $invitedUserAlreadyHasAccount,
            'acceptUrl' => URL::signedRoute('team-invitations.accept', ['invitation' => $this->invitation]),
        ];

        return $this->markdown('emails.team-invitation', $data)->subject(__('Team Invitation'));
    }
}
