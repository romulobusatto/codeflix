<?php

namespace Tests\Feature\Models;

use App\Models\Genre;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;
use Tests\Uuid;

class GenreTest extends TestCase
{
    use DatabaseMigrations;
    use Uuid;

    public function testList()
    {
        factory(Genre::class, 1)->create();
        $model = Genre::all();
        $this->assertCount(1, $model);
        $modelKeys = array_keys($model->first()->getAttributes());
        $this->assertEqualsCanonicalizing(
            [
                'id',
                'name',
                'is_active',
                'created_at',
                'updated_at',
                'deleted_at',
            ],
            $modelKeys);
    }

    public function testCreate()
    {
        $model = Genre::create([
            'name' => 'teste1'
        ]);
        $model->refresh();

        $this->assertEquals('teste1', $model->name);
        $this->assertTrue($model->is_active);
        $this->assertIsUuid($model->id);

        $model = Genre::create([
            'name' => 'teste1',
            'is_active' => false
        ]);
        $this->assertFalse($model->is_active);

        $model = Genre::create([
            'name' => 'teste1',
            'is_active' => true
        ]);
        $this->assertTrue($model->is_active);
    }

    public function testUpdate()
    {
        //** @var Genre $model */
        $model = factory(Genre::class)->create([
            'is_active' => false
        ])->first();
        $data = [
            'name' => 'test_name_updated',
            'is_active' => true
        ];
        $model->update($data);

        foreach ($data as $key => $values) {
            $this->assertEquals($values, $model->{$key});
        }
    }

    public function testDelete()
    {
        //** @var Genre $model */
        $model = factory(Genre::class)->create([
            'is_active' => false
        ])->first();
        $id = $model->id;
        $model->delete();

        $this->assertNull(Genre::find($id));
    }
}
