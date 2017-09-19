<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ThreadsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    function a_user_can_browse_all_threads()
    {
        $this->withoutExceptionHandling();

        $thread = factory('App\Thread')->create();

        $response = $this->get('/threads');

        $response->assertSee($thread->title);
    }

    /** @test */
    function a_user_can_read_a_single_thread()
    {
        $this->withoutExceptionHandling();

        $thread = factory('App\Thread')->create();

        $response = $this->get('/threads/'. $thread->id);

        $response->assertSee($thread->title);
    }
}
