<?php

namespace Tests\Feature\TemplateModelName;

use App\TemplateModelName;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Permission;
use Tests\TestCase;

class DeleteTest extends TestCase
{
    use RefreshDatabase;

    private function deleteModel($id, $params = [], $user = null, $hasPermission = true)
    {
        if ($user == null) {
            $user = factory(User::class)->create();
        }

        if ($hasPermission) {
            $user->attachPermission(Permission::create(['name' => 'delete-model-name']));
        }

        return $this->actingAs($user, 'api')->json(
            'DELETE',
            "/api/templateModelNames/$id",
            $params
        );
    }

    /** @test */
    public function testUserCanDeleteATemplateModelName()
    {
        $this->withoutExceptionHandling();

        $templateModelNameOne = factory(TemplateModelName::class)->create();
        $templateModelNameTwo = factory(TemplateModelName::class)->create();
        $this->assertEquals(2, TemplateModelName::count());

        $response = $this->deleteModel($templateModelNameOne->id);

        $response->assertStatus(200);
        $templateModelNames = TemplateModelName::all();
        $this->assertCount(1, $templateModelNames);
        $this->assertEquals($templateModelNameTwo->id, $templateModelNames->first()->id);
    }

    /** @test */
    public function testItWillReturnErrorIfItHasADependentRelationship()
    {
        $this->withoutExceptionHandling();

        $templateModelName = factory(TemplateModelName::class)->create();
        factory(DependentRelationship::class)->create([
            'templateModelName_id' => $templateModelName->id,
        ]);

        $response = $this->deleteModel($templateModelName->id);

        $response->assertStatus(409);
        $this->assertEquals(1, TemplateModelName::count());
        $this->assertEquals(1, DependentRelationship::count());
    }

    /** @test */
    public function testUserRequiresDeletePermissinoToRemoveModel()
    {
        $user = factory(User::class)->create();
        $templateModelName = factory(TemplateModelName::class)->create();

        $response = $this->deleteModel($templateModelName->id, [], $user, false);

        $response->assertStatus(403);
        $this->assertEquals(1, TemplateModelName::count());
    }

    /** @test */
    public function testUserGets404IfNoModelExists()
    {
        factory(TemplateModelName::class)->create();
        $this->assertCount(1, TemplateModelName::all());

        $response = $this->deleteModel(22);

        $response->assertStatus(404);
        $this->assertEquals(1, TemplateModelName::count());
    }
}
