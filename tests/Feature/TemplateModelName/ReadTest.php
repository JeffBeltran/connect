<?php

namespace Tests\Feature\TemplateModelName;

use App\TemplateModelName;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Permission;
use Tests\TestCase;

class ReadTest extends TestCase
{
    use RefreshDatabase;

    private function readModel($id, $params = null, $user = null, $hasPermission = true)
    {
        if ($user == null) {
            $user = factory(User::class)->create();
        }

        if ($hasPermission) {
            $user->attachPermission(Permission::create(['name' => 'read-model-name']));
        }

        return $this->actingAs($user, 'api')->json('GET', "/api/templateModelNames/$id" . $params);
    }

    /** @test */
    public function testReturnsTemplateModelNameDetailsForGivenID()
    {
        $this->withoutExceptionHandling();

        $templateModelName = factory(TemplateModelName::class)->create();

        $response = $this->readModel($templateModelName->id);

        $response->assertStatus(200)->assertExactJson([
            'id' => $templateModelName->id,
            'name' => $templateModelName->name,
            // ...
            'created_at' => $templateModelName->created_at->toDateTimeString(),
            'updated_at' => $templateModelName->updated_at->toDateTimeString(),
        ]);
    }

    /** @test */
    public function testReturnsTemplateModelNameDetailsWithTheirHasManyRelationshipNames()
    {
        $this->withoutExceptionHandling();

        $templateModelName = factory(TemplateModelName::class)->create();
        factory(HasManyRelationshipName::class, 3)->create([
            'templateModelName_id' => $templateModelName->id,
        ]);

        $response = $this->readModel($templateModelName->id, '?with=hasManyRelationshipName');

        $response->assertStatus(200)->assertJsonCount(3, 'hasManyRelationshipName');
    }

    /** @test */
    public function testReturnsTemplateModelNameDetailsWithTheBelongsToRelationshipName()
    {
        $this->withoutExceptionHandling();

        $belongsToRelationshipName = factory(BelongsToRelationshipName::class)->create();
        $templateModelName = factory(TemplateModelName::class)->create([
            'belongsToRelationshipName_id' => $belongsToRelationshipName->id,
        ]);

        $response = $this->readModel($templateModelName->id, '?with=belongsToRelationshipName');

        $response->assertStatus(200)->assertJson([
            'belongsToRelationshipName' => $belongsToRelationshipName->toArray(),
        ]);
    }

    /** @test */
    public function testReturnsTemplateModelNameDetailsWithTheirBelongsToManyRelationshipNames()
    {
        $this->withoutExceptionHandling();

        $belongsToManyRelationshipNameOne = factory(BelongsToManyRelationshipName::class)->create();
        $belongsToManyRelationshipNameTwo = factory(BelongsToManyRelationshipName::class)->create();
        $templateModelName = factory(TemplateModelName::class)->create();
        $templateModelName
            ->belongsToManyRelationshipNames()
            ->attach([
                $belongsToManyRelationshipNameOne->id,
                $belongsToManyRelationshipNameTwo->id,
            ]);

        $response = $this->readModel(
            $templateModelName->id,
            '?with=belongsToManyRelationshipNames'
        );

        $response->assertStatus(200)->assertJsonCount(2, 'belongsToManyRelationshipNames');

        $belongsToManyRelationshipNames = collect(
            $response->getData(true)['belongsToManyRelationshipNames']
        );
        $this->assertTrue(
            $belongsToManyRelationshipNames->contains('id', $belongsToManyRelationshipNameOne->id)
        );
        $this->assertTrue(
            $belongsToManyRelationshipNames->contains('id', $belongsToManyRelationshipNameTwo->id)
        );
    }

    /** @test */
    public function testUserMustHaveReadPermissionToViewDetails()
    {
        $user = factory(User::class)->create();
        $templateModelName = factory(TemplateModelName::class)->create();

        $response = $this->readModel($templateModelName->id, '', $user, false);

        $response->assertStatus(403);
        $this->assertEquals(1, TemplateModelName::count());
    }

    /** @test */
    public function testReturns404ErrorIfNoModelExists()
    {
        factory(TemplateModelName::class)->create();

        $response = $this->readModel(22);

        $response->assertStatus(404);
        $this->assertEquals(1, TemplateModelName::count());
    }
}
