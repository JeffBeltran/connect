<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ReadThreadsTest extends TestCase
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

    /** @test */
    function a_user_can_read_replies_that_are_associated_with_a_thread()
    {
        $thread = factory('App\Thread')->create();

        $reply = factory('App\Reply')->create([
            'thread_id' => $thread->id,
        ]);

        $response = $this->get('/threads/'. $thread->id);

        $response->assertSee($reply->body);

    }
}
