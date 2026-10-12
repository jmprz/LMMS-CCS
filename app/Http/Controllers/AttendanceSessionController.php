<?php

namespace App\Http\Controllers;

use App\Models\AttendanceSession;
use App\Models\LabSession;
use App\Services\AttendanceSessionService;
use Illuminate\Http\Request;

class AttendanceSessionController extends Controller
{
    public function show(Request $request, LabSession $labSession, AttendanceSessionService $service)
    {
        $service->authorizeManager($labSession, $request->user());

        return response()->json($service->summary($labSession, true));
    }

    public function start(Request $request, LabSession $labSession, AttendanceSessionService $service)
    {
        $data = $request->validate(['late_after_minutes' => ['sometimes', 'integer', 'min:0', 'max:120']]);
        $service->start($labSession, $request->user(), $data['late_after_minutes'] ?? 10);

        return response()->json($service->summary($labSession, true));
    }

    public function finalize(Request $request, AttendanceSession $attendanceSession, AttendanceSessionService $service)
    {
        $service->close($attendanceSession, $request->user());

        return response()->json($service->summary($attendanceSession->labSession, true));
    }

    public function cancel(Request $request, AttendanceSession $attendanceSession, AttendanceSessionService $service)
    {
        $service->close($attendanceSession, $request->user(), true);

        return response()->json($service->summary($attendanceSession->labSession, true));
    }

    public function excuse(Request $request, AttendanceSession $attendanceSession, int $userId, AttendanceSessionService $service)
    {
        $data = $request->validate(['excused' => ['required', 'boolean']]);
        $service->excuse($attendanceSession, $request->user(), $userId, $data['excused']);

        return response()->json($service->summary($attendanceSession->labSession, true));
    }
}
