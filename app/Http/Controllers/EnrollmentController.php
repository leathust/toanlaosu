<?php

namespace App\Http\Controllers;

use App\Actions\EnrollStudent;
use App\Http\Requests\StoreEnrollmentRequest;
use App\Models\Course;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class EnrollmentController extends Controller
{
    public function store(StoreEnrollmentRequest $request, Course $course, EnrollStudent $enrollStudent): JsonResponse
    {
        $student = User::query()->findOrFail($request->integer('user_id'));
        $enrollment = $enrollStudent->execute($request->user(), $course, $student, $request->validated());

        return response()->json(['data' => $enrollment], 201);
    }
}
