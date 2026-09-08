<?php

namespace Tests\Feature;

use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class TemplateDemoTest extends TestCase
{
    public function test_demo_route_is_unavailable_outside_local_development(): void
    {
        $this->app->instance('env', 'production');
        $this->get('/template-demos')->assertNotFound();
        $this->get('/template-demos/1')->assertNotFound();
        $this->get('/template-demos/4')->assertNotFound();
        foreach (['5', '6', '7'] as $number) {
            $this->get('/template-demos/'.$number)->assertNotFound();
            $this->get('/template-demos/assets/'.$number.'/heroVideo')->assertNotFound();
        }
        $this->get('/template-demos/assets/4/heroVideo')->assertNotFound();
        $this->get('/template-demos/assets/1/opening')->assertNotFound();
    }

    public function test_local_asset_endpoint_only_serves_existing_allowlisted_files(): void
    {
        $this->app->instance('env', 'local');
        $folder = storage_path('app/qa/reference-template-assets/test-fixtures');
        if (! is_dir($folder)) {
            mkdir($folder, 0755, true);
        }
        $fixture = tempnam($folder, 'demo-test-');
        file_put_contents($fixture, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+aX1sAAAAASUVORK5CYII='));
        config(['template_demo_assets' => ['1' => [
            'opening' => 'test-fixtures/'.basename($fixture),
            'missing' => 'test-fixtures/not-present.png',
            'escape' => '../../../../.env',
        ], '5' => ['outfit10' => 'test-fixtures/'.basename($fixture)],
            '6' => ['heroPoster' => 'test-fixtures/'.basename($fixture)],
            '7' => ['heroPoster' => 'test-fixtures/'.basename($fixture), 'escape' => '../../../../.env'],
        ]]);

        try {
            $this->get('/template-demos/assets/1/opening')->assertOk()
                ->assertHeader('content-type', 'image/png')
                ->assertHeader('x-content-type-options', 'nosniff');
            $this->get('/template-demos/assets/1/notListed')->assertNotFound();
            $this->get('/template-demos/assets/1/missing')->assertNotFound();
            $this->get('/template-demos/assets/1/escape')->assertNotFound();
            $this->get('/template-demos/assets/4/opening')->assertNotFound();
            $this->get('/template-demos/assets/5/outfit10')->assertOk()->assertHeader('content-type', 'image/png');
            $this->get('/template-demos/assets/6/heroPoster')->assertOk();
            $this->get('/template-demos/assets/7/heroPoster')->assertOk();
            $this->get('/template-demos/assets/7/escape')->assertNotFound();
            $this->get('/template-demos/assets/8/heroPoster')->assertNotFound();
            $this->get('/template-demos/1')->assertInertia(fn (Assert $page) => $page
                ->where('media.opening', '/template-demos/assets/1/opening')
                ->missing('media.missing')->missing('media.escape'));
            $this->app->instance('env', 'production');
            $this->get('/template-demos/assets/1/opening')->assertNotFound();
        } finally {
            unlink($fixture);
        }
    }

    public function test_local_gallery_and_seven_specimens_are_available_without_customer_records(): void
    {
        $this->app->instance('env', 'local');
        $this->get('/template-demos')->assertOk()->assertInertia(fn (Assert $page) => $page->component('TemplateDemo')->where('number', null));
        foreach (['1', '2', '3', '4', '5', '6', '7'] as $number) {
            $this->get('/template-demos/'.$number)->assertOk()->assertInertia(fn (Assert $page) => $page->component('TemplateDemo')->where('number', $number)->missing('event')->missing('party'));
        }
        $this->get('/template-demos/8')->assertNotFound();
    }
}
