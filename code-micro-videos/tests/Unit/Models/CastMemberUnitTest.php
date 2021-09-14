<?php

namespace Tests\Unit\Models;

use App\Models\CastMember;
use Illuminate\Database\Eloquent\SoftDeletes;
use Tests\TestCase;

class CastMemberUnitTest extends TestCase
{

    private $castMember;

    protected function setUp(): void
    {
        parent::setUp();
        $this->castMember = new CastMember();
    }

    public function testFillableAttributes()
    {
        $fillable = ['name', 'type'];
        $this->assertEquals($fillable, $this->castMember->getFillable());
    }

    public function testIfUseTraits()
    {
        $fillable = [
            SoftDeletes::class,
            \App\Models\Traits\Uuid::class
        ];
        $traits = array_keys(class_uses(CastMember::class));
        $this->assertEquals($fillable, $traits);
    }

    public function testDatesAttributes()
    {
        $dates = ['deleted_at', 'created_at', 'updated_at'];
        foreach ($dates as $data){
            $this->assertContains($data, $this->castMember->getDates());
        }
        $this->assertCount(count($dates), $this->castMember->getDates());
    }

    public function testKeyTypeAttribute()
    {
        $this->assertEquals('string', $this->castMember->getKeyType());
    }

    public function testCastAttribute()
    {
        $this->assertEquals(
            [
                'name' => 'string',
                'type' => 'integer',
            ],
            $this->castMember->getCasts());
    }

    public function testIncrementingAttribute()
    {
        $this->assertFalse($this->castMember->getIncrementing());
    }
}
