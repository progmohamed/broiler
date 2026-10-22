<?php

namespace App\Http\Controllers;

use App\FarmBackup;
use App\Http\Requests\RestoreFarmBackupRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class BackupController extends Controller
{
    public function index(FarmBackup $farmBackup): View
    {
        return view('backups.index', ['recordCounts' => $farmBackup->recordCounts()]);
    }

    public function download(FarmBackup $farmBackup): JsonResponse
    {
        $filename = 'layer-backup-'.now()->format('Y-m-d-His').'.json';

        return response()->json($farmBackup->exportData(), 200, [], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
            ->header('Content-Disposition', 'attachment; filename="'.$filename.'"');
    }

    public function restore(RestoreFarmBackupRequest $request, FarmBackup $farmBackup): RedirectResponse
    {
        try {
            $farmBackup->restore($request->file('backup'));
        } catch (Throwable) {
            throw ValidationException::withMessages([
                'backup' => 'تعذر استعادة الملف. تأكد أنه نسخة صالحة وكاملة من التطبيق.',
            ]);
        }

        return to_route('backups.index')->with('status', 'تمت استعادة النسخة الاحتياطية بنجاح.');
    }
}
