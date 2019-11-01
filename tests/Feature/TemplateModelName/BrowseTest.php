<?php

namespace Tests\Feature\TemplateModelName;

use App\TemplateModelName;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Permission;
use Tests\TestCase;

class BrowseTest extends TestCase
{
    use RefreshDatabase;

    private function browseModels($params = null, $user = null, $hasPermission = true)
    {
        if ($user == null) {
            $user = factory(User::class)->create();
        }

        if ($hasPermission) {
            $user->attachPermission(Permission::create(['name' => 'browse-model-name']));
        }

        $response = $this->actingAs($user, 'api')->json('GET', '/api/templateModelNames' . $params);

        return $response;
    }

    /** @test */
    public function testUserCanBrowseTemplateModelNames()
    {
        $this->withoutExceptionHandling();

        factory(TemplateModelName::class, 5)->create();

        $response = $this->browseModels();

        $response->assertStatus(200)->assertJsonCount(5);
    }

    /** @test */
    public function testSortTemplateModelNamesByName()
    {
        $this->withoutExceptionHandling();

        $templateModelNameOne = factory(TemplateModelName::class)->create([
            'name' => 'Alpha',
        ]);
        $templateModelNameTwo = factory(TemplateModelName::class)->create([
            'name' => 'Zulu',
        ]);
        $templateModelNameThree = factory(TemplateModelName::class)->create([
            'name' => 'Hotel',
        ]);

        $response = $this->browseModels('?sort=name,asc');

        $response->assertStatus(200)->assertJsonCount(3);

        $returnedData = collect($response->json());
        $this->assertEquals($templateModelNameOne->name, $returnedData->first()['name']);
        $this->assertEquals($templateModelNameTwo->name, $returnedData->last()['name']);
    }

    /** @test */
    public function testReturnsPaginationForTemplateModelNamesWhenLimitParamProvided()
    {
        $this->withoutExceptionHandling();

        factory(TemplateModelName::class, 20)->create();

        $response = $this->browseModels('?limit=10');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'current_page',
                'data',
                'first_page_url',
                'from',
                'last_page',
                'last_page_url',
                'next_page_url',
                'path',
                'per_page',
                'prev_page_url',
                'to',
                'total',
            ]);

        $returnedData = $response->json();
        $this->assertCount(10, $returnedData['data']);
        $this->assertEquals(20, $returnedData['total']);
    }

    /** @test */
    public function testAbleToViewOtherPageForPagination()
    {
        $this->withoutExceptionHandling();

        factory(TemplateModelName::class, 20)->create();

        $response = $this->browseModels('?limit=10&page=2');

        $response->assertStatus(200)->assertJson([
            'current_page' => 2,
        ]);

        $returnedData = $response->json();
        $this->assertCount(10, $returnedData['data']);
        $this->assertEquals(20, $returnedData['total']);
    }

    /** @test */
    public function testReturnsTemplateModelNamesWithTheirHasManyRelationshipNamesRelationship()
    {
        $this->withoutExceptionHandling();

        factory(TemplateModelName::class, 5)
            ->create()
            ->each(function ($templateModelName) {
                $templateModelName
                    ->hasManyRelationshipName()
                    ->save(factory(HasManyRelationshipName::class)->create());
            });

        $response = $this->browseModels('?with=hasManyRelationshipName');

        $response->assertStatus(200)->assertJsonCount(5);

        collect($response->json())->each(function ($templateModelName) {
            $this->assertNotEmpty(
                $templateModelName['hasManyRelationshipName'],
                'An templateModelName is missing the relationship'
            );
        });
    }

    /** @test */
    public function testReturnsTemplateModelNamesWithTheBelongsToRelationshipNameTheyBelongsTo()
    {
        $this->withoutExceptionHandling();

        factory(TemplateModelName::class, 5)
            ->create()
            ->each(function ($templateModelName) {
                $templateModelName
                    ->belongsToRelationshipName()
                    ->associate(factory(BelongsToRelationshipName::class)->create());
                $templateModelName->save();
            });

        $response = $this->browseModels('?with=belongsToRelationshipName');

        $response->assertStatus(200)->assertJsonCount(5);

        collect($response->json())->each(function ($templateModelName) {
            $this->assertNotEmpty(
                $templateModelName['belongsToRelationshipName'],
                'An templateModelName is missing the relationship'
            );
        });
    }

    /** @test */
    public function testReturnsTemplateModelNamesWithTheirBelongsToManyRelationshipNamesRelationship()
    {
        $this->withoutExceptionHandling();

        factory(TemplateModelName::class, 5)
            ->create()
            ->each(function ($templateModelName) {
                $templateModelName
                    ->belongsToManyRelationshipName()
                    ->attach([
                        factory(BelongsToManyRelationshipName::class)->create()->id,
                        factory(BelongsToManyRelationshipName::class)->create()->id,
                    ]);
            });

        $response = $this->browseModels('?with=belongsToManyRelationshipName');

        $response->assertStatus(200)->assertJsonCount(5);

        collect($response->json())->each(function ($templateModelName) {
            $this->assertNotEmpty(
                $templateModelName['belongsToManyRelationshipName'],
                'An templateModelName is missing the relationship'
            );
            $this->assertCount(2, $templateModelName['belongsToManyRelationshipName']);
        });
    }

    /** @test */
    public function testRequiresUserToHaveBrowsePermission()
    {
        $user = factory(User::class)->create();
        factory(TemplateModelName::class)->create();

        $response = $this->browseModels('', $user, false);

        $response->assertStatus(403);

        $this->assertEquals(1, TemplateModelName::count());
    }
}
