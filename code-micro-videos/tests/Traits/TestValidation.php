<?php

namespace Tests\Traits;

use Illuminate\Foundation\Testing\TestResponse;

trait TestValidation
{
    protected function assertInvalidationField(
        TestResponse $response,
        array        $fields,
        string       $rule,
        array        $ruleParams = []
    )
    {
        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors($fields);

        foreach ($fields as $field) {
            $fieldName = str_replace('_', ' ', $field);
            $response->assertJsonFragment([
                trans("validation.{$rule}", ['attribute' => $fieldName] + $ruleParams)
            ]);
        }
    }
}
