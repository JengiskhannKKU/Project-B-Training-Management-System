<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEvaluationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Authorization will be handled by Policy
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'overall_rating' => ['required', 'integer', 'min:1', 'max:5'],
            'content_quality' => ['required', 'integer', 'min:1', 'max:5'],
            'trainer_quality' => ['required', 'integer', 'min:1', 'max:5'],
            'material_quality' => ['required', 'integer', 'min:1', 'max:5'],
            'organization' => ['required', 'integer', 'min:1', 'max:5'],
            'would_recommend' => ['required', 'boolean'],
            'difficulty_level' => ['required', Rule::in(['easy', 'medium', 'hard'])],
            'strengths' => ['required', 'string', 'max:5000'],
            'improvements' => ['required', 'string', 'max:5000'],
            'comments' => ['nullable', 'string', 'max:5000'],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'overall_rating' => 'overall rating',
            'content_quality' => 'content quality',
            'trainer_quality' => 'trainer quality',
            'material_quality' => 'material quality',
            'organization' => 'organization',
            'would_recommend' => 'recommendation',
            'difficulty_level' => 'difficulty level',
            'strengths' => 'strengths',
            'improvements' => 'improvements',
            'comments' => 'comments',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'overall_rating.required' => 'Please provide an overall rating.',
            'overall_rating.min' => 'Rating must be at least 1.',
            'overall_rating.max' => 'Rating must not exceed 5.',
            'would_recommend.required' => 'Please indicate if you would recommend this course.',
            'difficulty_level.in' => 'Difficulty level must be easy, medium, or hard.',
            'strengths.required' => 'Please share what you found valuable about this course.',
            'improvements.required' => 'Please share suggestions for improvement.',
        ];
    }
}
