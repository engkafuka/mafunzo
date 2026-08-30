<?php

namespace App\Http\Controllers\Interview;

use App\Http\Controllers\Controller;
use App\Models\InterviewQuestion;
use App\Models\InterviewQuestionSet;
use App\Support\Interview\InterviewAuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class QuestionSetController extends Controller
{
    public function index(): View
    {
        $questionSets = InterviewQuestionSet::withCount('questions')
            ->orderByDesc('id')
            ->paginate(20);

        return view('interview.question-sets.index', compact('questionSets'));
    }

    public function create(): View
    {
        return view('interview.question-sets.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'version' => ['required', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $set = InterviewQuestionSet::create([
            ...$data,
            'is_active' => $request->boolean('is_active', true),
            'created_by' => $request->user()->id,
        ]);

        InterviewAuditLogger::log('question_set_created', 'Interview question set created', $request->user(), null, [
            'question_set_id' => $set->id,
        ]);

        return redirect()->route('interview.question-sets.show', $set)->with('status', __('Question set created.'));
    }

    public function show(InterviewQuestionSet $questionSet): View
    {
        $questionSet->load('questions');

        return view('interview.question-sets.show', compact('questionSet'));
    }

    public function edit(InterviewQuestionSet $questionSet): View
    {
        return view('interview.question-sets.edit', compact('questionSet'));
    }

    public function update(Request $request, InterviewQuestionSet $questionSet): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'version' => ['required', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $questionSet->update([
            ...$data,
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()->route('interview.question-sets.show', $questionSet)->with('status', __('Question set updated.'));
    }

    public function storeQuestion(Request $request, InterviewQuestionSet $questionSet): RedirectResponse
    {
        $data = $request->validate([
            'category' => ['required', 'string', 'max:100'],
            'question_text' => ['required', 'string'],
            'max_mark' => ['required', 'integer', 'min:1', 'max:100'],
            'weight' => ['nullable', 'numeric', 'min:0'],
            'rubric' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        InterviewQuestion::create([
            ...$data,
            'question_set_id' => $questionSet->id,
            'weight' => $data['weight'] ?? 1,
            'sort_order' => $data['sort_order'] ?? ($questionSet->questions()->max('sort_order') + 1),
            'is_active' => true,
        ]);

        return back()->with('status', __('Question added.'));
    }

    public function updateQuestion(Request $request, InterviewQuestionSet $questionSet, InterviewQuestion $question): RedirectResponse
    {
        abort_unless($question->question_set_id === $questionSet->id, 404);

        $data = $request->validate([
            'category' => ['required', 'string', 'max:100'],
            'question_text' => ['required', 'string'],
            'max_mark' => ['required', 'integer', 'min:1', 'max:100'],
            'weight' => ['nullable', 'numeric', 'min:0'],
            'rubric' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $question->update([
            ...$data,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return back()->with('status', __('Question updated.'));
    }
}
