<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\JournalReminderService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function teacher (Request $request, JournalReminderService $service)
    {
        return response()->json([
            'data' => ['overdue_journals' => $service->overdueFor($request->user())],
        ]);
    }

    public function waka (JournalReminderService $service)
    {
        return response()->json([
            'data' => ['overdue_journals' => $service->overdueFor()],
        ]);
    }
}
