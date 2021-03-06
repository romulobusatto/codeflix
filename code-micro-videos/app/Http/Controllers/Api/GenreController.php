<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Genre;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GenreController extends BasicCrudController
{
    private $rules = [
        'name' => 'required|max:255',
        'is_active' => 'boolean',
        'categories_id' => 'required|array|exists:categories,id,deleted_at,NULL',
    ];

    public function store(Request $request)
    {
        $validatedData = $this->validate($request, $this->rulesStore());
        unset($validatedData['categories_id']);
        $self = $this;
        $model = DB::transaction(function () use ($self, $validatedData, $request) {
            $model = $this->model()::create($validatedData);
            $self->handleRelations($model, $request);
            return $model;
        });
        $model->refresh();
        return $model;
    }

    public function update(Request $request, $id)
    {
        $model = $this->findOrFail($id);
        $validatedData = $this->validate($request, $this->rulesUpdate());
        unset($validatedData['categories_id']);
        $self = $this;
        $model = DB::transaction(function () use ($self, $model, $validatedData, $request) {
            $model->update($validatedData);
            $self->handleRelations($model, $request);
            return $model;
        });
        return $model;
    }

    protected function handleRelations($model, Request $request)
    {
        $model->categories()->sync($request->get('categories_id'));
    }

    protected function model()
    {
        return Genre::class;
    }

    protected function rulesStore()
    {
        return $this->rules;
    }

    protected function rulesUpdate()
    {
        return $this->rules;
    }
}
