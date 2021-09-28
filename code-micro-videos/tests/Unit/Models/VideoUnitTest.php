<?php

namespace Tests\Unit\Models;

use App\Models\Video;
use Illuminate\Database\Eloquent\SoftDeletes;
use Tests\TestCase;

class VideoUnitTest extends TestCase
{

    private $model;

    protected function setUp(): void
    {
        parent::setUp();
        $this->model = new Video();
    }

    public function testFillableAttributes()
    {
        $fillable = ['title',
            'description',
            'rating',
            'year_launched',
            'duration',
            'opened'
        ];
        $this->assertEquals($fillable, $this->model->getFillable());
    }

    public function testIfUseTraits()
    {
        $fillable = [
            SoftDeletes::class,
            \App\Models\Traits\Uuid::class
        ];
        $traits = array_keys(class_uses(Video::class));
        $this->assertEquals($fillable, $traits);
    }

    public function testDatesAttributes()
    {
        $dates = ['deleted_at', 'created_at', 'updated_at'];
        foreach ($dates as $data) {
            $this->assertContains($data, $this->model->getDates());
        }
        $this->assertCount(count($dates), $this->model->getDates());
    }

    public function testKeyTypeAttribute()
    {
        $this->assertEquals('string', $this->model->getKeyType());
    }

    public function testCastAttribute()
    {
        $this->assertEquals(
            [
                'id' => 'string',
                'title' => 'string',
                'description' => 'string',
                'rating' => 'string',
                'year_launched' => 'integer',
                'duration' => 'integer',
                'opened' => 'boolean',
            ],
            $this->model->getCasts());
    }

    public function testIncrementingAttribute()
    {
        $this->assertFalse($this->model->getIncrementing());
    }

    public function testCategories()
    {
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $this->model->categories);
    }

    public function testGenres()
    {
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $this->model->genres);
    }
}
