<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Coupon;
use App\Models\Event;
use App\Models\EventPackage;
use App\Models\Payment;
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
    public function customers(Request $request): Response { $search = $request->string('search')->trim()->toString(); $customers = Customer::query()->withCount(['users', 'events'])->when($search, fn ($query) => $query->where(fn ($query) => $query->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%")->orWhere('phone', 'like', "%{$search}%")))->latest()->get(); return Inertia::render('Admin/Customers', ['customers' => $customers, 'filters' => ['search' => $search]]); }
    public function storeCustomer(Request $r): RedirectResponse { Customer::create($r->validate(['name'=>'required|string|max:255','contact_name'=>'nullable|string|max:255','email'=>'nullable|email','phone'=>'nullable|string|max:50','is_active'=>'boolean'])); return back()->with('success','Customer created.'); }
    public function updateCustomer(Request $r, Customer $customer): RedirectResponse { $customer->update($r->validate(['name'=>'required|string|max:255','contact_name'=>'nullable|string|max:255','email'=>'nullable|email','phone'=>'nullable|string|max:50','is_active'=>'boolean'])); return back()->with('success','Customer updated.'); }
    public function users(Request $request): Response { $search = $request->string('search')->trim()->toString(); $users = User::with('customer')->where('role','customer')->when($search, fn ($query) => $query->where(fn ($query) => $query->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%")->orWhereHas('customer', fn ($customer) => $customer->where('name', 'like', "%{$search}%"))))->latest()->get(); return Inertia::render('Admin/Users', ['users'=>$users, 'customers'=>Customer::where('is_active',true)->get(), 'filters' => ['search' => $search]]); }
    public function storeUser(Request $r): RedirectResponse { $data=$r->validate(['customer_id'=>'required|exists:customers,id','name'=>'required|string|max:255','email'=>'required|email|unique:users,email','password'=>'required|string|min:8']); User::create([...$data,'role'=>'customer','password'=>Hash::make($data['password'])]); return back()->with('success','Customer user created.'); }
    public function packages(Request $request): Response { $search = $request->string('search')->trim()->toString(); $active = $request->string('active')->toString(); $packages = EventPackage::query()->when($search, fn ($query) => $query->where('name', 'like', "%{$search}%"))->when(in_array($active, ['active', 'inactive'], true), fn ($query) => $query->where('is_active', $active === 'active'))->capacityOrder()->get(); return Inertia::render('Admin/Packages', ['packages'=>$packages, 'filters' => ['search' => $search, 'active' => $active]]); }
    public function storePackage(Request $r): RedirectResponse { EventPackage::create($r->validate(['name'=>'required|string|max:255','minimum_guests'=>'required|integer|min:1','maximum_guests'=>'required|integer|gte:minimum_guests','price'=>'nullable|numeric|min:0','is_active'=>'boolean'])); return back()->with('success','Package created.'); }
    public function updatePackage(Request $r, EventPackage $package): RedirectResponse { $package->update($r->validate(['name'=>'required|string|max:255','minimum_guests'=>'required|integer|min:1','maximum_guests'=>'required|integer|gte:minimum_guests','price'=>'nullable|numeric|min:0','is_active'=>'boolean'])); return back()->with('success','Package updated.'); }
    public function payments(Request $request): Response
    {
        $search = $request->string('search')->trim()->toString();
        $status = $request->string('status')->toString();
        $customerId = $request->integer('customer_id');
        $payments = Payment::with(['customer', 'event', 'package', 'latestTransaction'])
            ->when($search, fn ($query) => $query->where(fn ($query) => $query->where('coupon_code', 'like', "%{$search}%")->orWhereHas('transactions', fn ($transactions) => $transactions->where('reference', 'like', "%{$search}%"))->orWhereHas('customer', fn ($customer) => $customer->where('name', 'like', "%{$search}%"))->orWhereHas('event', fn ($event) => $event->where('title', 'like', "%{$search}%"))))
            ->when(in_array($status, Payment::STATUSES, true), fn ($query) => $query->where('status', $status))
            ->when($customerId, fn ($query) => $query->where('customer_id', $customerId))
            ->latest()->get();
        $summary = [
            'total_final_amount' => $payments->sum(fn ($payment) => (float) $payment->final_amount),
            'total_paid_amount' => $payments->sum(fn ($payment) => (float) $payment->paid_amount),
            'total_remaining_amount' => $payments->sum(fn ($payment) => $payment->remainingAmount()),
            'unpaid_count' => $payments->where('status', 'unpaid')->count(),
            'partially_paid_count' => $payments->where('status', 'partially_paid')->count(),
            'paid_count' => $payments->where('status', 'paid')->count(),
        ];

        $availableCoupons = Coupon::where('is_active', true)->orderBy('code')->get()->filter(fn (Coupon $coupon) => $coupon->isAvailableFor())->values();

        return Inertia::render('Admin/Payments', ['payments' => $payments, 'summary' => $summary, 'customers' => Customer::where('is_active', true)->get(), 'events' => Event::with('package')->latest()->get(), 'coupons' => $availableCoupons, 'filters' => ['search' => $search, 'status' => $status, 'customer_id' => $customerId ?: '']]);
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

        $coupon = null;
        if ($data['coupon_id'] ?? null) {
            $coupon = Coupon::findOrFail($data['coupon_id']);
            if (! $coupon->isAvailableFor()) throw ValidationException::withMessages(['coupon_id' => 'This coupon is inactive, outside its valid dates, or has reached its usage limit.']);
            $discountDetails = $this->calculateDiscount($originalAmount, $coupon->discount_type, (float) $coupon->discount_value);
        } elseif ($data['discount_type'] ?? null) {
            $discountDetails = $this->calculateDiscount($originalAmount, $data['discount_type'], (float) $data['discount_value']);
        } else {
            if (($data['discount_value'] ?? null) !== null) throw ValidationException::withMessages(['discount_type' => 'Choose a discount type before entering a discount value.']);
            $discountDetails = ['amount' => 0.0, 'final_amount' => $originalAmount];
        }

        $initialAmount = round((float) ($data['paid_amount'] ?? 0), 2);
        if ($initialAmount > $discountDetails['final_amount']) throw ValidationException::withMessages(['paid_amount' => 'The initial payment cannot exceed the calculated final amount.']);

        $payment = DB::transaction(function () use ($data, $originalAmount, $initialAmount, $discountDetails, $coupon, $request): Payment {
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
        $coupon = Coupon::findOrFail($data['coupon_id']);
        if (! $coupon->isAvailableFor($payment)) throw ValidationException::withMessages(['coupon_id' => 'This coupon is inactive, outside its valid dates, or has reached its usage limit.']);

        $this->applyDiscount($payment, $coupon->discount_type, (float) $coupon->discount_value);
        $payment->update(['coupon_id' => $coupon->id, 'coupon_code' => $coupon->code]);

        return back()->with('success', 'Coupon applied. Its calculated discount has been saved on this financial record.');
    }

    public function coupons(): Response
    {
        return Inertia::render('Admin/Coupons', ['coupons' => Coupon::latest()->get(), 'discountTypes' => Coupon::DISCOUNT_TYPES]);
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
}
