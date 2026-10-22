<?php

namespace App\Http\Controllers;

use App\Http\Requests\SaveBroilerVaccinationRequest;
use App\Models\BroilerCycle;
use App\Models\BroilerVaccination;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BroilerVaccinationController extends Controller
{
    public function store(SaveBroilerVaccinationRequest $request, BroilerCycle $cycle): RedirectResponse
    {
        DB::transaction(function () use ($request, $cycle): void {
            $cycle = $this->lockOpenCycle($cycle);
            $cycle->vaccinations()->create($this->schedule($request, $cycle));
        });

        return to_route('broiler.show', $cycle)->with('status', 'تم إضافة التحصين والتنبيه إلى جدول الدورة.');
    }

    public function update(SaveBroilerVaccinationRequest $request, BroilerCycle $cycle, int $vaccination): RedirectResponse
    {
        DB::transaction(function () use ($request, $cycle, $vaccination): void {
            $cycle = $this->lockOpenCycle($cycle);
            $record = $cycle->vaccinations()->findOrFail($vaccination);
            if ($record->completed_at) {
                throw ValidationException::withMessages(['vaccination' => 'التحصين المنفذ لا يمكن تعديل موعده.']);
            }
            $record->update($this->schedule($request, $cycle));
        });

        return to_route('broiler.show', $cycle)->with('status', 'تم تحديث موعد التحصين والتنبيه.');
    }

    public function complete(BroilerCycle $cycle, int $vaccination): RedirectResponse
    {
        DB::transaction(function () use ($cycle, $vaccination): void {
            $cycle = $this->lockOpenCycle($cycle);
            $record = $cycle->vaccinations()->findOrFail($vaccination);
            if (! $record->completed_at) {
                $record->update(['completed_at' => now('UTC')]);
            }
        });

        return to_route('broiler.show', $cycle)->with('status', 'تم تسجيل تنفيذ التحصين وإيقاف تنبيهه.');
    }

    public function destroy(BroilerCycle $cycle, int $vaccination): RedirectResponse
    {
        DB::transaction(function () use ($cycle, $vaccination): void {
            $record = $this->lockOpenCycle($cycle)->vaccinations()->findOrFail($vaccination);
            if ($record->completed_at) {
                throw ValidationException::withMessages(['vaccination' => 'لا يمكن حذف تحصين تم تنفيذه.']);
            }
            $record->delete();
        });

        return to_route('broiler.show', $cycle)->with('status', 'تم حذف الموعد.');
    }

    public function reminders(): JsonResponse
    {
        $reminders = BroilerVaccination::query()->with('cycle.house')->whereNull('completed_at')->where('remind_at', '<=', now('UTC'))->whereHas('cycle', fn ($query) => $query->whereNull('closed_on'))->orderBy('due_at')->get()->map(fn (BroilerVaccination $record): array => [
            'id' => $record->id, 'key' => $record->id.'-'.$record->due_at->timestamp.'-'.$record->remind_at->timestamp,
            'title' => $record->name, 'cycle' => $record->cycle->name, 'house' => $record->cycle->house->name,
            'due' => $record->due_at->setTimezone(BroilerVaccination::TIMEZONE)->format('Y-m-d H:i').' (توقيت القاهرة)',
            'overdue' => $record->due_at->isPast(), 'url' => route('broiler.show', $record->cycle).'#vaccinations',
        ]);

        return response()->json(['reminders' => $reminders])->header('Cache-Control', 'no-store, private');
    }

    public function calendar(BroilerCycle $cycle): Response
    {
        $lines = ['BEGIN:VCALENDAR', 'VERSION:2.0', 'PRODID:-//Broiler//Vaccinations//AR', 'CALSCALE:GREGORIAN'];
        foreach ($cycle->vaccinations()->whereNull('completed_at')->orderBy('due_at')->get() as $record) {
            $lines = [...$lines, 'BEGIN:VEVENT', 'UID:broiler-'.$cycle->id.'-'.$record->id.'@'.parse_url(config('app.url'), PHP_URL_HOST), 'DTSTAMP:'.now('UTC')->format('Ymd\THis\Z'), 'DTSTART:'.$record->due_at->utc()->format('Ymd\THis\Z'), 'DURATION:PT30M', 'SUMMARY:'.$this->escapeCalendar($record->name.' — '.$cycle->name), 'DESCRIPTION:'.$this->escapeCalendar($record->notes ?? ''), 'BEGIN:VALARM', 'ACTION:DISPLAY', 'DESCRIPTION:'.$this->escapeCalendar('تذكير بالتحصين: '.$record->name), 'TRIGGER:-PT'.$record->remind_before_hours.'H', 'END:VALARM', 'END:VEVENT'];
        }
        $lines[] = 'END:VCALENDAR';
        $folded = array_map(function (string $line): string {
            $chunks = [];
            while (strlen($line) > 74) {
                $chunk = mb_strcut($line, 0, 74, 'UTF-8');
                $chunks[] = $chunk;
                $line = substr($line, strlen($chunk));
            }
            $chunks[] = $line;

            return implode("\r\n ", $chunks);
        }, $lines);

        return response(implode("\r\n", $folded)."\r\n", 200, ['Content-Type' => 'text/calendar; charset=utf-8', 'Content-Disposition' => 'attachment; filename="cycle-'.$cycle->id.'-vaccinations.ics"', 'Cache-Control' => 'no-store, private']);
    }

    private function lockOpenCycle(BroilerCycle $cycle): BroilerCycle
    {
        $cycle = BroilerCycle::query()->lockForUpdate()->findOrFail($cycle->id);
        if ($cycle->closed_on) {
            throw ValidationException::withMessages(['cycle' => 'الدورة مغلقة.']);
        }

        return $cycle;
    }

    /** @return array<string, mixed> */
    private function schedule(SaveBroilerVaccinationRequest $request, BroilerCycle $cycle): array
    {
        $data = $request->validated();
        $data['timezone'] = BroilerVaccination::TIMEZONE;
        $due = Carbon::createFromFormat('!Y-m-d\TH:i', $data['scheduled_at'], $data['timezone']);
        if ($due->format('Y-m-d\TH:i') !== $data['scheduled_at'] || $due->toDateString() < $cycle->started_on->toDateString()) {
            throw ValidationException::withMessages(['scheduled_at' => 'اختر موعدًا صحيحًا لا يسبق بداية الدورة.']);
        }
        unset($data['scheduled_at']);
        $data['due_at'] = $due->utc();
        $data['remind_at'] = $due->copy()->subHours((int) $data['remind_before_hours']);

        return $data;
    }

    private function escapeCalendar(string $value): string
    {
        return str_replace(['\\', "\r\n", "\r", "\n", ';', ','], ['\\\\', '\n', '\n', '\n', '\;', '\,'], $value);
    }
}
