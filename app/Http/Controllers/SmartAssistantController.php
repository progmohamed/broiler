<?php

namespace App\Http\Controllers;

use App\FarmAssistant;
use App\FarmAssistantException;
use App\Http\Requests\AskFarmAssistantRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class SmartAssistantController extends Controller
{
    public function index(): View
    {
        return view('smart-assistant.index', [
            'enabled' => filled(config('services.groq.key')),
            'report' => null,
            'question' => null,
        ]);
    }

    public function store(AskFarmAssistantRequest $request, FarmAssistant $assistant): JsonResponse|View|RedirectResponse
    {
        $question = $request->validated('question');

        try {
            $report = $assistant->answer(
                $question,
                $request->session()->get('farm_assistant_history', []),
            );
        } catch (FarmAssistantException $exception) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $exception->getMessage()], $exception->status);
            }

            return to_route('smart-assistant.index')->withInput()->withErrors(['question' => $exception->getMessage()]);
        }

        $history = $request->session()->get('farm_assistant_history', []);
        $history[] = ['question' => $question, 'answer' => $report['answer']];
        $request->session()->put('farm_assistant_history', array_slice($history, -6));
        $reportId = 'assistant-'.Str::uuid();

        if ($request->expectsJson()) {
            return response()->json([
                'report' => $report,
                'html' => view('smart-assistant.report', compact('report', 'reportId'))->render(),
            ])->header('Cache-Control', 'no-store, private');
        }

        return view('smart-assistant.index', [
            'enabled' => true,
            'report' => $report,
            'reportId' => $reportId,
            'question' => $question,
        ]);
    }

    public function destroy(Request $request): JsonResponse|RedirectResponse
    {
        $request->session()->forget(['farm_assistant_history', 'farm_assistant_plan']);

        return $request->expectsJson()
            ? response()->json(['message' => 'بدأت محادثة جديدة.'])
            : to_route('smart-assistant.index');
    }
}
