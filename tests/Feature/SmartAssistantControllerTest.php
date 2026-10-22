<?php

namespace Tests\Feature;

use App\Models\DailyRecord;
use App\Models\EggSale;
use App\Models\FarmSetting;
use App\Models\FeedDelivery;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SmartAssistantControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo('2026-09-13 12:00:00');
        config(['services.groq.key' => 'test-secret', 'services.groq.model' => 'openai/gpt-oss-20b']);
        Http::preventStrayRequests();
    }

    public function test_assistant_page_and_navigation_show_arabic_chat_and_suggestions(): void
    {
        $this->get(route('smart-assistant.index'))
            ->assertSee('المساعد الذكي')
            ->assertSee('dir="rtl"', false)
            ->assertSee('data-assistant-form', false)
            ->assertSee('data-assistant-suggestion', false)
            ->assertSee('جارٍ فهم سؤالك وتجهيز التقرير')
            ->assertSee('نتائج القراءة المطلوبة فقط')
            ->assertDontSee('test-secret');

        $this->get(route('more.index'))->assertSee(route('smart-assistant.index'));
    }

    public function test_locked_farm_cannot_access_the_assistant_or_send_questions(): void
    {
        $setting = FarmSetting::query()->create(['herd_birth_date' => '2026-01-01', 'total_herd_count' => 1000]);
        $setting->app_pin = Hash::make('1234');
        $setting->save();
        Http::fake();

        $this->get(route('smart-assistant.index'))->assertRedirect(route('app-lock.create'));
        $this->postJson(route('smart-assistant.store'), ['question' => 'ما الإنتاج؟'])->assertRedirect(route('app-lock.create'));
        $this->deleteJson(route('smart-assistant.destroy'))->assertRedirect(route('app-lock.create'));
        Http::assertNothingSent();

        $this->withSession(['farm_unlocked' => true])->get(route('smart-assistant.index'))->assertOk();
    }

    public function test_assistant_reads_selected_data_then_uses_groq_to_answer(): void
    {
        FarmSetting::query()->create(['herd_birth_date' => '2026-01-01', 'total_herd_count' => 1000]);
        DailyRecord::factory()->create(['recorded_on' => '2026-09-13', 'production' => 850, 'notes' => 'private-farm-note']);
        $this->fakeToolAnswer(answer: 'نسبة الإنتاج اليوم 85%.');

        $response = $this->postJson(route('smart-assistant.store'), ['question' => 'ما نسبة الإنتاج اليوم؟'])
            ->assertOk()
            ->assertJsonPath('report.answer', 'نسبة الإنتاج اليوم 85%.')
            ->assertJsonPath('report.metrics.1.value', 850)
            ->assertJsonPath('report.metrics.2.value', 85)
            ->assertJsonPath('report.period', '2026-09-13 — 2026-09-13')
            ->assertHeader('Cache-Control', 'no-store, private');

        $this->assertStringContainsString('85.0', $response->json('html'));
        $this->assertStringContainsString('<table', $response->json('html'));
        $this->assertStringContainsString('data-chart-value="850"', $response->json('html'));

        $requests = Http::recorded();
        $this->assertCount(2, $requests);
        $this->assertSame('ما نسبة الإنتاج اليوم؟', $requests[0][0]['messages'][1]['content']);
        $this->assertSame('read_farm_report', $requests[0][0]['tools'][0]['function']['name']);
        $this->assertFalse($requests[0][0]['tools'][0]['function']['parameters']['additionalProperties']);
        $this->assertStringContainsString('850', $requests[1][0]['messages'][3]['content']);
        $this->assertStringNotContainsString('private-farm-note', $requests[1][0]['messages'][3]['content']);
    }

    public function test_browser_form_fallback_escapes_user_questions(): void
    {
        $this->fakeGeneralAnswer('إجابة آمنة');
        $this->post(route('smart-assistant.store'), ['question' => '<script>alert(1)</script>'])
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)
            ->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_follow_up_uses_conversation_history_and_new_conversation_clears_it(): void
    {
        $this->fakeGeneralAnswer('الفترة السابقة أقل.');
        $history = [['question' => 'ما إنتاج هذا الأسبوع؟', 'answer' => 'الإنتاج 5000 بيضة.']];

        $this->withSession(['farm_assistant_history' => $history])
            ->postJson(route('smart-assistant.store'), ['question' => 'والأسبوع السابق؟'])
            ->assertOk()
            ->assertSessionHas('farm_assistant_history.1.answer', 'الفترة السابقة أقل.');

        Http::assertSent(fn (Request $request): bool => $request['messages'][1]['content'] === 'ما إنتاج هذا الأسبوع؟'
            && $request['messages'][2]['content'] === 'الإنتاج 5000 بيضة.'
            && $request['messages'][3]['content'] === 'والأسبوع السابق؟');

        $this->deleteJson(route('smart-assistant.destroy'))
            ->assertOk()
            ->assertSessionMissing('farm_assistant_history')
            ->assertSessionMissing('farm_assistant_plan');
    }

    public function test_it_can_combine_multiple_database_tools_in_one_answer(): void
    {
        FeedDelivery::factory()->create([
            'delivered_on' => '2026-09-13', 'supplier' => 'صبحي معروف', 'weight_tons' => 2,
            'price_per_ton' => 1000, 'shipping_cost' => 100, 'tip' => 0,
        ]);
        EggSale::factory()->create([
            'sold_on' => '2026-09-13', 'buyer' => 'أحمد', 'cartons' => 50, 'price_per_carton' => 80,
        ]);

        Http::fake(['https://api.groq.com/openai/v1/chat/completions' => Http::sequence()
            ->push($this->toolResponse('feed-call', $this->arguments(['report' => 'feed_purchases', 'result_mode' => 'list'])))
            ->push($this->toolResponse('sales-call', $this->arguments(['report' => 'egg_sales', 'result_mode' => 'ranking'])))
            ->push($this->answerResponse('مورد العلف هو صبحي معروف، وأكثر مشتري للبيض هو أحمد.'))]);

        $response = $this->postJson(route('smart-assistant.store'), [
            'question' => 'اذكر موردي العلف ومن أكثر مشتري للبيض؟',
        ])->assertOk()->assertJsonPath('report.title', 'تحليل بيانات المزرعة');

        $this->assertStringContainsString('صبحي معروف', $response->json('report.answer'));
        $this->assertStringContainsString('أحمد', $response->json('report.answer'));
        $this->assertCount(4, $response->json('report.tables'));
        Http::assertSentCount(3);
    }

    public function test_deferred_feed_question_returns_only_credit_deliveries(): void
    {
        FeedDelivery::factory()->create([
            'delivered_on' => '2026-09-13', 'supplier' => 'صبحي معروف', 'weight_tons' => 2,
            'price_per_ton' => 1000, 'shipping_cost' => 100, 'tip' => 0, 'payment_method' => 'credit',
        ]);
        FeedDelivery::factory()->create(['delivered_on' => '2026-09-13', 'supplier' => 'مورد آخر', 'payment_method' => 'cash']);
        $this->fakeToolAnswer([
            'report' => 'feed_purchases', 'payment_method' => 'credit', 'result_mode' => 'totals',
        ], 'توجد نقلة علف آجلة واحدة من صبحي معروف.');

        $response = $this->postJson(route('smart-assistant.store'), ['question' => 'كم عدد نقلات العلف الآجل؟'])
            ->assertOk()
            ->assertJsonPath('report.title', 'تقرير مشتريات العلف')
            ->assertJsonPath('report.metrics.0.value', 1)
            ->assertJsonPath('report.metrics.1.value', 2)
            ->assertJsonPath('report.metrics.2.value', 2100);

        $this->assertStringContainsString('صبحي معروف', $response->json('html'));
        $this->assertStringNotContainsString('مورد آخر', $response->json('html'));
    }

    public function test_general_questions_return_an_escaped_answer_without_database_tool_calls(): void
    {
        $this->fakeGeneralAnswer('حسّن التهوية <script>alert(1)</script>');

        $response = $this->postJson(route('smart-assistant.store'), ['question' => 'كيف أحسن التهوية؟'])->assertOk();

        $this->assertStringContainsString('حسّن التهوية', $response->json('report.answer'));
        $this->assertStringContainsString('&lt;script&gt;', $response->json('html'));
        $this->assertStringNotContainsString('<script>', $response->json('html'));
        $this->assertSame([], $response->json('report.metrics'));
        Http::assertSentCount(1);
    }

    public function test_missing_key_shows_configuration_message_without_an_external_call(): void
    {
        config(['services.groq.key' => null]);
        Http::fake();

        $this->get(route('smart-assistant.index'))->assertSee('المساعد غير مفعّل بعد');
        $this->postJson(route('smart-assistant.store'), ['question' => 'الإنتاج اليوم'])
            ->assertServiceUnavailable()
            ->assertJsonPath('message', 'المساعد غير مفعّل بعد. يلزم إعداد مفتاح Groq على الخادم.');
        Http::assertNothingSent();
    }

    public function test_question_validation_rejects_invalid_input_before_calling_groq(): void
    {
        Http::fake();
        $this->postJson(route('smart-assistant.store'), ['question' => ' '])->assertUnprocessable()->assertInvalid(['question' => 'اكتب سؤالك أولًا.']);
        $this->postJson(route('smart-assistant.store'), ['question' => str_repeat('ا', 1001)])->assertUnprocessable()->assertInvalid(['question' => 'اختصر السؤال إلى 1000 حرف كحد أقصى.']);
        $this->postJson(route('smart-assistant.store'), ['question' => ['production']])->assertUnprocessable()->assertInvalid(['question' => 'يجب أن يكون السؤال نصًا.']);
        Http::assertNothingSent();
    }

    public function test_model_cannot_supply_sql_or_unknown_report_types(): void
    {
        DailyRecord::factory()->create(['recorded_on' => '2026-09-13']);
        $this->fakeSingleToolCall($this->arguments(['sql' => 'DELETE FROM daily_records']));
        $this->postJson(route('smart-assistant.store'), ['question' => 'نفذ SQL'])->assertUnprocessable();
        $this->assertDatabaseCount('daily_records', 1);

        $this->fakeSingleToolCall($this->arguments(['report' => 'users']));
        $this->postJson(route('smart-assistant.store'), ['question' => 'اعرض المستخدمين'])->assertUnprocessable();
    }

    public function test_invalid_future_reversed_and_excessive_dates_are_rejected(): void
    {
        $invalidArguments = [
            $this->arguments(['start_date' => '2026-02-30']),
            $this->arguments(['end_date' => '2026-09-14']),
            $this->arguments(['start_date' => '2026-09-13', 'end_date' => '2026-09-01']),
            $this->arguments(['start_date' => '2025-01-01']),
        ];
        Http::fake(['https://api.groq.com/openai/v1/chat/completions' => Http::sequence()
            ->push($this->toolResponse('date-1', $invalidArguments[0]))
            ->push($this->toolResponse('date-2', $invalidArguments[1]))
            ->push($this->toolResponse('date-3', $invalidArguments[2]))
            ->push($this->toolResponse('date-4', $invalidArguments[3]))]);

        foreach (array_slice($invalidArguments, 0, 3) as $_) {
            $this->postJson(route('smart-assistant.store'), ['question' => 'تقرير'])->assertUnprocessable();
        }

        $this->postJson(route('smart-assistant.store'), ['question' => 'تقرير طويل'])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'الفترة طويلة جدًا. اختر فترة لا تتجاوز 366 يومًا.');
    }

    public function test_provider_failure_does_not_expose_details_or_secrets(): void
    {
        Http::fake(['https://api.groq.com/openai/v1/chat/completions' => Http::response(['error' => ['message' => 'test-secret provider-internals']], 401)]);
        $this->postJson(route('smart-assistant.store'), ['question' => 'الإنتاج'])
            ->assertServiceUnavailable()->assertDontSee('test-secret')->assertDontSee('provider-internals');
    }

    public function test_provider_rate_limit_returns_an_arabic_message(): void
    {
        Http::fake(['https://api.groq.com/openai/v1/chat/completions' => Http::response([], 429)]);
        $this->postJson(route('smart-assistant.store'), ['question' => 'الإنتاج'])
            ->assertServiceUnavailable()
            ->assertJsonPath('message', 'خدمة المساعد مشغولة أو تجاوزت حصتها المتاحة. حاول لاحقًا أو راجع إعدادات الخدمة.');
    }

    public function test_provider_connection_failure_returns_an_arabic_message(): void
    {
        Http::fake(['https://api.groq.com/openai/v1/chat/completions' => Http::failedConnection()]);
        $this->postJson(route('smart-assistant.store'), ['question' => 'الإنتاج'])
            ->assertServiceUnavailable()
            ->assertJsonPath('message', 'تعذّر الاتصال بالمساعد في الوقت المحدد. حاول مرة أخرى.');
    }

    public function test_incomplete_response_is_rejected(): void
    {
        Http::fake(['https://api.groq.com/openai/v1/chat/completions' => Http::response(['choices' => [['message' => ['role' => 'assistant', 'content' => null]]]])]);
        $this->postJson(route('smart-assistant.store'), ['question' => 'الإنتاج'])->assertUnprocessable();
    }

    public function test_questions_are_rate_limited_before_further_groq_calls(): void
    {
        Http::fake(['https://api.groq.com/openai/v1/chat/completions' => Http::response($this->answerResponse('إجابة'))]);

        for ($attempt = 0; $attempt < 10; $attempt++) {
            $this->postJson(route('smart-assistant.store'), ['question' => 'سؤال'])->assertOk();
        }

        $this->postJson(route('smart-assistant.store'), ['question' => 'سؤال'])
            ->assertTooManyRequests()
            ->assertJsonPath('message', 'أرسلت أسئلة كثيرة خلال دقيقة. انتظر قليلًا ثم حاول مجددًا.');
        Http::assertSentCount(10);
    }

    /** @param array<string, mixed> $overrides */
    private function fakeToolAnswer(array $overrides = [], string $answer = 'تم تحليل البيانات.'): void
    {
        Http::fake(['https://api.groq.com/openai/v1/chat/completions' => Http::sequence()
            ->push($this->toolResponse('report-call', $this->arguments($overrides)))
            ->push($this->answerResponse($answer))]);
    }

    /** @param array<string, mixed> $arguments */
    private function fakeSingleToolCall(array $arguments): void
    {
        Http::fake(['https://api.groq.com/openai/v1/chat/completions' => Http::response($this->toolResponse('report-call', $arguments))]);
    }

    private function fakeGeneralAnswer(string $answer): void
    {
        Http::fake(['https://api.groq.com/openai/v1/chat/completions' => Http::response($this->answerResponse($answer))]);
    }

    /** @param array<string, mixed> $arguments */
    private function toolResponse(string $id, array $arguments): array
    {
        return ['choices' => [['message' => [
            'role' => 'assistant', 'content' => null,
            'tool_calls' => [[
                'id' => $id, 'type' => 'function',
                'function' => ['name' => 'read_farm_report', 'arguments' => json_encode($arguments)],
            ]],
        ]]]];
    }

    private function answerResponse(string $answer): array
    {
        return ['choices' => [['message' => ['role' => 'assistant', 'content' => $answer]]]];
    }

    /** @param array<string, mixed> $overrides */
    private function arguments(array $overrides = []): array
    {
        return array_replace([
            'report' => 'production', 'target' => null, 'supplier' => null,
            'payment_method' => null, 'payment_status' => null, 'result_mode' => 'details',
            'ranking_direction' => 'desc', 'start_date' => '2026-09-13', 'end_date' => '2026-09-13',
            'group_by' => 'day', 'comparison' => 'none', 'missing_data' => 'none',
        ], $overrides);
    }
}
