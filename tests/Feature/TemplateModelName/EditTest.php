<?php

namespace Tests\Feature\TemplateModelName;

use App\TemplateModelName;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Permission;
use Tests\TestCase;

class EditTest extends TestCase
{
    use RefreshDatabase;

    private function editModel($id, $params = [], $user = null, $hasPermission = true)
    {
        if ($user == null) {
            $user = factory(User::class)->create();
        }

        if ($hasPermission) {
            $user->attachPermission(Permission::create(['name' => 'edit-model-name']));
        }

        return $this->actingAs($user, 'api')->json('PUT', "/api/templateModelNames/$id", $params);
    }

    /** @test */
    public function testBasicUpdateForTemplateModelName()
    {
        $this->withoutExceptionHandling();

        $templateModelName = factory(TemplateModelName::class)->create([
            'name' => 'TemplateModelName Name',
        ]);
        $input = [
            'name' => 'New TemplateModelName Name',
        ];

        $response = $this->editModel($templateModelName->id, $input);

        $response->assertStatus(200);
        $templateModelNames = TemplateModelName::all();
        $this->assertCount(1, $templateModelNames);
        $this->assertEquals($input['name'], $templateModelNames->first()->name);
    }

    /** @test */
    public function testRequiresThe_related_model_Relation()
    {
        $templateModelName = factory(TemplateModelName::class)->create();
        $input = [
            'name' => 'Some Name',
        ];

        $response = $this->editModel($templateModelName->id, $input);

        $response->assertStatus(422)->assertJsonValidationErrors('related_model_id');
    }

    /** @test */
    public function testRequiresThatThe_related_model_RelationIsValid()
    {
        $templateModelName = factory(TemplateModelName::class)->create();
        $input = [
            'name' => 'Some Name',
            'related_model_id' => '22',
        ];

        $response = $this->editModel($templateModelName->id, $input);

        $response->assertStatus(422)->assertJsonValidationErrors('related_model_id');
    }

    /** @test */
    public function testRequiresNameFieldForTemplateModelName()
    {
        $templateModelName = factory(TemplateModelName::class)->create();
        $input = [
            'name' => '',
        ];

        $response = $this->editModel($templateModelName->id, $input);

        $response->assertStatus(422)->assertJsonValidationErrors('name');

        $this->assertEquals(1, TemplateModelName::count());
        $this->assertEquals($templateModelName->updated_at, TemplateModelName::first()->updated_at);
    }

    /** @test */
    public function testRequiresNameToBeUniqueForTemplateModelName()
    {
        $templateModelNameOne = factory(TemplateModelName::class)->create([
            'name' => 'templateModelName name',
        ]);
        $templateModelNameTwo = factory(TemplateModelName::class)->create([
            'name' => 'new templateModelName name',
        ]);
        $input = [
            'name' => 'new templateModelName name',
        ];

        $response = $this->editModel($templateModelNameOne->id, $input);

        $response->assertStatus(422)->assertJsonValidationErrors('name');

        $this->assertEquals(
            $templateModelNameOne->updated_at,
            TemplateModelName::find($templateModelNameOne->id)->updated_at
        );
    }

    /** @test */
    public function testIgnoresUniqueNameConstraintOnSelf()
    {
        $this->withoutExceptionHandling();

        $templateModelName = factory(TemplateModelName::class)->create([
            'name' => 'Same TemplateModelName Name',
            'description' => 'old description',
        ]);
        $input = [
            'name' => 'Same TemplateModelName Name',
            'description' => 'new description',
        ];

        $response = $this->editModel($templateModelName->id, $input);

        $response->assertStatus(200);
        $templateModelNames = TemplateModelName::all();
        $this->assertCount(1, $templateModelNames);
        $this->assertEquals($input['name'], $templateModelNames->first()->name);
        $this->assertEquals($input['description'], $templateModelNames->first()->description);
    }

    /** @test */
    public function testUserRequiresEditAccessToChangeModel()
    {
        $user = factory(User::class)->create();
        $templateModelName = factory(TemplateModelName::class)->create();

        $response = $this->editModel($templateModelName->id, [], $user, false);

        $response->assertStatus(403);
        $this->assertEquals(1, TemplateModelName::count());
    }

    /** @test */
    public function testReturns404IfNoModelExists()
    {
        $templateModelName = factory(TemplateModelName::class)->create();

        $response = $this->editModel(22, []);

        $response->assertStatus(404);
        $this->assertEquals(1, TemplateModelName::count());
    }
}
