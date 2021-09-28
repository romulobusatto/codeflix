<?php

namespace App\Http\Controllers\Api;

use App\Models\Video;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VideoController extends BasicCrudController
{
    private $rules;

    public function __construct()
    {
        $this->rules = [
            'title' => 'required|max:255',
            'description' => 'required',
            'rating' => 'required|in:' . implode(',', Video::RATING_LIST),
            'year_launched' => 'required|date_format:Y',
            'duration' => 'required|integer',
            'opened' => 'boolean',
            'categories_id' => 'required|array|exists:categories,id,deleted_at,NULL',
            'genres_id' => 'required|array|exists:genres,id,deleted_at,NULL',
        ];
    }

    public function store(Request $request)
    {
        $validatedData = $this->validate($request, $this->rulesStore());
        unset($validatedData['categories_id'], $validatedData['genres_id']);
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
        unset($validatedData['categories_id'], $validatedData['genres_id']);
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
        $model->genres()->sync($request->get('genres_id'));
    }

    protected function model()
    {
        return Video::class;
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
