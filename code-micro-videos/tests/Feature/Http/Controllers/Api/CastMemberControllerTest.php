<?php

namespace Tests\Feature\Http\Controllers\Api;

use App\Models\CastMember;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;
use Tests\Traits\TestSaves;
use Tests\Traits\TestValidation;


class CastMemberControllerTest extends TestCase
{
    use DatabaseMigrations, TestValidation, TestSaves;

    private $model;

    protected function model()
    {
        return CastMember::class;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->model = factory($this->model())->create([
            'type' => CastMember::TYPE_DIRECTOR
        ]);
    }

    public function testIndex()
    {
        $response = $this->get(route('cast_members.index'));

        $response
            ->assertStatus(200)
            ->assertJson([$this->model->toArray()]);
    }

    public function testShow()
    {
        $response = $this->get(route('cast_members.show', ['cast_member' => $this->model->id]));

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
            'type' => '3',
        ];
        $this->assertInvalidationInStoreAction($data, 'in');
        $this->assertInvalidationInUpdateAction($data, 'in');
    }

    public function testStore()
    {
        $data = [
            [
            'name' => 'teste 1',
            'type' => CastMember::TYPE_DIRECTOR,
            ],
            [
                'name' => 'test 1',
                'type' => CastMember::TYPE_ACTOR,
            ]
        ];
        foreach ($data as $value) {
            $response = $this->assertStore($value, $value + [
                    'deleted_at' => null
                ]);
            $response->assertJsonStructure([
                'created_at', 'updated_at'
            ]);
        }
    }

    public function testUpdate()
    {
        $this->model = factory(CastMember::class)->create([
            'name' => 'test insert',
            'type' => CastMember::TYPE_DIRECTOR,
        ]);
        $data = [
            'name' => 'test update',
            'type' => CastMember::TYPE_ACTOR,
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
        $model = factory(CastMember::class)->create([]);
        $response = $this->json('DELETE', route('cast_members.destroy', ['cast_member' => $model->id]), []);

        $response
            ->assertStatus(204);

        $this->assertNull(CastMember::find($model->id));

        $model->restore();
        $this->assertNotNull(CastMember::find($model->id));
    }

    protected function routeStore()
    {
        return route('cast_members.store');
    }

    protected function routeUpdate()
    {
        return route('cast_members.update', ['cast_member' => $this->model->id]);
    }
}
