<?php

namespace Tests\Unit\Models;

use App\Models\Genre;
use Illuminate\Database\Eloquent\SoftDeletes;
use Tests\TestCase;

class GenreTest extends TestCase
{

    private $genre;

    protected function setUp(): void
    {
        parent::setUp();
        $this->genre = new Genre();
    }

    public function testFillableAttributes()
    {
        $fillable = ['name', 'is_active'];
        $this->assertEquals($fillable, $this->genre->getFillable());
    }

    public function testIfUseTraits()
    {
        $fillable = [
            SoftDeletes::class,
            \App\Models\Traits\Uuid::class
        ];
        $traits = array_keys(class_uses(Genre::class));
        $this->assertEquals($fillable, $traits);
    }

    public function testDatesAttributes()
    {
        $dates = ['deleted_at', 'created_at', 'updated_at'];
        foreach ($dates as $data){
            $this->assertContains($data, $this->genre->getDates());
        }
        $this->assertCount(count($dates), $this->genre->getDates());
    }

    public function testKeyTypeAttribute()
    {
        $this->assertEquals('string', $this->genre->getKeyType());
    }

    public function testCastAttribute()
    {
        $this->assertEquals(
            [
                'is_active' => 'boolean'
            ],
            $this->genre->getCasts());
    }

    public function testIncrementingAttribute()
    {
        $this->assertFalse($this->genre->getIncrementing());
    }
}
