<?php

namespace Tests\Feature\Http\Controllers\Api;

use App\Models\Category;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\TestResponse;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Traits\TestValidation;


class CategoryControllerTest extends TestCase
{
    use DatabaseMigrations, TestValidation;

    public function testIndex()
    {
        $model = factory(Category::class)->create();
        $response = $this->get(route('categories.index'));

        $response
            ->assertStatus(200)
            ->assertJson([$model->toArray()]);
    }

    public function testShow()
    {
        $model = factory(Category::class)->create();
        $response = $this->get(route('categories.show', ['category' => $model->id]));

        $response
            ->assertStatus(200)
            ->assertJson($model->toArray());
    }

    public function testInvalidationData()
    {
        $response = $this->json('POST', route('categories.store'), []);
        $this->assertInvalidationRequired($response);

        $response = $this->json('POST', route('categories.store'), [
            'name' => str_repeat('a', 256),
            'is_active' => 'a',
        ]);
        $this->assertInvalidationMax($response);
        $this->assertInvalidationBoolean($response);

        $model = factory(Category::class)->create();
        $response = $this->json('PUT', route('categories.update', ['category' => $model->id]), []);
        $this->assertInvalidationRequired($response);

        $response = $this->json('PUT', route('categories.update', ['category' => $model->id]), [
            'name' => str_repeat('a', 256),
            'is_active' => 'a',
        ]);
        $this->assertInvalidationMax($response);
        $this->assertInvalidationBoolean($response);
    }

    protected function assertInvalidationRequired(TestResponse $response)
    {
        $this->assertInvalidationField($response, ['name'], 'required');
        $response->assertJsonMissingValidationErrors(['is_active']);
    }

    protected function assertInvalidationMax(TestResponse $response)
    {
        $this->assertInvalidationField($response, ['name'], 'max.string', ['max' => 255]);
    }

    protected function assertInvalidationBoolean(TestResponse $response)
    {
        $this->assertInvalidationField($response, ['is_active'], 'boolean');
    }

    public function testStore()
    {
        $response = $this->json('POST', route('categories.store'), [
            'name' => 'teste 1'
        ]);
        $id = $response->json('id');
        $model = Category::find($id);

        $response
            ->assertStatus(201)
            ->assertJson($model->toArray());
        $this->assertTrue($response->json('is_active'));
        $this->assertNull($response->json('description'));


        $response = $this->json('POST', route('categories.store'), [
            'name' => 'test 1',
            'description' => 'test_description',
            'is_active' => false,
        ]);

        $response->assertJsonFragment([
            'is_active' => false,
            'description' => 'test_description',
        ]);
    }

    public function testUpdate()
    {
        $model = factory(Category::class)->create([
            'is_active' => false,
            'description' => 'description',
        ]);
        $response = $this->json('PUT', route('categories.update', ['category' => $model->id]), [
            'name' => 'teste 1',
            'is_active' => true,
            'description' => null
        ]);
        $model->refresh();
        $response
            ->assertStatus(200)
            ->assertJson($model->toArray())
            ->assertJsonFragment([
                'is_active' => true,
                'description' => null,
            ]);
    }

    public function testDestroy()
    {
        $model = factory(Category::class)->create([]);
        $response = $this->json('DELETE', route('categories.destroy', ['category' => $model->id]), []);

        $response
            ->assertStatus(204);

        $this->assertNull(Category::find($model->id));

        $model->restore();
        $this->assertNotNull(Category::find($model->id));
    }
}
