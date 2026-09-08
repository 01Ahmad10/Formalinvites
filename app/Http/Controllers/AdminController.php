<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Coupon;
use App\Models\Event;
use App\Models\EventPackage;
use App\Models\Payment;
use App\Models\RsvpPersonResponse;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AdminController extends Controller
{
    public function customers(Request $request): Response
    {
        $search = $request->string('search')->trim()->toString();
        $customers = Customer::query()
            ->with(['users' => fn ($query) => $query->where('role', 'customer')->orderBy('customer_account_role')->orderBy('id'), 'events' => fn ($query) => $query->with(['package', 'payments'])->withCount('publications')])
            ->withCount(['users' => fn ($query) => $query->where('role', 'customer'), 'events'])
            ->when($search, fn ($query) => $query->where(fn ($query) => $query->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%")->orWhere('phone', 'like', "%{$search}%")))
            ->latest()->paginate(25)->withQueryString();
        $customers->getCollection()->transform(fn (Customer $customer) => $this->customerForList($customer));

        return Inertia::render('Admin/Customers', [
            'customers' => $customers->items(),
            'packages' => EventPackage::query()->where('is_active', true)->capacityOrder()->get(['id','name','minimum_guests','maximum_guests','price']),
            'filters' => ['search' => $search],
            'pagination' => $this->pagination($customers),
            'hasCustomers' => Customer::query()->exists(),
        ]);
    }
    public function storeCustomer(Request $r): RedirectResponse { Customer::create($r->validate(['name'=>'required|string|max:255','contact_name'=>'nullable|string|max:255','email'=>'nullable|email','phone'=>'nullable|string|max:50','is_active'=>'boolean','allowed_events'=>'nullable|integer|min:0'])); return back()->with('success','Client created.'); }
    public function updateCustomer(Request $r, Customer $customer): RedirectResponse
    {
        $data = $r->validate(['name'=>'sometimes|required|string|max:255','contact_name'=>'sometimes|nullable|string|max:255','email'=>'sometimes|nullable|email','phone'=>'sometimes|nullable|string|max:50','is_active'=>'sometimes|boolean','allowed_events'=>'sometimes|integer|min:0']);
        DB::transaction(function () use ($customer, $data): void {
            $customer = Customer::query()->lockForUpdate()->findOrFail($customer->id);
            if (array_key_exists('allowed_events', $data) && $data['allowed_events'] < $customer->usedEvents()) {
                throw ValidationException::withMessages(['allowed_events' => "Allowed invitations cannot be lower than this Client's {$customer->usedEvents()} existing Events."]);
            }
            $customer->update($data);
        });
        return back()->with('success','Client updated.');
    }
    public function storeClient(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required','string','max:255'], 'phone' => ['nullable','string','max:50'], 'guest_capacity' => ['required','integer','min:1'], 'allowed_events' => ['required','integer','min:1'],
            'primary_name' => ['required','string','max:255'], 'primary_email' => ['required','email','unique:users,email'], 'primary_password' => ['required','string','min:8'],
            'secondary_name' => ['nullable','string','max:255'], 'secondary_email' => ['nullable','email','unique:users,email'], 'secondary_password' => ['nullable','string','min:8'],
        ]);
        $package = $this->packageForCapacity((int) $data['guest_capacity']);
        $secondary = filled($data['secondary_name'] ?? null) || filled($data['secondary_email'] ?? null) || filled($data['secondary_password'] ?? null);
        if ($secondary && (! filled($data['secondary_name'] ?? null) || ! filled($data['secondary_email'] ?? null) || ! filled($data['secondary_password'] ?? null))) throw ValidationException::withMessages(['secondary_name' => 'Complete all second login fields or leave them all blank.']);

        DB::transaction(function () use ($data, $secondary, $package): void {
            // These legacy Customer fields remain populated without making Admin enter the same contact details twice.
            $customer = Customer::create(['name'=>$data['name'],'contact_name'=>$data['primary_name'],'email'=>$data['primary_email'],'phone'=>$data['phone'] ?? null,'is_active'=>true,'allowed_events'=>$data['allowed_events']]);
            $event = $customer->events()->create(['event_package_id'=>$package->id, 'guest_capacity'=>$data['guest_capacity'], 'event_timezone'=>config('app.timezone'), 'status'=>'draft']);
            $primary = User::create(['customer_id'=>$customer->id,'name'=>$data['primary_name'],'email'=>$data['primary_email'],'password'=>Hash::make($data['primary_password']),'role'=>'customer','customer_account_role'=>'primary']);
            $event->members()->attach($primary->id, ['role'=>'owner']);
            if ($secondary) { $second = User::create(['customer_id'=>$customer->id,'name'=>$data['secondary_name'],'email'=>$data['secondary_email'],'password'=>Hash::make($data['secondary_password']),'role'=>'customer','customer_account_role'=>'secondary']); $event->members()->attach($second->id, ['role'=>'editor']); }
            return;
        });
        return to_route('admin.customers.index')->with('success', 'Client, login account, package, and Event setup shell created.');
    }

    public function showCustomer(Customer $customer): Response
    {
        $customer->load([
            'users' => fn ($query) => $query->where('role','customer')->orderBy('customer_account_role'),
            'events.package',
            'events.payments',
            'events' => fn ($query) => $query
                ->withCount([
                    'publications',
                    'invitationParties as families_count' => fn ($parties) => $parties->where('is_active', true),
                    'invitationParties as responded_families_count' => fn ($parties) => $parties->where('is_active', true)->whereHas('rsvp', fn ($rsvp) => $rsvp->whereNotNull('submitted_at')),
                ])
                ->withSum(['invitationParties as allocated_capacity' => fn ($parties) => $parties->where('is_active', true)], 'maximum_party_size')
                ->orderBy('main_date')->orderBy('id'),
        ]);
        $attendees = RsvpPersonResponse::query()
            ->selectRaw('invitation_parties.event_id, COUNT(*) as confirmed_attendees')
            ->join('rsvps', 'rsvps.id', '=', 'rsvp_person_responses.rsvp_id')
            ->join('invitation_parties', 'invitation_parties.id', '=', 'rsvps.invitation_party_id')
            ->whereIn('invitation_parties.event_id', $customer->events->pluck('id'))
            ->where('invitation_parties.is_active', true)->whereNotNull('rsvps.submitted_at')->where('rsvp_person_responses.is_attending', true)
            ->groupBy('invitation_parties.event_id')->pluck('confirmed_attendees', 'invitation_parties.event_id');
        return Inertia::render('Admin/ClientDetails', ['customer' => $customer, 'allowance' => $customer->allowanceSummary(), 'customerUsers' => $customer->users, 'events' => $customer->events->map(fn (Event $event) => ['id'=>$event->id,'title'=>$event->title,'event_type'=>$event->event_type,'date'=>$event->main_date?->format('M j, Y'),'invitation_status'=>$event->invitationStatus(),'guest_capacity'=>$event->effectiveGuestCapacity(),'allocated_capacity'=>(int) ($event->allocated_capacity ?? 0),'families_count'=>(int) $event->families_count,'responded_families_count'=>(int) $event->responded_families_count,'confirmed_attendees'=>(int) ($attendees[$event->id] ?? 0),'package'=>$event->package?->only(['name','minimum_guests','maximum_guests','price']),'finance'=>['has_record'=>$event->payments->isNotEmpty(),'final_amount'=>(float) $event->payments->sum('final_amount'),'paid_amount'=>(float) $event->payments->sum('paid_amount'),'remaining_amount'=>$event->payments->sum(fn ($payment) => $payment->remainingAmount())]])->values(), 'canAddSecondLogin' => $customer->users->count() < 2]);
    }

    public function storeSecondLogin(Request $request, Customer $customer): RedirectResponse
    {
        $data = $request->validate(['name'=>['required','string','max:255'],'email'=>['required','email','unique:users,email'],'password'=>['required','string','min:8']]);
        DB::transaction(function () use ($customer, $data): void {
            $customer = Customer::query()->lockForUpdate()->findOrFail($customer->id);
            $this->ensureCustomerAccountSpace($customer->id, true);
            $user = User::create([...$data,'customer_id'=>$customer->id,'role'=>'customer','customer_account_role'=>'secondary','password'=>Hash::make($data['password'])]);
            $customer->events()->each(fn (Event $event) => $event->members()->syncWithoutDetaching([$user->id => ['role'=>'editor']]));
        });
        return back()->with('success','Second client login added.');
    }

    public function resetCustomerUserPassword(Request $request, User $user): RedirectResponse
    {
        abort_unless($user->role === 'customer', 404);
        $data = $request->validate(['password'=>['required','string','min:8']]);
        $user->update(['password'=>Hash::make($data['password'])]);
        return back()->with('success','Client password reset.');
    }

    private function ensureCustomerAccountSpace(int $customerId, bool $lock = false): void
    {
        $accounts = User::query()->where('customer_id',$customerId)->where('role','customer');
        if ($lock) $accounts->lockForUpdate();
        if ($accounts->count() >= 2) throw ValidationException::withMessages(['customer_id'=>'A Client can have at most two login accounts.']);
    }

    private function packageForCapacity(int $capacity): EventPackage
    {
        $matches = EventPackage::query()->where('is_active', true)->where('minimum_guests', '<=', $capacity)->where('maximum_guests', '>=', $capacity)->get();
        if ($matches->isEmpty()) throw ValidationException::withMessages(['guest_capacity' => 'No active Package covers this guest capacity. Review the Package ranges.']);
        if ($matches->count() > 1) throw ValidationException::withMessages(['guest_capacity' => 'More than one active Package covers this guest capacity. Resolve the overlapping Package ranges first.']);
        return $matches->first();
    }

    private function customerForList(Customer $customer): array
    {
        $users = $customer->users;
        $primary = $users->firstWhere('customer_account_role', 'primary') ?? $users->first();
        $secondary = $users->firstWhere('customer_account_role', 'secondary') ?? $users->reject(fn (User $user) => $primary && $user->is($primary))->first();
        $events = $customer->events;
        $event = $events->count() === 1 ? $events->first() : null;
        $payment = $event?->payments->first();

        return [
            'id' => $customer->id, 'name' => $customer->name,
            'primary_login' => $primary ? ['name' => $primary->name, 'email' => $primary->email] : null,
            'second_login' => $secondary ? ['name' => $secondary->name, 'email' => $secondary->email] : null,
            'event_count' => $events->count(),
            'allowance' => $customer->allowanceSummary(),
            'event' => $event ? ['title' => $event->title, 'guest_capacity' => $event->effectiveGuestCapacity(), 'invitation_status' => $event->invitationStatus(), 'payment_status' => match ($payment?->status) { 'paid' => 'Paid', 'partially_paid' => 'Partially Paid', default => 'Unpaid' }] : null,
        ];
    }
    public function packages(Request $request): Response { $search = $request->string('search')->trim()->toString(); $active = $request->string('active')->toString(); $packages = EventPackage::query()->when($search, fn ($query) => $query->where('name', 'like', "%{$search}%"))->when(in_array($active, ['active', 'inactive'], true), fn ($query) => $query->where('is_active', $active === 'active'))->capacityOrder()->get(); return Inertia::render('Admin/Packages', ['packages'=>$packages, 'filters' => ['search' => $search, 'active' => $active]]); }
    public function storePackage(Request $r): RedirectResponse { EventPackage::create($r->validate(['name'=>'required|string|max:255','minimum_guests'=>'required|integer|min:1','maximum_guests'=>'required|integer|gte:minimum_guests','price'=>'nullable|numeric|min:0','is_active'=>'boolean'])); return back()->with('success','Package created.'); }
    public function updatePackage(Request $r, EventPackage $package): RedirectResponse { $package->update($r->validate(['name'=>'required|string|max:255','minimum_guests'=>'required|integer|min:1','maximum_guests'=>'required|integer|gte:minimum_guests','price'=>'nullable|numeric|min:0','is_active'=>'boolean'])); return back()->with('success','Package updated.'); }
    public function payments(Request $request): Response
    {
        $search = $request->string('search')->trim()->toString();
        $status = $request->string('status')->toString();
        $customerId = $request->integer('customer_id');
        $paymentQuery = Payment::query()
            ->when($search, fn ($query) => $query->where(fn ($query) => $query->where('coupon_code', 'like', "%{$search}%")->orWhereHas('transactions', fn ($transactions) => $transactions->where('reference', 'like', "%{$search}%"))->orWhereHas('customer', fn ($customer) => $customer->where('name', 'like', "%{$search}%"))->orWhereHas('event', fn ($event) => $event->where('title', 'like', "%{$search}%"))))
            ->when(in_array($status, Payment::STATUSES, true), fn ($query) => $query->where('status', $status))
            ->when($customerId, fn ($query) => $query->where('customer_id', $customerId));
        $summary = (clone $paymentQuery)->selectRaw("COALESCE(SUM(final_amount), 0) as total_final_amount, COALESCE(SUM(paid_amount), 0) as total_paid_amount, COALESCE(SUM(CASE WHEN final_amount > paid_amount THEN final_amount - paid_amount ELSE 0 END), 0) as total_remaining_amount, SUM(CASE WHEN status = 'unpaid' THEN 1 ELSE 0 END) as unpaid_count, SUM(CASE WHEN status = 'partially_paid' THEN 1 ELSE 0 END) as partially_paid_count, SUM(CASE WHEN status = 'paid' THEN 1 ELSE 0 END) as paid_count")->first();
        $payments = $paymentQuery->with(['customer:id,name', 'event:id,title', 'package:id,name', 'latestTransaction'])
            ->latest()->paginate(25)->withQueryString();

        $today = now()->toDateString();
        $availableCoupons = Coupon::query()->where('is_active', true)
            ->where(fn ($query) => $query->whereNull('starts_at')->orWhereDate('starts_at', '<=', $today))
            ->where(fn ($query) => $query->whereNull('expires_at')->orWhereDate('expires_at', '>=', $today))
            ->withCount('payments')->orderBy('code')->get(['id', 'code', 'discount_type', 'discount_value', 'usage_limit'])
            ->filter(fn (Coupon $coupon) => $coupon->usage_limit === null || $coupon->payments_count < $coupon->usage_limit)->values();

        $customers = Customer::where('is_active', true)->orderBy('name')->get(['id', 'name']);

        return Inertia::render('Admin/Payments', ['payments' => $payments->items(), 'summary' => $summary, 'customers' => $customers, 'events' => Event::with('package:id,name,price')->latest()->get(['id', 'customer_id', 'title', 'event_package_id']), 'coupons' => $availableCoupons, 'filters' => ['search' => $search, 'status' => $status, 'customer_id' => $customerId ?: ''], 'pagination' => $this->pagination($payments), 'hasClients' => $customers->isNotEmpty()]);
    }

    public function storePayment(Request $request): RedirectResponse
    {
        $data = $request->validate(['customer_id' => ['required', 'exists:customers,id'], 'event_id' => ['nullable', 'exists:events,id'], 'event_package_id' => ['nullable', 'exists:event_packages,id'], 'original_amount' => ['required', 'numeric', 'min:0'], 'coupon_id' => ['nullable', 'exists:coupons,id'], 'discount_type' => ['nullable', Rule::in(Coupon::DISCOUNT_TYPES)], 'discount_value' => ['nullable', 'numeric', 'min:0', 'required_with:discount_type'], 'paid_amount' => ['nullable', 'numeric', 'min:0'], 'payment_method' => ['nullable', 'string', 'max:100'], 'reference' => ['nullable', 'string', 'max:255'], 'payment_date' => ['nullable', 'date'], 'notes' => ['nullable', 'string']]);

        if ($data['event_id'] ?? null) {
            $event = Event::findOrFail($data['event_id']);
            if ((int) $event->customer_id !== (int) $data['customer_id']) throw ValidationException::withMessages(['event_id' => 'The selected event does not belong to this customer.']);
            if (Payment::where('event_id', $event->id)->exists()) throw ValidationException::withMessages(['event_id' => 'This event already has a financial record.']);
            if ($event->event_package_id === null && ($data['event_package_id'] ?? null)) throw ValidationException::withMessages(['event_package_id' => 'The selected event has no assigned package.']);
            if ($event->event_package_id !== null && isset($data['event_package_id']) && (int) $data['event_package_id'] !== (int) $event->event_package_id) throw ValidationException::withMessages(['event_package_id' => 'The selected package does not match the event package.']);
            $data['event_package_id'] = $event->event_package_id;
        } elseif ($data['event_package_id'] ?? null) {
            throw ValidationException::withMessages(['event_package_id' => 'A package can only be assigned through a selected event.']);
        }

        $originalAmount = round((float) $data['original_amount'], 2);
        if (($data['coupon_id'] ?? null) && (($data['discount_type'] ?? null) || ($data['discount_value'] ?? null) !== null)) throw ValidationException::withMessages(['coupon_id' => 'Choose either a coupon or a manual discount, not both.']);

        $initialAmount = round((float) ($data['paid_amount'] ?? 0), 2);
        $payment = DB::transaction(function () use ($data, $originalAmount, $initialAmount, $request): Payment {
            $coupon = null;
            if ($data['coupon_id'] ?? null) {
                // Serializing use of this coupon makes usage_limit reliable under concurrent Admin requests.
                $coupon = Coupon::query()->lockForUpdate()->findOrFail($data['coupon_id']);
                if (! $coupon->isAvailableFor()) throw ValidationException::withMessages(['coupon_id' => 'This coupon is inactive, outside its valid dates, or has reached its usage limit.']);
                $discountDetails = $this->calculateDiscount($originalAmount, $coupon->discount_type, (float) $coupon->discount_value);
            } elseif ($data['discount_type'] ?? null) {
                $discountDetails = $this->calculateDiscount($originalAmount, $data['discount_type'], (float) $data['discount_value']);
            } else {
                if (($data['discount_value'] ?? null) !== null) throw ValidationException::withMessages(['discount_type' => 'Choose a discount type before entering a discount value.']);
                $discountDetails = ['amount' => 0.0, 'final_amount' => $originalAmount];
            }
            if ($initialAmount > $discountDetails['final_amount']) throw ValidationException::withMessages(['paid_amount' => 'The initial payment cannot exceed the calculated final amount.']);

            $payment = Payment::create([
                'customer_id' => $data['customer_id'], 'event_id' => $data['event_id'] ?? null, 'event_package_id' => $data['event_package_id'] ?? null,
                'coupon_id' => $coupon?->id, 'coupon_code' => $coupon?->code,
                'original_amount' => $originalAmount, 'discount' => $discountDetails['amount'], 'discount_type' => $coupon?->discount_type ?? ($data['discount_type'] ?? null), 'discount_value' => $coupon?->discount_value ?? ($data['discount_value'] ?? null), 'final_amount' => $discountDetails['final_amount'], 'paid_amount' => 0, 'status' => 'unpaid',
            ]);
            if ($initialAmount > 0) $payment->transactions()->create(['amount' => $initialAmount, 'payment_method' => $data['payment_method'] ?? null, 'reference' => $data['reference'] ?? null, 'payment_date' => $data['payment_date'] ?? null, 'notes' => $data['notes'] ?? null, 'created_by' => $request->user()->id, 'status' => 'confirmed']);
            $payment->refreshTotals();
            return $payment;
        });

        return to_route('admin.payments.show', $payment)->with('success', 'Financial record created.');
    }

    public function showPayment(Payment $payment): Response
    {
        abort_unless(request()->user()->can('view', $payment), 403);
        $payment->load(['customer', 'event', 'package', 'coupon', 'transactions' => fn ($query) => $query->with('recordedBy:id,name')->orderBy('payment_date')->orderBy('id')]);

        return Inertia::render('Admin/PaymentShow', ['payment' => $payment, 'coupons' => Coupon::where('is_active', true)->orderBy('code')->get()]);
    }

    public function storePaymentTransaction(Request $request, Payment $payment): RedirectResponse
    {
        abort_unless($request->user()->can('update', $payment), 403);
        $data = $request->validate(['amount' => ['required', 'numeric', 'min:0.01'], 'payment_method' => ['nullable', 'string', 'max:100'], 'reference' => ['nullable', 'string', 'max:255'], 'payment_date' => ['nullable', 'date'], 'notes' => ['nullable', 'string']]);

        DB::transaction(function () use ($payment, $data, $request): void {
            $payment = Payment::query()->lockForUpdate()->findOrFail($payment->id);
            if ((float) $data['amount'] > $payment->remainingAmount()) throw ValidationException::withMessages(['amount' => 'This payment exceeds the remaining balance. Record only the remaining balance or adjust the financial record first.']);
            $payment->transactions()->create([...$data, 'created_by' => $request->user()->id, 'status' => 'confirmed']);
            $payment->refreshTotals();
        });

        return back()->with('success', 'Payment transaction recorded.');
    }

    public function updatePaymentDiscount(Request $request, Payment $payment): RedirectResponse
    {
        abort_unless($request->user()->can('update', $payment), 403);
        $data = $request->validate(['discount_type' => ['required', Rule::in(Coupon::DISCOUNT_TYPES)], 'discount_value' => ['required', 'numeric', 'min:0']]);
        $this->applyDiscount($payment, $data['discount_type'], (float) $data['discount_value']);
        $payment->update(['coupon_id' => null, 'coupon_code' => null]);

        return back()->with('success', 'Discount updated. Any previously applied coupon was removed.');
    }

    public function applyPaymentCoupon(Request $request, Payment $payment): RedirectResponse
    {
        abort_unless($request->user()->can('update', $payment), 403);
        $data = $request->validate(['coupon_id' => ['required', 'exists:coupons,id']]);
        DB::transaction(function () use ($data, $payment): void {
            $payment = Payment::query()->lockForUpdate()->findOrFail($payment->id);
            $coupon = Coupon::query()->lockForUpdate()->findOrFail($data['coupon_id']);
            if (! $coupon->isAvailableFor($payment)) throw ValidationException::withMessages(['coupon_id' => 'This coupon is inactive, outside its valid dates, or has reached its usage limit.']);
            $this->applyDiscount($payment, $coupon->discount_type, (float) $coupon->discount_value);
            $payment->update(['coupon_id' => $coupon->id, 'coupon_code' => $coupon->code]);
        });

        return back()->with('success', 'Coupon applied. Its calculated discount has been saved on this financial record.');
    }

    public function coupons(): Response
    {
        $coupons = Coupon::query()->latest()->paginate(25)->withQueryString();
        return Inertia::render('Admin/Coupons', ['coupons' => $coupons->items(), 'pagination' => $this->pagination($coupons), 'discountTypes' => Coupon::DISCOUNT_TYPES]);
    }

    public function storeCoupon(Request $request): RedirectResponse
    {
        Coupon::create($this->validatedCoupon($request));
        return back()->with('success', 'Coupon created.');
    }

    public function updateCoupon(Request $request, Coupon $coupon): RedirectResponse
    {
        $coupon->update($this->validatedCoupon($request, $coupon));
        return back()->with('success', 'Coupon updated. Existing applied discounts are unchanged.');
    }

    public function confirmPayment(Payment $payment): RedirectResponse
    {
        abort_unless(request()->user()->can('update', $payment), 403);
        $payment->refreshTotals();
        return back()->with('success', 'Financial totals refreshed from payment transactions.');
    }

    private function applyDiscount(Payment $payment, string $type, float $value): void
    {
        $details = $this->calculateDiscount((float) $payment->original_amount, $type, $value);
        if ($details['final_amount'] < (float) $payment->paid_amount) throw ValidationException::withMessages(['discount_value' => 'The discount would make the final amount lower than payments already received.']);

        $payment->update(['discount_type' => $type, 'discount_value' => $value, 'discount' => $details['amount'], 'final_amount' => $details['final_amount']]);
        $payment->refreshTotals();
    }

    private function calculateDiscount(float $originalAmount, string $type, float $value): array
    {
        if ($type === 'percentage' && $value > 100) throw ValidationException::withMessages(['discount_value' => 'A percentage discount cannot exceed 100%.']);
        if ($type === 'fixed' && $value > $originalAmount) throw ValidationException::withMessages(['discount_value' => 'A fixed discount cannot exceed the original amount.']);

        $discount = round($type === 'percentage' ? $originalAmount * ($value / 100) : $value, 2);
        return ['amount' => $discount, 'final_amount' => max(round($originalAmount - $discount, 2), 0)];
    }

    private function validatedCoupon(Request $request, ?Coupon $coupon = null): array
    {
        $data = $request->validate(['code' => ['required', 'string', 'max:100', Rule::unique('coupons', 'code')->ignore($coupon)], 'description' => ['nullable', 'string', 'max:255'], 'discount_type' => ['required', Rule::in(Coupon::DISCOUNT_TYPES)], 'discount_value' => ['required', 'numeric', 'min:0'], 'is_active' => ['boolean'], 'starts_at' => ['nullable', 'date'], 'expires_at' => ['nullable', 'date', 'after_or_equal:starts_at'], 'usage_limit' => ['nullable', 'integer', 'min:1']]);
        if ($data['discount_type'] === 'percentage' && (float) $data['discount_value'] > 100) throw ValidationException::withMessages(['discount_value' => 'A percentage discount cannot exceed 100%.']);
        return $data;
    }

    private function pagination($paginator): array
    {
        return [
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'total' => $paginator->total(),
            'prev_page_url' => $paginator->previousPageUrl(),
            'next_page_url' => $paginator->nextPageUrl(),
        ];
    }
}
