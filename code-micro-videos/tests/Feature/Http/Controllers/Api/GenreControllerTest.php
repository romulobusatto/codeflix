<?php

namespace Tests\Feature\Http\Controllers\Api;

use App\Models\Genre;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\TestResponse;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Traits\TestValidation;


class GenreControllerTest extends TestCase
{
    use DatabaseMigrations, TestValidation;

    public function testIndex()
    {
        $model = factory(Genre::class)->create();
        $response = $this->get(route('genres.index'));

        $response
            ->assertStatus(200)
            ->assertJson([$model->toArray()]);
    }

    public function testShow()
    {
        $model = factory(Genre::class)->create();
        $response = $this->get(route('genres.show', ['genre' => $model->id]));

        $response
            ->assertStatus(200)
            ->assertJson($model->toArray());
    }

    public function testInvalidationData()
    {
        $response = $this->json('POST', route('genres.store'), []);
        $this->assertInvalidationRequired($response);

        $response = $this->json('POST', route('genres.store'), [
            'name' => str_repeat('a', 256),
            'is_active' => 'a',
        ]);
        $this->assertInvalidationMax($response);
        $this->assertInvalidationBoolean($response);

        $model = factory(Genre::class)->create();
        $response = $this->json('PUT', route('genres.update', ['genre' => $model->id]), []);
        $this->assertInvalidationRequired($response);

        $response = $this->json('PUT', route('genres.update', ['genre' => $model->id]), [
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
        $response = $this->json('POST', route('genres.store'), [
            'name' => 'teste 1'
        ]);
        $id = $response->json('id');
        $model = Genre::find($id);

        $response
            ->assertStatus(201)
            ->assertJson($model->toArray());
        $this->assertTrue($response->json('is_active'));

        $response = $this->json('POST', route('genres.store'), [
            'name' => 'test 1',
            'is_active' => false,
        ]);
        $response->assertJsonFragment([
            'is_active' => false,
        ]);
    }

    public function testUpdate()
    {
        $model = factory(Genre::class)->create([
            'is_active' => false,
        ]);
        $response = $this->json('PUT', route('genres.update', ['genre' => $model->id]), [
            'name' => 'teste 1',
            'is_active' => true,
        ]);
        $model->refresh();
        $response
            ->assertStatus(200)
            ->assertJson($model->toArray())
            ->assertJsonFragment([
                'is_active' => true,
            ]);
    }

    public function testDestroy()
    {
        $model = factory(Genre::class)->create([]);
        $response = $this->json('DELETE', route('genres.destroy', ['genre' => $model->id]), []);

        $response
            ->assertStatus(204);

        $this->assertNull(Genre::find($model->id));

        $model->restore();
        $this->assertNotNull(Genre::find($model->id));
    }
}
