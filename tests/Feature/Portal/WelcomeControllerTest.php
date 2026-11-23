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
}
