<?php

namespace Tests\Feature;

use Tests\TestCase;

class ErrorPageTest extends TestCase
{
    public function test_custom_404_page_is_branded(): void
    {
        $this->get('/this-route-definitely-does-not-exist')
            ->assertNotFound()
            ->assertSee('Сторінку не знайдено');
    }
}
