<?php

namespace App\Http\Controllers;

use App\Services\ActivityDurationService;
use Illuminate\Http\Request;

class ActivityDurationController extends Controller
{
    public function start(Request $request, int $classId, ActivityDurationService $service)
    {
        $data = $request->validate(['client_session_key' => 'required|uuid']);

        return $this->response($service->start($request->user(), $classId, $data['client_session_key']));
    }

    public function heartbeat(Request $request, int $classId, ActivityDurationService $service)
    {
        return $this->record($request, $classId, $service, false);
    }

    public function end(Request $request, int $classId, ActivityDurationService $service)
    {
        return $this->record($request, $classId, $service, true);
    }

    private function record(Request $request, int $classId, ActivityDurationService $service, bool $close)
    {
        $data = $request->validate(['session_id' => 'required|integer|min:1', 'sequence' => 'required|integer|min:1|max:2147483647',
            'active_seconds' => 'required|integer|min:0|max:2147483647']);

        return $this->response($service->record($request->user(), $classId, $data['session_id'], $data['sequence'], $data['active_seconds'], $close));
    }

    private function response($session)
    {
        return response()->json(['session_id' => $session->id, 'duration_seconds' => $session->duration_seconds, 'closed' => $session->closed_at !== null]);
    }
}
