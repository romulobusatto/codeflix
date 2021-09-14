<?php

namespace Tests\Feature\Http\Controllers\Api;

use App\Models\Genre;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\TestResponse;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Traits\TestSaves;
use Tests\Traits\TestValidation;


class GenreControllerTest extends TestCase
{
    use DatabaseMigrations, TestValidation, TestSaves;

    private $model;

    protected function model()
    {
        return Genre::class;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->model = factory($this->model())->create();
    }

    public function testIndex()
    {
        $response = $this->get(route('genres.index'));

        $response
            ->assertStatus(200)
            ->assertJson([$this->model->toArray()]);
    }

    public function testShow()
    {
        $response = $this->get(route('genres.show', ['genre' => $this->model->id]));
        $response
            ->assertStatus(200)
            ->assertJson($this->model->toArray());
    }

    public function testInvalidationData()
    {
        $data = [
            'name' => ''
        ];
        $this->assertInvalidationInStoreAction($data, 'required');
        $this->assertInvalidationInUpdateAction($data, 'required');

        $data = [
            'name' => str_repeat('a', 256)
        ];
        $this->assertInvalidationInStoreAction($data, 'max.string', ['max' => 255]);
        $this->assertInvalidationInUpdateAction($data, 'max.string', ['max' => 255]);

        $data = [
            'is_active' => 'a',
        ];
        $this->assertInvalidationInStoreAction($data, 'boolean');
        $this->assertInvalidationInUpdateAction($data, 'boolean');
    }

    public function testStore()
    {
        $data = [
            'name' => 'teste 1'
        ];
        $response = $this->assertStore($data, $data + [
                'is_active' => true,
                'deleted_at' => null
            ]);
        $response->assertJsonStructure([
            'created_at', 'updated_at'
        ]);

        $data = [
            'name' => 'test 1',
            'is_active' => false,
        ];
        $this->assertStore($data, $data);
    }

    public function testUpdate()
    {
        $this->model = factory(Genre::class)->create([
            'is_active' => false
        ]);
        $data = [
            'name' => 'teste 1',
            'is_active' => true,
        ];
        $response = $this->assertUpdate($data, $data + [
                'deleted_at' => null
            ]);
        $response->assertJsonStructure([
            'created_at', 'updated_at'
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

    protected function routeStore()
    {
        return route('genres.store');
    }

    protected function routeUpdate()
    {
        return route('genres.update', ['genre' => $this->model->id]);
    }
}
