<?php

namespace Tests\Feature\Http\Controllers\Api;


use App\Http\Controllers\Api\BasicCrudController;
use Illuminate\Http\Request;
use Tests\Stubs\Controllers\CategoryControllerStub;
use Tests\Stubs\Models\CategoryStub;
use Tests\TestCase;


class BasicCrudControllerTest extends TestCase
{
    private $controller;
    private $model;

    protected function setUp(): void
    {
        parent::setUp();
        CategoryStub::dropTable();
        CategoryStub::createTable();
        $this->controller = new CategoryControllerStub();
        $this->model = CategoryStub::create(['name' => 'Test', 'description' => 'Test']);
    }

    protected function tearDown(): void
    {
        CategoryStub::dropTable();
        parent::tearDown();
    }

    public function testIndex()
    {
        $result = $this->controller->index()->toArray();
        $this->assertEquals([$this->model->toArray()], $result);
    }

    public function testInvalidationDataInStore()
    {
        $this->expectException(\Illuminate\Validation\ValidationException::class);

        $request = \Mockery::mock(Request::class);
        $request
            ->shouldReceive('all')
            ->once()
            ->andReturn(['name' => '']);
        $this->controller->store($request);
    }

    public function testStore()
    {
        $request = \Mockery::mock(Request::class);
        $request
            ->shouldReceive('all')
            ->once()
            ->andReturn(['name' => 'test_name', 'description' => 'test_description']);
        $obj = $this->controller->store($request);
        $this->assertEquals($obj->toArray(), CategoryStub::find($this->model->id + 1)->toArray());
    }

    public function testFindOrFailFetchModel()
    {
        $reflectionClass = new \ReflectionClass(BasicCrudController::class);
        $reflectionMethod = $reflectionClass->getMethod('findOrFail');
        $reflectionMethod->setAccessible(true);

        $result = $reflectionMethod->invokeArgs($this->controller, [$this->model->id]);
        $this->assertInstanceOf(CategoryStub::class, $result);
    }

    public function testFindOrFailThrowExceptionWhenIdInvalid()
    {
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        $reflectionClass = new \ReflectionClass(BasicCrudController::class);
        $reflectionMethod = $reflectionClass->getMethod('findOrFail');
        $reflectionMethod->setAccessible(true);

        $reflectionMethod->invokeArgs($this->controller, [0]);
    }

    public function testShow()
    {
        $obj = $this->controller->show($this->model->id);
        $this->assertEquals($this->model->toArray(), $obj->toArray());
    }

    public function testInvalidationDataInUpdate()
    {
        $this->expectException(\Illuminate\Validation\ValidationException::class);

        $request = \Mockery::mock(Request::class);
        $request
            ->shouldReceive('all')
            ->once()
            ->andReturn(['name' => '']);
        $this->controller->update($request, $this->model->id);
    }

    public function testUpdate()
    {
        $request = \Mockery::mock(Request::class);
        $request
            ->shouldReceive('all')
            ->twice()
            ->andReturn(['name' => 'test_name_update', 'description' => 'test_description_update']);
        $obj = $this->controller->update($request, $this->model->id);
        $this->assertNotEquals($obj->name, $this->model->name);
        $this->model->refresh();
        $this->assertEquals($obj->toArray(), $this->model->toArray());
    }

    public function testDestroy()
    {
        $reponse = $this->controller->destroy($this->model->id);

        $this->createTestResponse($reponse)
            ->assertStatus(204);
        $this->assertNull(CategoryStub::find($this->model->id));
    }
}
