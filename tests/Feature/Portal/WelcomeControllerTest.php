<?php

declare(strict_types=1);

namespace Tests\Feature\Portal;

use Tests\TestCase;

final class WelcomeControllerTest extends TestCase
{
    public function test_landing_page_displays_the_active_module_count(): void
    {
        $response = $this->get(route('welcome'));

        $response->assertOk()
            ->assertViewIs('welcome')
            ->assertViewHas('activeModuleCount', 8)
            ->assertSee('<strong class="stat-number">8</strong>', false);

        $this->assertSame(8, substr_count($response->getContent(), 'class="module-card"'));
    }

    public function test_only_the_visible_hero_slide_is_loaded_with_the_initial_page(): void
    {
        $response = $this->get(route('welcome'));

        $response->assertOk()
            ->assertSee('src="'.asset('images/image2.avif').'"', false)
            ->assertSee('data-src="'.asset('images/image1.avif').'"', false)
            ->assertSee('data-src="'.asset('images/image3.avif').'"', false)
            ->assertDontSee(' src="'.asset('images/image1.avif').'"', false)
            ->assertDontSee(' src="'.asset('images/image3.avif').'"', false);
    }
}
