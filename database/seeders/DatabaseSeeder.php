<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Event;
use App\Models\EventPackage;
use App\Models\Payment;
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
        $admin = User::create(['name' => 'Admin User', 'email' => 'admin@formalevites.test', 'role' => 'admin', 'password' => $password]);
        User::create(['name' => 'Support User', 'email' => 'support@formalevites.test', 'role' => 'support', 'password' => $password]);
        $first = Customer::create(['name' => 'Cedar Celebrations', 'contact_name' => 'Maya Haddad', 'email' => 'maya@example.test', 'is_active' => true]);
        $second = Customer::create(['name' => 'Olive Events', 'contact_name' => 'Karim Nasser', 'email' => 'karim@example.test', 'is_active' => true]);
        $maya = User::create(['name' => 'Maya Haddad', 'email' => 'maya@formalevites.test', 'role' => 'customer', 'customer_id' => $first->id, 'password' => $password]);
        $karim = User::create(['name' => 'Karim Nasser', 'email' => 'karim@formalevites.test', 'role' => 'customer', 'customer_id' => $second->id, 'password' => $password]);
        $packages = collect([[1,50,25],[51,100,55],[101,150,80],[151,200,100],[201,250,130],[251,300,180],[301,350,200],[351,400,280],[401,450,null],[451,500,350]])->map(fn ($row) => EventPackage::create(['name' => "{$row[0]}-{$row[1]} guests", 'minimum_guests' => $row[0], 'maximum_guests' => $row[1], 'price' => $row[2], 'is_active' => true]));
        $wedding = Event::create(['customer_id' => $first->id, 'event_package_id' => $packages[1]->id, 'title' => 'Maya and Elias Wedding', 'event_type' => 'wedding', 'host_name' => 'Maya Haddad', 'main_date' => now()->addMonths(3)->toDateString(), 'venue' => 'Cedar Hall', 'status' => 'approved']);
        $birthday = Event::create(['customer_id' => $first->id, 'event_package_id' => $packages[0]->id, 'title' => 'Nour Birthday', 'event_type' => 'birthday', 'host_name' => 'Maya Haddad', 'main_date' => now()->addMonth()->toDateString(), 'status' => 'draft']);
        $engagement = Event::create(['customer_id' => $second->id, 'event_package_id' => $packages[2]->id, 'title' => 'Karim and Rania Engagement', 'event_type' => 'engagement', 'host_name' => 'Karim Nasser', 'main_date' => now()->addMonths(2)->toDateString(), 'status' => 'submitted']);
        $wedding->members()->attach($maya, ['role' => 'owner']); $birthday->members()->attach($maya, ['role' => 'owner']); $engagement->members()->attach($karim, ['role' => 'owner']);
        Payment::create(['customer_id' => $first->id, 'event_id' => $wedding->id, 'event_package_id' => $packages[1]->id, 'original_amount' => 55, 'discount' => 5, 'final_amount' => 50, 'paid_amount' => 50, 'payment_method' => 'cash', 'reference' => 'CASH-001', 'payment_date' => now()->toDateString(), 'status' => 'confirmed', 'notes' => 'Local development payment']);
    }
}
