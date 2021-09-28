<?php

namespace Tests\Feature\Http\Controllers\Api;

use App\Http\Controllers\Api\VideoController;
use App\Models\Category;
use App\Models\Genre;
use App\Models\Video;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Http\Request;
use Tests\Exceptions\TestException;
use Tests\TestCase;
use Tests\Traits\TestSaves;
use Tests\Traits\TestValidation;


class VideoControllerTest extends TestCase
{
    use DatabaseMigrations, TestValidation, TestSaves;

    private $model;
    private $sendData;

    protected function setUp(): void
    {
        parent::setUp();
        $category = factory(Category::class)->create();
        $genre = factory(Genre::class)->create();
        $this->model = factory($this->model())->create(['opened' => false]);
        $this->sendData = [
            'title' => 'title',
            'description' => 'description',
            'rating' => Video::RATING_LIST[0],
            'year_launched' => 2010,
            'duration' => 90,
            'categories_id' => [$category->id],
            'genres_id' => [$genre->id],
        ];
    }

    public function testIndex()
    {
        $response = $this->get(route('videos.index'));

        $response
            ->assertStatus(200)
            ->assertJson([$this->model->toArray()]);
    }

    public function testShow()
    {
        $response = $this->get(route('videos.show', ['video' => $this->model->id]));

        $response
            ->assertStatus(200)
            ->assertJson($this->model->toArray());
    }

    public function testInvalidationRequired()
    {
        $data = [
            'title' => '',
            'description' => '',
            'year_launched' => '',
            'duration' => '',
            'rating' => '',
            'categories_id' => '',
            'genres_id' => '',
        ];
        $this->assertInvalidationInStoreAction($data, 'required');
        $this->assertInvalidationInUpdateAction($data, 'required');
    }

    public function testInvalidationMax()
    {
        $data = [
            'title' => str_repeat('a', 256)
        ];
        $this->assertInvalidationInStoreAction($data, 'max.string', ['max' => 255]);
        $this->assertInvalidationInUpdateAction($data, 'max.string', ['max' => 255]);
    }

    public function testInvalidationInteger()
    {
        $data = [
            'duration' => 'a'
        ];
        $this->assertInvalidationInStoreAction($data, 'integer');
        $this->assertInvalidationInUpdateAction($data, 'integer');
    }

    public function testInvalidationYearlaunchedField()
    {
        $data = [
            'year_launched' => 'a'
        ];
        $this->assertInvalidationInStoreAction($data, 'date_format', ['format' => 'Y']);
        $this->assertInvalidationInUpdateAction($data, 'date_format', ['format' => 'Y']);
    }

    public function testInvalidationBoolean()
    {
        $data = [
            'opened' => 'a'
        ];
        $this->assertInvalidationInStoreAction($data, 'boolean');
        $this->assertInvalidationInUpdateAction($data, 'boolean');
    }

    public function testInvalidationIn()
    {
        $data = [
            'rating' => 'a'
        ];
        $this->assertInvalidationInStoreAction($data, 'in');
        $this->assertInvalidationInUpdateAction($data, 'in');
    }

    public function testInvalidationArray()
    {
        $data = [
            'categories_id' => 'a',
            'genres_id' => 'a'
        ];
        $this->assertInvalidationInStoreAction($data, 'array');
        $this->assertInvalidationInUpdateAction($data, 'array');
    }

    public function testInvalidationExists()
    {
        $data = [
            'genres_id' => [100],
            'categories_id' => [100]
        ];
        $this->assertInvalidationInStoreAction($data, 'exists');
        $this->assertInvalidationInUpdateAction($data, 'exists');

        $category = factory(Category::class)->create();
        $category->delete();
        $genre = factory(Genre::class)->create();
        $genre->delete();

        $data = [
            'genres_id' => [$genre->id],
            'categories_id' => [$category->id]
        ];
        $this->assertInvalidationInStoreAction($data, 'exists');
        $this->assertInvalidationInUpdateAction($data, 'exists');
    }

    public function testStoreAndUpdate()
    {
        $testData = $this->sendData;
        unset($testData['categories_id'], $testData['genres_id']);
        $data = [
            [
                'send_data' => $this->sendData,
                'test_data' => $testData + ['opened' => false,],
            ],
            [
                'send_data' => $this->sendData + ['opened' => true,],
                'test_data' => $testData + ['opened' => true,],
            ],
            [
                'send_data' => $this->sendData + ['rating' => Video::RATING_LIST[1]],
                'test_data' => $testData + ['rating' => Video::RATING_LIST[1]],
            ],
        ];
        foreach ($data as $value) {
            $response = $this->assertStore(
                $value['send_data'],
                $value['test_data'] + ['deleted_at' => null]
            );
            $response->assertJsonStructure([
                'created_at', 'updated_at'
            ]);
            $this->assertHasCategory($response->json('id'), $value['send_data']['categories_id'][0]);
            $this->assertHasGenre($response->json('id'), $value['send_data']['genres_id'][0]);

            $response = $this->assertUpdate(
                $value['send_data'],
                $value['test_data'] + ['deleted_at' => null]
            );
            $response->assertJsonStructure([
                'created_at', 'updated_at'
            ]);
            $this->assertHasCategory($response->json('id'), $value['send_data']['categories_id'][0]);
            $this->assertHasGenre($response->json('id'), $value['send_data']['genres_id'][0]);
        }
    }

    public function testSyncCategory()
    {
        $categoriesId = factory(Category::class, 3)->create([])->pluck('id')->toArray();
        $genre = factory(Genre::class)->create();
        $genre->categories()->sync($categoriesId);
        $sendData = $this->sendData;
        $sendData['categories_id'] = $categoriesId;
        $sendData['genres_id'] = [$genre->id];

        $response = $this->json('POST', $this->routeStore(), $sendData);
        $this->assertHasCategory($response->json('id'), $categoriesId[0]);
        $this->assertHasCategory($response->json('id'), $categoriesId[1]);
        $this->assertHasCategory($response->json('id'), $categoriesId[2]);

        $sendData['categories_id'] = [$categoriesId[1], $categoriesId[2]];
        $response = $this->json('PUT',
            route('videos.update', ['video' => $response->json('id')]), $sendData);
        $this->assertMissingCategory($response->json('id'), $categoriesId[0]);
        $this->assertHasCategory($response->json('id'), $categoriesId[1]);
        $this->assertHasCategory($response->json('id'), $categoriesId[2]);
    }

    public function testSyncGenre()
    {
        $genres = factory(Genre::class, 3)->create([]);
        $genresId = $genres->pluck('id')->toArray();
        $category = factory(Category::class)->create();
        $genres->each(function ($genre) use ($category) {
            $genre->categories()->sync($category->id);
        });

        $sendData = $this->sendData;
        $sendData['genres_id'] = $genresId;
        $sendData['categories_id'] = [$category->id];

        $response = $this->json('POST', $this->routeStore(), $sendData);
        $this->assertHasGenre($response->json('id'), $genresId[0]);
        $this->assertHasGenre($response->json('id'), $genresId[1]);
        $this->assertHasGenre($response->json('id'), $genresId[2]);

        $sendData['genres_id'] = [$genresId[1], $genresId[2]];
        $response = $this->json('PUT',
            route('videos.update', ['video' => $response->json('id')]), $sendData);
        $this->assertMissingGenre($response->json('id'), $genresId[0]);
        $this->assertHasGenre($response->json('id'), $genresId[1]);
        $this->assertHasGenre($response->json('id'), $genresId[2]);
    }


    protected function assertHasCategory($idVideo, $idCategory)
    {
        $this->assertDatabaseHas('category_video', [
            'video_id' => $idVideo,
            'category_id' => $idCategory,
        ]);
    }

    protected function assertMissingCategory($idVideo, $idCategory)
    {
        $this->assertDatabaseMissing('category_video', [
            'video_id' => $idVideo,
            'category_id' => $idCategory,
        ]);
    }

    protected function assertHasGenre($idVideo, $idGenre)
    {
        $this->assertDatabaseHas('genre_video', [
            'video_id' => $idVideo,
            'genre_id' => $idGenre,
        ]);
    }

    protected function assertMissingGenre($idVideo, $idGenre)
    {
        $this->assertDatabaseMissing('genre_video', [
            'video_id' => $idVideo,
            'genre_id' => $idGenre,
        ]);
    }

    public function testRollbackStore()
    {
        $controller = \Mockery::mock(VideoController::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $controller->shouldReceive('validate')
            ->withAnyArgs()
            ->andReturn($this->sendData);

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
            $this->assertCount(1, Video::all());
        }
    }

    public function testRollbackUpdate()
    {
        $controller = \Mockery::mock(VideoController::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $controller->shouldReceive('validate')
            ->withAnyArgs()
            ->andReturn($this->sendData + ['title' => 'testRollbackUpdate']);

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
            $this->assertNotEquals('testRollbackUpdate', $this->model->title);
        }
    }

    public function testDestroy()
    {
        $model = factory(Video::class)->create([]);
        $response = $this->json('DELETE', route('videos.destroy', ['video' => $model->id]), []);

        $response
            ->assertStatus(204);

        $this->assertNull(Video::find($model->id));

        $model->restore();
        $this->assertNotNull(Video::find($model->id));
    }

    protected function model()
    {
        return Video::class;
    }

    protected function routeStore()
    {
        return route('videos.store');
    }

    protected function routeUpdate()
    {
        return route('videos.update', ['video' => $this->model->id]);
    }
}
