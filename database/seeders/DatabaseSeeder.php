<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Event;
use App\Models\EventPackage;
use App\Models\Payment;
use App\Models\Coupon;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $password = Hash::make('password');
        $admin = User::firstOrCreate(['email' => 'admin@formalevites.test'], ['name' => 'Admin User', 'role' => 'admin', 'password' => $password]);
        User::firstOrCreate(['email' => 'support@formalevites.test'], ['name' => 'Support User', 'role' => 'support', 'password' => $password]);
        $first = Customer::firstOrCreate(['name' => 'Cedar Celebrations'], ['contact_name' => 'Maya Haddad', 'email' => 'maya@example.test', 'is_active' => true]);
        $second = Customer::firstOrCreate(['name' => 'Olive Events'], ['contact_name' => 'Karim Nasser', 'email' => 'karim@example.test', 'is_active' => true]);
        $maya = User::firstOrCreate(['email' => 'maya@formalevites.test'], ['name' => 'Maya Haddad', 'role' => 'customer', 'customer_id' => $first->id, 'password' => $password]);
        $karim = User::firstOrCreate(['email' => 'karim@formalevites.test'], ['name' => 'Karim Nasser', 'role' => 'customer', 'customer_id' => $second->id, 'password' => $password]);
        $packages = collect([[1,50,25],[51,100,55],[101,150,80],[151,200,100],[201,250,130],[251,300,180],[301,350,200],[351,400,280],[401,450,null],[451,500,350]])->map(fn ($row) => EventPackage::firstOrCreate(['name' => "{$row[0]}-{$row[1]} guests"], ['minimum_guests' => $row[0], 'maximum_guests' => $row[1], 'price' => $row[2], 'is_active' => true]));
        $wedding = Event::firstOrCreate(['title' => 'Maya and Elias Wedding'], ['customer_id' => $first->id, 'event_package_id' => $packages[1]->id, 'event_type' => 'wedding', 'host_name' => 'Maya Haddad', 'main_date' => now()->addMonths(3)->toDateString(), 'venue' => 'Cedar Hall', 'status' => 'approved']);
        $birthday = Event::firstOrCreate(['title' => 'Nour Birthday'], ['customer_id' => $first->id, 'event_package_id' => $packages[0]->id, 'event_type' => 'birthday', 'host_name' => 'Maya Haddad', 'main_date' => now()->addMonth()->toDateString(), 'status' => 'draft']);
        $engagement = Event::firstOrCreate(['title' => 'Karim and Rania Engagement'], ['customer_id' => $second->id, 'event_package_id' => $packages[2]->id, 'event_type' => 'engagement', 'host_name' => 'Karim Nasser', 'main_date' => now()->addMonths(2)->toDateString(), 'status' => 'submitted']);
        $wedding->members()->syncWithoutDetaching([$maya->id => ['role' => 'owner']]);
        $birthday->members()->syncWithoutDetaching([$maya->id => ['role' => 'owner']]);
        $engagement->members()->syncWithoutDetaching([$karim->id => ['role' => 'owner']]);
        Coupon::firstOrCreate(['code' => 'WELCOME10'], ['description' => 'Development welcome coupon', 'discount_type' => 'percentage', 'discount_value' => 10, 'is_active' => true]);
        $payment = Payment::firstOrCreate(['event_id' => $wedding->id], ['customer_id' => $first->id, 'event_package_id' => $packages[1]->id, 'original_amount' => 55, 'discount' => 5, 'discount_type' => 'fixed', 'discount_value' => 5, 'final_amount' => 50, 'paid_amount' => 0, 'status' => 'unpaid']);
        if (! $payment->transactions()->exists() && (float) $payment->paid_amount === 0.0) {
            $payment->transactions()->create(['amount' => 50, 'payment_method' => 'cash', 'reference' => 'CASH-001', 'payment_date' => now()->toDateString(), 'notes' => 'Local development payment', 'created_by' => $admin->id, 'status' => 'confirmed']);
            $payment->refreshTotals();
        }
    }
}
