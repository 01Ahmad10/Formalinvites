<?php

namespace App\Http\Controllers;

use App\Models\InvitationParty;
use App\Support\InvitationPresenter;
use App\Support\PublishedInvitationResolver;
use App\Support\PublicRsvpPayload;
use Inertia\Inertia;
use Inertia\Response;

class PublicInvitationController extends Controller
{
    public function show(string $token, InvitationPresenter $invitationPresenter, PublicRsvpPayload $rsvpPayload, PublishedInvitationResolver $published): Response
    {
        [$party, $publication] = $published->forToken($token);

        return Inertia::render('PublicInvitation', [
            'invitation' => $invitationPresenter->presentPublication($publication, $party),
            'party' => $rsvpPayload->invitationParty($party, $publication),
            'rsvp' => $rsvpPayload->invitationRsvp($party, $publication),
            'meals' => $rsvpPayload->invitationMeals($party, $publication),
            'closed' => $rsvpPayload->closed($party, $publication),
            'confirmation' => session('success'),
        ]);
    }
}
