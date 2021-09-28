<?php

namespace Tests\Unit\Models;

use App\Models\Category;
use Illuminate\Database\Eloquent\SoftDeletes;
use Tests\TestCase;

class CategoryUnitTest extends TestCase
{

    private $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->category = new Category();
    }

    public function testFillableAttributes()
    {
        $fillable = ['name', 'description', 'is_active'];
        $this->assertEquals($fillable, $this->category->getFillable());
    }

    public function testIfUseTraits()
    {
        $fillable = [
            SoftDeletes::class,
            \App\Models\Traits\Uuid::class
        ];
        $categoryTraits = array_keys(class_uses(Category::class));
        $this->assertEquals($fillable, $categoryTraits);
    }

    public function testDatesAttributes()
    {
        $dates = ['deleted_at', 'created_at', 'updated_at'];
        foreach ($dates as $data){
            $this->assertContains($data, $this->category->getDates());
        }
        $this->assertCount(count($dates), $this->category->getDates());
    }

    public function testKeyTypeAttribute()
    {
        $this->assertEquals('string', $this->category->getKeyType());
    }

    public function testCastAttribute()
    {
        $this->assertEquals(
            [
                'id' => 'string',
                'is_active' => 'boolean'
            ],
            $this->category->getCasts());
    }

    public function testIncrementingAttribute()
    {
        $this->assertFalse($this->category->getIncrementing());
    }
}
