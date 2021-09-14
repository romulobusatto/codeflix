<?php

namespace Tests\Feature\Models;

use App\Models\CastMember;
use App\Models\Genre;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;
use Tests\Traits\TestUuid;

class CastMemberTest extends TestCase
{
    use DatabaseMigrations;
    use TestUuid;

    public function testList()
    {
        factory(CastMember::class, 1)->create();
        $model = CastMember::all();
        $this->assertCount(1, $model);
        $modelKeys = array_keys($model->first()->getAttributes());
        $this->assertEqualsCanonicalizing(
            [
                'id',
                'name',
                'type',
                'created_at',
                'updated_at',
                'deleted_at',
            ],
            $modelKeys);
    }

    public function testCreate()
    {
        $model = CastMember::create([
            'name' => 'teste1',
            'type' => CastMember::TYPE_ACTOR
        ]);
        $model->refresh();

        $this->assertEquals('teste1', $model->name);
        $this->assertEquals(CastMember::TYPE_ACTOR, $model->type);
        $this->assertIsUuid($model->id);

        $model = CastMember::create([
            'name' => 'teste1',
            'type' => CastMember::TYPE_DIRECTOR
        ]);
        $this->assertEquals(CastMember::TYPE_DIRECTOR, $model->type);
    }

    public function testUpdate()
    {
        //** @var CastMember $model */
        $model = factory(CastMember::class)->create([
            'name' => 'teste1',
            'type' => CastMember::TYPE_ACTOR
        ]);
        $data = [
            'name' => 'test_name_updated',
            'type' => CastMember::TYPE_DIRECTOR
        ];
        $model->update($data);

        foreach ($data as $key => $values) {
            $this->assertEquals($values, $model->{$key});
        }
    }

    public function testDelete()
    {
        //** @var CastMember $model */
        $model = factory(CastMember::class)->create();
        $model->delete();
        $this->assertNull(CastMember::find($model->id));

        $model->restore();
        $this->assertNotNull(CastMember::find($model->id));
    }
}
