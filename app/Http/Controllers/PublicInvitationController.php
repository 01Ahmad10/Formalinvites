<?php

namespace App\Http\Controllers;

use App\Models\InvitationParty;
use App\Support\InvitationPresenter;
use App\Support\PublicRsvpPayload;
use Inertia\Inertia;
use Inertia\Response;

class PublicInvitationController extends Controller
{
    public function show(string $token, InvitationPresenter $invitationPresenter, PublicRsvpPayload $rsvpPayload): Response
    {
        $party = InvitationParty::query()->where('rsvp_token', $token)->with(['event', 'members'])->firstOrFail();

        return Inertia::render('PublicInvitation', [
            'invitation' => $invitationPresenter->present($party->event, $party),
            'party' => $rsvpPayload->invitationParty($party),
            'rsvp' => $rsvpPayload->invitationRsvp($party),
            'meals' => $rsvpPayload->invitationMeals($party),
            'closed' => $rsvpPayload->closed($party),
            'confirmation' => session('success'),
        ]);
    }
}
