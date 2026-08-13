<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Event;
use App\Models\EventActivity;
use App\Models\EventPackage;
use App\Models\Payment;
use App\Models\Coupon;
use App\Models\InvitationParty;
use App\Models\EventMealOption;
use App\Models\User;
use App\Models\Template;
use App\Support\InvitationTemplateSettings;
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
        $elegant = Template::firstOrCreate(['slug' => 'elegant-classic'], ['name' => 'Elegant Classic', 'description' => 'A timeless formal invitation layout.', 'category' => 'formal', 'component_key' => 'elegant-classic', 'supported_event_types' => ['wedding', 'engagement'], 'default_settings' => InvitationTemplateSettings::defaults(), 'is_active' => true, 'display_order' => 1]);
        Template::firstOrCreate(['slug' => 'modern-minimal'], ['name' => 'Modern Minimal', 'description' => 'A clean, contemporary invitation layout.', 'category' => 'modern', 'component_key' => 'modern-minimal', 'default_settings' => [...InvitationTemplateSettings::defaults(), 'heading_font' => 'modern_sans', 'text_alignment' => 'left'], 'is_active' => true, 'display_order' => 2]);
        Template::firstOrCreate(['slug' => 'romantic-floral'], ['name' => 'Romantic Floral', 'description' => 'A warm romantic invitation layout.', 'category' => 'romantic', 'component_key' => 'romantic-floral', 'supported_event_types' => ['wedding', 'bridal_shower', 'engagement'], 'default_settings' => [...InvitationTemplateSettings::defaults(), 'primary_color' => '#B76E79'], 'is_active' => true, 'display_order' => 3]);
        $wedding = Event::firstOrCreate(['title' => 'Maya and Elias Wedding'], ['customer_id' => $first->id, 'event_package_id' => $packages[1]->id, 'event_type' => 'wedding', 'host_name' => 'Maya Haddad', 'main_date' => now()->addMonths(3)->toDateString(), 'venue' => 'Cedar Hall', 'status' => 'approved']);
        $birthday = Event::firstOrCreate(['title' => 'Nour Birthday'], ['customer_id' => $first->id, 'event_package_id' => $packages[0]->id, 'event_type' => 'birthday', 'host_name' => 'Maya Haddad', 'main_date' => now()->addMonth()->toDateString(), 'status' => 'draft']);
        $engagement = Event::firstOrCreate(['title' => 'Karim and Rania Engagement'], ['customer_id' => $second->id, 'event_package_id' => $packages[2]->id, 'event_type' => 'engagement', 'host_name' => 'Karim Nasser', 'main_date' => now()->addMonths(2)->toDateString(), 'status' => 'submitted']);
        $wedding->update([
            'event_timezone' => 'America/New_York',
            'rsvp_deadline' => $wedding->main_date?->copy()->subDays(10)->toDateString(),
            'dress_code' => 'Formal attire',
            'parking_information' => 'Complimentary parking is available at Cedar Hall.',
            'transportation_information' => 'Please arrange your own transportation.',
            'accommodation_information' => 'A guest rate is available at the nearby Cedar Hotel.',
            'guest_information' => 'Please arrive 15 minutes before the ceremony.',
            'template_id' => $elegant->id,
        ]);
        $wedding->templateSetting()->firstOrCreate([], ['settings' => ['primary_color' => '#C9A96E']]);
        $weddingDate = $wedding->main_date->toDateString();
        $ceremonyStart = \Carbon\CarbonImmutable::createFromFormat('!Y-m-d H:i', "{$weddingDate} 16:00", $wedding->event_timezone);
        $receptionStart = \Carbon\CarbonImmutable::createFromFormat('!Y-m-d H:i', "{$weddingDate} 18:00", $wedding->event_timezone);
        EventActivity::firstOrCreate(['event_id' => $wedding->id, 'title' => 'Wedding ceremony'], ['activity_type' => 'ceremony', 'description' => 'Please be seated before the ceremony begins.', 'starts_at' => $ceremonyStart->utc(), 'ends_at' => $ceremonyStart->addHour()->utc(), 'venue' => 'Cedar Hall Garden', 'display_order' => 1, 'is_active' => true]);
        EventActivity::firstOrCreate(['event_id' => $wedding->id, 'title' => 'Reception'], ['activity_type' => 'reception', 'starts_at' => $receptionStart->utc(), 'ends_at' => $receptionStart->addHours(4)->utc(), 'venue' => 'Cedar Hall Ballroom', 'display_order' => 2, 'is_active' => true]);
        $wedding->members()->syncWithoutDetaching([$maya->id => ['role' => 'owner']]);
        $birthday->members()->syncWithoutDetaching([$maya->id => ['role' => 'owner']]);
        $engagement->members()->syncWithoutDetaching([$karim->id => ['role' => 'owner']]);
        $individual = InvitationParty::firstOrCreate(['event_id' => $wedding->id, 'name' => 'Nadia Saad'], ['primary_contact_name' => 'Nadia Saad', 'email' => 'nadia@example.test', 'maximum_party_size' => 1, 'table_name' => 'A1', 'created_by' => $admin->id]);
        $couple = InvitationParty::firstOrCreate(['event_id' => $wedding->id, 'name' => 'Rami and Leila Haddad'], ['primary_contact_name' => 'Rami Haddad', 'phone' => '555-0110', 'maximum_party_size' => 2, 'table_name' => 'A2', 'created_by' => $admin->id]);
        $family = InvitationParty::firstOrCreate(['event_id' => $wedding->id, 'name' => 'The Smith Family'], ['primary_contact_name' => 'John Smith', 'email' => 'smith@example.test', 'maximum_party_size' => 4, 'table_name' => 'B1', 'created_by' => $admin->id]);
        $individual->members()->firstOrCreate(['first_name' => 'Nadia', 'last_name' => 'Saad'], ['member_type' => 'adult']);
        $couple->members()->firstOrCreate(['first_name' => 'Rami', 'last_name' => 'Haddad'], ['member_type' => 'adult']);
        $couple->members()->firstOrCreate(['first_name' => 'Leila', 'last_name' => 'Haddad'], ['member_type' => 'adult']);
        $family->members()->firstOrCreate(['first_name' => 'John', 'last_name' => 'Smith'], ['member_type' => 'adult']);
        $family->members()->firstOrCreate(['first_name' => 'Emma', 'last_name' => 'Smith'], ['member_type' => 'child']);
        EventMealOption::firstOrCreate(['event_id' => $wedding->id, 'name' => 'Chicken'], ['description' => 'Roasted chicken', 'display_order' => 1, 'is_active' => true]);
        EventMealOption::firstOrCreate(['event_id' => $wedding->id, 'name' => 'Vegetarian'], ['description' => 'Vegetarian option', 'display_order' => 2, 'is_active' => true]);
        Coupon::firstOrCreate(['code' => 'WELCOME10'], ['description' => 'Development welcome coupon', 'discount_type' => 'percentage', 'discount_value' => 10, 'is_active' => true]);
        $payment = Payment::firstOrCreate(['event_id' => $wedding->id], ['customer_id' => $first->id, 'event_package_id' => $packages[1]->id, 'original_amount' => 55, 'discount' => 5, 'discount_type' => 'fixed', 'discount_value' => 5, 'final_amount' => 50, 'paid_amount' => 0, 'status' => 'unpaid']);
        if (! $payment->transactions()->exists() && (float) $payment->paid_amount === 0.0) {
            $payment->transactions()->create(['amount' => 50, 'payment_method' => 'cash', 'reference' => 'CASH-001', 'payment_date' => now()->toDateString(), 'notes' => 'Local development payment', 'created_by' => $admin->id, 'status' => 'confirmed']);
            $payment->refreshTotals();
        }
    }
}
