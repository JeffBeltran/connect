<?php

namespace Tests\Feature\TemplateModelName;

use App\TemplateModelName;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Permission;
use Tests\TestCase;

class AddTest extends TestCase
{
    use RefreshDatabase;

    private function addModel($params, $user = null, $hasPermission = true)
    {
        if ($user == null) {
            $user = factory(User::class)->create();
        }

        if ($hasPermission) {
            $user->attachPermission(Permission::create(['name' => 'add-model-name']));
        }

        return $this->actingAs($user, 'api')->json('POST', '/api/templateModelNames', $params);
    }

    /** @test */
    public function testCreateBasicTemplateModelName()
    {
        $this->withoutExceptionHandling();

        $input = [
            'name' => 'xyz',
            // ...
        ];

        $response = $this->addModel($input);

        $response->assertStatus(201);
        $templateModelNames = TemplateModelName::all();
        $this->assertCount(1, $templateModelNames);
        $this->assertEquals($input['name'], $templateModelNames->first()->name);
    }

    /** @test */
    public function testRequiresNameFieldWhenCreatingATemplateModelName()
    {
        $input = [
            'name' => '',
        ];

        $response = $this->addModel($input);

        $response->assertStatus(422)->assertJsonValidationErrors('name');
        $this->assertEquals(0, TemplateModelName::count());
    }

    /** @test */
    public function testRequiresNameFieldToBeUniqueWhenCreatingTemplateModelName()
    {
        factory(TemplateModelName::class)->create([
            'name' => 'Same Name',
        ]);
        $input = [
            'name' => 'Same Name',
        ];

        $response = $this->addModel($input);

        $response->assertStatus(422)->assertJsonValidationErrors('name');
        $this->assertEquals(1, TemplateModelName::count());
    }

    /** @test */
    public function testRequiresThe_related_model_Relation()
    {
        $input = [
            'name' => 'Some Name',
        ];

        $response = $this->addModel($input);

        $response->assertStatus(422)->assertJsonValidationErrors('related_model_id');
        $this->assertEquals(0, TemplateModelName::count());
    }

    /** @test */
    public function testRequiresThatThe_related_model_RelationIsValid()
    {
        $input = [
            'name' => 'Some Name',
            'related_model_id' => '22',
        ];

        $response = $this->addModel($input);

        $response->assertStatus(422)->assertJsonValidationErrors('related_model_id');
        $this->assertEquals(0, TemplateModelName::count());
    }

    /** @test */
    public function testUserMustHaveAddPermissionToCreateModel()
    {
        $user = factory(User::class)->create();

        $response = $this->addModel([], $user, false);

        $response->assertStatus(403);
        $this->assertEquals(0, TemplateModelName::count());
    }
}
