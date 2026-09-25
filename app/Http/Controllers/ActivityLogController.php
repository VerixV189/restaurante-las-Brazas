<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Inertia\Inertia;
use Inertia\Response;

class ActivityLogController extends Controller
{
    public function index(): Response
    {
        $logs = ActivityLog::with('user')->latest()->paginate(25);

        return Inertia::render('ActivityLogs/Index', [
            'logs' => $logs,
        ]);
    }
}
