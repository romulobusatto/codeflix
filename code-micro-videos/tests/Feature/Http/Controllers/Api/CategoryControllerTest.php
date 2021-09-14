<?php

namespace Tests\Feature\Http\Controllers\Api;

use App\Models\Category;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;
use Tests\Traits\TestSaves;
use Tests\Traits\TestValidation;


class CategoryControllerTest extends TestCase
{
    use DatabaseMigrations, TestValidation, TestSaves;

    private $model;

    protected function model()
    {
        return Category::class;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->model = factory($this->model())->create();
    }

    public function testIndex()
    {
        $response = $this->get(route('categories.index'));

        $response
            ->assertStatus(200)
            ->assertJson([$this->model->toArray()]);
    }

    public function testShow()
    {
        $response = $this->get(route('categories.show', ['category' => $this->model->id]));
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
                'description' => null,
                'is_active' => true,
                'deleted_at' => null
            ]);
        $response->assertJsonStructure([
            'created_at', 'updated_at'
        ]);

        $data = [
            'name' => 'test 1',
            'description' => 'test_description',
            'is_active' => false,
        ];
        $this->assertStore($data, $data);
    }

    public function testUpdate()
    {
        $this->model = factory(Category::class)->create([
            'is_active' => false,
            'description' => 'description',
        ]);
        $data = [
            'name' => 'teste 1',
            'is_active' => true,
            'description' => 'test'
        ];
        $response = $this->assertUpdate($data, $data + [
                'deleted_at' => null
            ]);
        $response->assertJsonStructure([
            'created_at', 'updated_at'
        ]);

        $data = [
            'name' => 'teste 1',
            'description' => ''
        ];
        $this->assertUpdate($data, array_merge($data, [
            'description' => null
        ]));
    }

    public function testDestroy()
    {
        $response = $this->json('DELETE', route('categories.destroy', ['category' => $this->model->id]), []);

        $response
            ->assertStatus(204);

        $this->assertNull(Category::find($this->model->id));

        $this->model->restore();
        $this->assertNotNull(Category::find($this->model->id));
    }

    protected function routeStore()
    {
        return route('categories.store');
    }

    protected function routeUpdate()
    {
        return route('categories.update', ['category' => $this->model->id]);
    }


}
