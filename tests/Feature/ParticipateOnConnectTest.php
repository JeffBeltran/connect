<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ParticipateOnConnectTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    function an_authenticated_user_may_participate_on_thread()
    {
        $this->withoutExceptionHandling();

        $this->be($user = factory('App\User')->create());

        $thread = factory('App\Thread')->create();

        $reply = factory('App\Reply')->make();

        $this->post('/threads/'.$thread->id.'/replies', $reply->toArray());

        $response = $this->get($thread->path());
        $response->assertSee($reply->body);
    }

    /** @test */
    function unauthenticated_users_may_not_add_replies()
    {
        $this->withoutExceptionHandling();
        $this->expectException('Illuminate\Auth\AuthenticationException');
        $this->post('/threads/1/replies', []);
    }
}
