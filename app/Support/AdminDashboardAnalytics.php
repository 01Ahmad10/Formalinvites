<?php

namespace App\Support;

use App\Models\Customer;
use App\Models\Event;
use App\Models\InvitationParty;
use App\Models\Payment;
use App\Models\PaymentTransaction;
use Illuminate\Support\Facades\DB;

class AdminDashboardAnalytics
{
    public function data(): array
    {
        $events = Event::query();
        $eventCount = (clone $events)->count();
        $archivedEventCount = (clone $events)->where('status', 'archived')->count();
        $liveEventCount = (clone $events)->where('status', 'published')->count();
        $disabledEventCount = (clone $events)->where('status', 'disabled')->count();
        $setupEventCount = $eventCount - $liveEventCount - $disabledEventCount - $archivedEventCount;

        $confirmedByPayment = PaymentTransaction::query()
            ->selectRaw('payment_id, SUM(amount) as confirmed_amount')
            ->where('status', 'confirmed')
            ->groupBy('payment_id');
        $financials = Payment::query()->leftJoinSub($confirmedByPayment, 'confirmed_transactions', 'confirmed_transactions.payment_id', '=', 'payments.id')
            ->selectRaw('COALESCE(SUM(payments.final_amount), 0) as revenue')
            ->selectRaw('COALESCE(SUM(confirmed_transactions.confirmed_amount), 0) as collected')
            // An overpayment must not erase the balance on a different financial record.
            ->selectRaw('COALESCE(SUM(CASE WHEN payments.final_amount > COALESCE(confirmed_transactions.confirmed_amount, 0) THEN payments.final_amount - COALESCE(confirmed_transactions.confirmed_amount, 0) ELSE 0 END), 0) as outstanding')
            ->first();
        $revenue = round((float) $financials->revenue, 2);
        $collected = round((float) $financials->collected, 2);
        $outstanding = round((float) $financials->outstanding, 2);

        $activePartyCount = InvitationParty::query()->where('is_active', true)->count();
        // Public RSVP submissions always set submitted_at, including a decline.
        $respondedPartyCount = InvitationParty::query()
            ->where('is_active', true)
            ->whereHas('rsvp', fn ($query) => $query->whereNotNull('submitted_at'))
            ->count();

        $upcomingEvents = Event::query()
            ->with(['customer', 'package', 'template:id,name'])
            ->withExists('publications')
            ->where('status', '!=', 'archived')
            ->whereDate('main_date', '>=', today())
            ->orderBy('main_date')
            ->orderBy('id')
            ->limit(6)
            ->get()
            ->map(fn (Event $event) => [
                'id' => $event->id,
                'title' => $event->title ?: 'Invitation setup',
                'client' => $event->customer?->name,
                'template_name' => $event->template?->name,
                'date' => $event->main_date?->format('M j, Y'),
                'type' => $event->event_type,
                'capacity' => $event->effectiveGuestCapacity(),
                'status' => str($event->invitationStatus())->headline()->toString(),
            ]);

        $attentionItems = Event::query()
            ->where('status', '!=', 'archived')
            ->whereDate('main_date', '>=', today())
            ->whereDoesntHave('publications')
            ->orderBy('main_date')
            ->orderBy('id')
            ->limit(5)
            ->get(['id', 'title'])
            ->map(fn (Event $event) => [
                'id' => $event->id,
                'title' => $event->title ?: 'Invitation setup',
                'message' => 'Invitation setup is not complete.',
            ]);

        return [
            'kpis' => [
                'clients' => Customer::count(),
                'events' => $eventCount,
                'live' => $liveEventCount,
                'setup' => $setupEventCount,
                'revenue' => $revenue,
                'collected' => $collected,
                'outstanding' => $outstanding,
                'rsvp_rate' => $activePartyCount ? (int) round($respondedPartyCount / $activePartyCount * 100) : 0,
            ],
            'revenue_trend' => $this->revenueTrend(),
            'event_status' => $this->breakdown(array_filter([
                'Live' => $liveEventCount,
                'Setup' => $setupEventCount,
                'Disabled' => $disabledEventCount,
                'Archived' => $archivedEventCount,
            ], fn (int $count): bool => $count > 0), $eventCount),
            'rsvp_breakdown' => $this->rsvpBreakdown($activePartyCount, $respondedPartyCount),
            'events_by_type' => Event::query()
                ->select('event_type')
                ->selectRaw('COUNT(*) as count')
                ->whereNotNull('event_type')
                ->where('event_type', '!=', '')
                ->groupBy('event_type')
                ->orderByDesc('count')
                ->orderBy('event_type')
                ->get()
                ->map(fn (Event $event) => ['label' => str($event->event_type)->headline()->toString(), 'count' => (int) $event->count])
                ->values()
                ->all(),
            'upcoming' => $upcomingEvents,
            'attention' => $attentionItems,
        ];
    }

    private function revenueTrend(): array
    {
        $periodStart = now()->startOfMonth()->subMonths(11);
        $periodEnd = now()->endOfMonth();
        $paymentMonth = $this->monthExpression('created_at');
        // A financial obligation belongs to the month its Payment record was created.
        $revenueByMonth = Payment::query()
            ->selectRaw("{$paymentMonth} as month, SUM(final_amount) as total")
            ->whereBetween('created_at', [$periodStart, $periodEnd])
            ->groupByRaw($paymentMonth)
            ->pluck('total', 'month');

        // Collected money belongs to its declared transaction date; when omitted, use the recorded date.
        $transactionDate = $this->transactionDateExpression();
        $transactionMonth = $this->monthExpression($transactionDate);
        $collectedByMonth = PaymentTransaction::query()
            ->selectRaw("{$transactionMonth} as month, SUM(amount) as total")
            ->where('status', 'confirmed')
            ->whereRaw("{$transactionDate} >= ? AND {$transactionDate} <= ?", [$periodStart->toDateString(), $periodEnd->toDateString()])
            ->groupByRaw($transactionMonth)
            ->pluck('total', 'month');

        $months = collect(range(0, 11))->map(function (int $offset) use ($periodStart, $revenueByMonth, $collectedByMonth): array {
            $month = $periodStart->copy()->addMonths($offset);
            $key = $month->format('Y-m');

            return [
                'label' => $month->format('M'),
                'revenue' => round((float) ($revenueByMonth[$key] ?? 0), 2),
                'collected' => round((float) ($collectedByMonth[$key] ?? 0), 2),
            ];
        });

        return [
            'labels' => $months->pluck('label')->all(),
            'revenue' => $months->pluck('revenue')->all(),
            'collected' => $months->pluck('collected')->all(),
        ];
    }

    private function rsvpBreakdown(int $activePartyCount, int $respondedPartyCount): array
    {
        $attending = InvitationParty::query()
            ->where('is_active', true)
            ->whereHas('rsvp', fn ($query) => $query->whereNotNull('submitted_at')->where('status', 'attending'))
            ->count();
        $declined = InvitationParty::query()
            ->where('is_active', true)
            ->whereHas('rsvp', fn ($query) => $query->whereNotNull('submitted_at')->where('status', 'not_attending'))
            ->count();

        return [
            'response_rate' => $activePartyCount ? (int) round($respondedPartyCount / $activePartyCount * 100) : 0,
            'items' => $this->breakdown([
                'Attending' => $attending,
                'Declined' => $declined,
                'Awaiting Response' => $activePartyCount - $attending - $declined,
            ], $activePartyCount),
        ];
    }

    private function breakdown(array $counts, int $total): array
    {
        return collect($counts)
            ->map(fn (int $count, string $label) => [
                'label' => $label,
                'count' => $count,
                'percentage' => $total ? (int) round($count / $total * 100) : 0,
            ])
            ->values()
            ->all();
    }

    private function monthExpression(string $column): string
    {
        return DB::connection()->getDriverName() === 'pgsql'
            ? "to_char({$column}, 'YYYY-MM')"
            : "strftime('%Y-%m', {$column})";
    }

    private function transactionDateExpression(): string
    {
        return DB::connection()->getDriverName() === 'pgsql'
            ? 'COALESCE(payment_date, created_at::date)'
            : 'COALESCE(payment_date, DATE(created_at))';
    }
}
