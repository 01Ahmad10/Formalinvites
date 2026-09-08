<?php

namespace Tests\Feature;

use App\Models\EventPackage;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PackageCatalogSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_default_active_package_catalog_has_the_approved_non_overlapping_ranges(): void
    {
        $this->seed(DatabaseSeeder::class);

        $packages = EventPackage::query()->where('is_active', true)->capacityOrder()->get();

        $this->assertSame([
            ['1-50 guests', 1, 50, '25.00'],
            ['51-100 guests', 51, 100, '55.00'],
            ['101-150 guests', 101, 150, '80.00'],
            ['151-200 guests', 151, 200, '100.00'],
            ['201-250 guests', 201, 250, '130.00'],
            ['251-300 guests', 251, 300, '180.00'],
            ['301-350 guests', 301, 350, '200.00'],
            ['351-400 guests', 351, 400, '280.00'],
            ['401-450 guests', 401, 450, '315.00'],
            ['451-500 guests', 451, 500, '350.00'],
        ], $packages->map(fn (EventPackage $package) => [$package->name, $package->minimum_guests, $package->maximum_guests, $package->price])->all());

        $this->assertCount(10, $packages);
        $this->assertDatabaseMissing('coupons', ['code' => 'WELCOME10']);

        foreach ($packages->values() as $index => $package) {
            if ($index === 0) {
                continue;
            }

            $this->assertGreaterThan($packages[$index - 1]->maximum_guests, $package->minimum_guests);
        }
    }
}
