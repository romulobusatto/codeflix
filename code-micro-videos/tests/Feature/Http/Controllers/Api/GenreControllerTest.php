<?php

namespace Tests\Feature\Http\Controllers\Api;

use App\Http\Controllers\Api\GenreController;
use App\Http\Controllers\Api\VideoController;
use App\Models\Category;
use App\Models\Genre;
use App\Models\Video;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\TestResponse;
use Illuminate\Http\Request;
use Tests\Exceptions\TestException;
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

    public function testInvalidationRequired()
    {
        $data = [
            'name' => '',
            'categories_id' => '',
        ];
        $this->assertInvalidationInStoreAction($data, 'required');
        $this->assertInvalidationInUpdateAction($data, 'required');
    }

    public function testInvalidationMax()
    {
        $data = [
            'name' => str_repeat('a', 256)
        ];
        $this->assertInvalidationInStoreAction($data, 'max.string', ['max' => 255]);
        $this->assertInvalidationInUpdateAction($data, 'max.string', ['max' => 255]);
    }

    public function testInvalidationBoolean()
    {
        $data = [
            'is_active' => 'a',
        ];
        $this->assertInvalidationInStoreAction($data, 'boolean');
        $this->assertInvalidationInUpdateAction($data, 'boolean');
    }

    public function testInvalidationArray()
    {
        $data = [
            'categories_id' => 'a',
        ];
        $this->assertInvalidationInStoreAction($data, 'array');
        $this->assertInvalidationInUpdateAction($data, 'array');
    }

    public function testInvalidationExists()
    {
        $data = [
            'categories_id' => [100]
        ];
        $this->assertInvalidationInStoreAction($data, 'exists');
        $this->assertInvalidationInUpdateAction($data, 'exists');

        $category = factory(Category::class)->create();
        $category->delete();

        $data = [
            'categories_id' => [$category->id]
        ];
        $this->assertInvalidationInStoreAction($data, 'exists');
        $this->assertInvalidationInUpdateAction($data, 'exists');
    }

    public function testStore()
    {
        $category = factory(Category::class)->create();
        $data = [
            'name' => 'teste 1'
        ];
        $response = $this->assertStore($data + ['categories_id' => [$category->id]], $data + [
                'is_active' => true,
                'deleted_at' => null
            ]);
        $response->assertJsonStructure([
            'created_at', 'updated_at'
        ]);
        $this->assertHasCategory($response->json('id'), $category->id);

        $data = [
            'name' => 'test 1',
            'is_active' => false,
        ];
        $this->assertStore($data + ['categories_id' => [$category->id]], $data);
    }

    public function testUpdate()
    {
        $category = factory(Category::class)->create();
        $this->model = factory(Genre::class)->create([
            'is_active' => false
        ]);
        $data = [
            'name' => 'teste 1',
            'is_active' => true,
        ];
        $response = $this->assertUpdate($data + ['categories_id' => [$category->id]], $data + [
                'deleted_at' => null
            ]);
        $response->assertJsonStructure([
            'created_at', 'updated_at'
        ]);
        $this->assertHasCategory($response->json('id'), $category->id);
    }

    public function testSyncCategory()
    {
        $categoriesId = factory(Category::class, 3)->create([])->pluck('id')->toArray();
        $sendData = [
            'name' => 'teste',
            'categories_id' => $categoriesId
        ];
        $response = $this->json('POST', $this->routeStore(), $sendData);
        $this->assertHasCategory($response->json('id'), $categoriesId[0]);
        $this->assertHasCategory($response->json('id'), $categoriesId[1]);
        $this->assertHasCategory($response->json('id'), $categoriesId[2]);

        $sendData = [
            'name' => 'teste',
            'categories_id' => [$categoriesId[1], $categoriesId[2]]
        ];
        $response = $this->json('PUT',
            route('genres.update', ['genre' => $response->json('id')]), $sendData);
        $this->assertMissingCategory($response->json('id'), $categoriesId[0]);
        $this->assertHasCategory($response->json('id'), $categoriesId[1]);
        $this->assertHasCategory($response->json('id'), $categoriesId[2]);
    }

    protected function assertHasCategory($idGenre, $idCategory)
    {
        $this->assertDatabaseHas('category_genre', [
            'genre_id' => $idGenre,
            'category_id' => $idCategory,
        ]);
    }

    protected function assertMissingCategory($idGenre, $idCategory)
    {
        $this->assertDatabaseMissing('category_genre', [
            'genre_id' => $idGenre,
            'category_id' => $idCategory,
        ]);
    }

    public function testRollbackStore()
    {
        $controller = \Mockery::mock(GenreController::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $controller->shouldReceive('validate')
            ->withAnyArgs()
            ->andReturn([
                'name' => 'teste 1'
            ]);

        $controller->shouldReceive('rulesStore')
            ->withAnyArgs()
            ->andReturn([]);

        $controller->shouldReceive('handleRelations')
            ->once()
            ->andThrow(new TestException());

        $request = \Mockery::mock(Request::class);

        try {
            $controller->store($request);
            throw new \Exception('Error');
        } catch (TestException $e) {
            $this->assertCount(1, Genre::all());
        }
    }

    public function testRollbackUpdate()
    {
        $controller = \Mockery::mock(GenreController::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $controller->shouldReceive('validate')
            ->withAnyArgs()
            ->andReturn(['name' => 'testRollbackUpdate']);

        $controller->shouldReceive('rulesUpdate')
            ->withAnyArgs()
            ->andReturn([]);

        $controller->shouldReceive('handleRelations')
            ->once()
            ->andThrow(new TestException());

        $request = \Mockery::mock(Request::class);

        try {
            $controller->update($request, $this->model->id);
        } catch (TestException $e) {
            $this->model->refresh();
            $this->assertNotEquals('testRollbackUpdate', $this->model->name);
        }
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
