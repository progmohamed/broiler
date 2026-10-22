<?php

namespace App;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use JsonException;

class FarmAssistant
{
    private const MAX_TOOL_CALLS = 4;

    public function __construct(private FarmReportBuilder $reportBuilder) {}

    /**
     * @param  list<array{question: string, answer: string}>  $history
     * @return array<string, mixed>
     */
    public function answer(string $question, array $history = []): array
    {
        $key = config('services.groq.key');

        if (! is_string($key) || trim($key) === '') {
            throw new FarmAssistantException('المساعد غير مفعّل بعد. يلزم إعداد مفتاح Groq على الخادم.', 503);
        }

        $messages = [['role' => 'system', 'content' => $this->instructions()]];

        foreach (array_slice($history, -6) as $turn) {
            $messages[] = ['role' => 'user', 'content' => $turn['question']];
            $messages[] = ['role' => 'assistant', 'content' => $turn['answer']];
        }

        $messages[] = ['role' => 'user', 'content' => $question];
        $reports = [];
        $toolCallCount = 0;

        while (true) {
            $message = $this->request($key, $messages);
            $toolCalls = is_array($message['tool_calls'] ?? null) ? $message['tool_calls'] : [];

            if ($toolCalls === []) {
                $content = $message['content'] ?? null;

                if (! is_string($content) || trim($content) === '') {
                    throw new FarmAssistantException('لم يكتمل فهم السؤال. حاول صياغته بصورة أقصر وأكثر تحديدًا.');
                }

                return $this->combineReports(trim($content), $reports);
            }

            $messages[] = ['role' => 'assistant', 'content' => $message['content'] ?? null, 'tool_calls' => $toolCalls];

            foreach ($toolCalls as $toolCall) {
                if ($toolCallCount >= self::MAX_TOOL_CALLS) {
                    throw new FarmAssistantException('احتاج السؤال إلى استعلامات كثيرة. قسّمه إلى سؤالين أقصر.');
                }

                $toolCallCount++;
                $callId = is_string($toolCall['id'] ?? null) ? $toolCall['id'] : 'call-'.$toolCallCount;
                $name = $toolCall['function']['name'] ?? null;
                $arguments = $this->decodeArguments($toolCall['function']['arguments'] ?? null);

                if ($name !== 'read_farm_report' || $arguments === null) {
                    $result = ['error' => 'طلب الأداة غير صالح. استخدم read_farm_report بالحقول المحددة.'];
                } else {
                    $report = $this->runReport($arguments);
                    $reports[] = $report;
                    $result = $this->toolResult($report);
                }

                $messages[] = [
                    'role' => 'tool', 'tool_call_id' => $callId, 'name' => 'read_farm_report',
                    'content' => json_encode($result, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                ];
            }
        }

    }

    /** @param list<array<string, mixed>> $messages */
    private function request(string $key, array $messages): array
    {
        try {
            $response = Http::withToken($key)->acceptJson()->connectTimeout(5)->timeout(30)
                ->post('https://api.groq.com/openai/v1/chat/completions', [
                    'model' => config('services.groq.model'), 'messages' => $messages,
                    'tools' => [$this->reportTool()], 'tool_choice' => 'auto', 'parallel_tool_calls' => false,
                    'temperature' => 0.1, 'max_completion_tokens' => 1600,
                ]);
        } catch (ConnectionException) {
            throw new FarmAssistantException('تعذّر الاتصال بالمساعد في الوقت المحدد. حاول مرة أخرى.', 503);
        }

        if (! $response->successful()) {
            throw new FarmAssistantException(
                $response->status() === 429
                    ? 'خدمة المساعد مشغولة أو تجاوزت حصتها المتاحة. حاول لاحقًا أو راجع إعدادات الخدمة.'
                    : 'تعذّر تشغيل خدمة المساعد. راجع إعدادات الخدمة أو حاول لاحقًا.',
                503,
            );
        }

        $message = $response->json('choices.0.message');

        if (! is_array($message)) {
            throw new FarmAssistantException('لم يكتمل فهم السؤال. حاول صياغته بصورة أقصر وأكثر تحديدًا.');
        }

        return $message;
    }

    private function instructions(): string
    {
        $today = today()->toDateString();
        $timezone = config('app.timezone');

        return <<<TEXT
            أنت مساعد عربي لمزرعة دجاج بياض. تاريخ اليوم {$today} والمنطقة الزمنية {$timezone}.
            أجب بأسلوب محادثة واضح ومباشر. لديك أداة قراءة فقط اسمها read_farm_report تصل إلى كل البيانات المسجلة.
            لأي سؤال عن أرقام المزرعة أو الأسماء أو الموردين أو المشترين أو المخزون أو المعاملات، يجب أن تستخدم الأداة قبل الإجابة. لا تخمّن بيانات المزرعة.
            يمكنك استدعاء الأداة أكثر من مرة للمقارنة أو الجمع بين أنواع بيانات مختلفة. لا تطلب SQL ولا تحاول تعديل السجلات.
            عند سؤال الأسماء استخدم result_mode=list، وعند سؤال الأكثر أو الأقل استخدم ranking وحدد ranking_direction، وعند سؤال العدد أو الإجمالي استخدم totals.
            آخر 7 أيام تشمل اليوم والستة السابقة. هذا الشهر يبدأ بأول الشهر وينتهي اليوم. عند عدم ذكر فترة للأسماء أو الترتيب استخدم آخر 366 يومًا، ولغير ذلك آخر 30 يومًا.
            البيانات تسجل قطيعًا واحدًا على مستوى المزرعة. لا توجد عنابر متعددة أو أسباب نافق أو حالات مرضية منظمة؛ اشرح ذلك بصدق إذا سُئلت عنها.
            بعد نتائج الأداة أجب عن السؤال نفسه، واذكر أهم الأرقام أو الأسماء، ولا تسرد كل تفاصيل الأداة إذا لم يطلبها المستخدم.
            TEXT;
    }

    /** @return array<string, mixed> */
    private function reportTool(): array
    {
        $properties = [
            'report' => ['type' => 'string', 'enum' => array_values(array_diff(FarmReportPlanner::REPORTS, ['general', 'unsupported']))],
            'target' => ['type' => ['string', 'null']],
            'supplier' => ['type' => ['string', 'null']],
            'payment_method' => ['type' => ['string', 'null'], 'enum' => [null, 'cash', 'bank_transfer', 'credit']],
            'payment_status' => ['type' => ['string', 'null'], 'enum' => [null, 'paid', 'pending']],
            'result_mode' => ['type' => 'string', 'enum' => ['details', 'list', 'ranking', 'totals']],
            'ranking_direction' => ['type' => 'string', 'enum' => ['asc', 'desc']],
            'start_date' => ['type' => 'string'], 'end_date' => ['type' => 'string'],
            'group_by' => ['type' => 'string', 'enum' => ['day', 'week', 'month']],
            'comparison' => ['type' => 'string', 'enum' => ['none', 'previous_period']],
            'missing_data' => ['type' => 'string', 'enum' => ['none', 'mortality_causes']],
        ];

        return [
            'type' => 'function',
            'function' => [
                'name' => 'read_farm_report',
                'description' => 'اقرأ بيانات المزرعة المحلية. التقارير: production، mortality، feed_efficiency، feed_purchases، egg_sales، other_sales، expenses، worker_advances، inventory، finance، summary. target للعميل أو العامل أو تصنيف المصروف، وsupplier لمورد العلف.',
                'parameters' => ['type' => 'object', 'properties' => $properties, 'required' => array_keys($properties), 'additionalProperties' => false],
            ],
        ];
    }

    /** @return array<string, mixed>|null */
    private function decodeArguments(mixed $arguments): ?array
    {
        if (! is_string($arguments)) {
            return null;
        }

        try {
            $decoded = json_decode($arguments, true, 32, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return null;
        }

        return is_array($decoded) ? $decoded : null;
    }

    /** @param array<string, mixed> $arguments */
    private function runReport(array $arguments): array
    {
        $reports = array_values(array_diff(FarmReportPlanner::REPORTS, ['general', 'unsupported']));
        $allowedArguments = [
            'report', 'target', 'supplier', 'payment_method', 'payment_status', 'result_mode',
            'ranking_direction', 'start_date', 'end_date', 'group_by', 'comparison', 'missing_data',
        ];

        if (array_diff(array_keys($arguments), $allowedArguments) !== []) {
            throw new FarmAssistantException('معايير قراءة البيانات غير صالحة.');
        }

        $validator = Validator::make($arguments, [
            'report' => ['required', Rule::in($reports)], 'target' => ['present', 'nullable', 'string', 'max:150'],
            'supplier' => ['present', 'nullable', 'string', 'max:150'],
            'payment_method' => ['present', 'nullable', Rule::in(['cash', 'bank_transfer', 'credit'])],
            'payment_status' => ['present', 'nullable', Rule::in(['paid', 'pending'])],
            'result_mode' => ['required', Rule::in(['details', 'list', 'ranking', 'totals'])],
            'ranking_direction' => ['required', Rule::in(['asc', 'desc'])],
            'start_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:1900-01-01'],
            'end_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:start_date', 'before_or_equal:'.today()->toDateString()],
            'group_by' => ['required', Rule::in(['day', 'week', 'month'])],
            'comparison' => ['required', Rule::in(['none', 'previous_period'])],
            'missing_data' => ['required', Rule::in(['none', 'mortality_causes'])],
        ]);

        if ($validator->fails()) {
            throw new FarmAssistantException('معايير قراءة البيانات غير صالحة.');
        }

        $plan = $validator->validated();

        if (Carbon::parse($plan['start_date'])->diffInDays(Carbon::parse($plan['end_date'])) >= 366) {
            throw new FarmAssistantException('الفترة طويلة جدًا. اختر فترة لا تتجاوز 366 يومًا.');
        }

        $plan['scope'] = 'farm';
        $plan['answer'] = null;

        return $this->reportBuilder->build($plan);
    }

    /** @return array<string, mixed> */
    private function toolResult(array $report): array
    {
        $result = $report;
        $result['tables'] = array_map(function (array $table): array {
            $table['rows'] = array_slice($table['rows'], 0, 50);

            return $table;
        }, $report['tables']);
        $result['charts'] = array_map(function (array $chart): array {
            $chart['points'] = array_slice($chart['points'], 0, 60);

            return $chart;
        }, $report['charts']);

        return $result;
    }

    /** @param list<array<string, mixed>> $reports */
    private function combineReports(string $answer, array $reports): array
    {
        if ($reports === []) {
            return ['title' => 'إجابة المساعد', 'period' => today()->toDateString(), 'answer' => $answer, 'notes' => [], 'metrics' => [], 'tables' => [], 'charts' => []];
        }

        return [
            'title' => count($reports) === 1 ? $reports[0]['title'] : 'تحليل بيانات المزرعة',
            'period' => implode(' | ', array_values(array_unique(array_column($reports, 'period')))),
            'answer' => $answer,
            'notes' => array_slice(array_values(array_unique(array_merge(...array_column($reports, 'notes')))), 0, 12),
            'metrics' => array_slice(array_merge(...array_column($reports, 'metrics')), 0, 12),
            'tables' => array_slice(array_merge(...array_column($reports, 'tables')), 0, 8),
            'charts' => array_slice(array_merge(...array_column($reports, 'charts')), 0, 4),
        ];
    }
}
