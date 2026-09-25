<?php

namespace App\Controllers\Api;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Request;
use App\Services\CashShiftService;
use App\Services\SyncService;

class SyncApiController extends Controller
{
    public function push(Request $request): void
    {
        $body = $request->isJson() ? $request->jsonBody() : $request->all();

        if (!Csrf::verify($body['_csrf_token'] ?? null)) {
            $this->json(['success' => false, 'message' => 'Sesi tidak valid, silakan login ulang.', 'errors' => [], 'data' => null], 419);
            return;
        }

        $deviceId = $body['device_id'] ?? null;
        if (!$deviceId) {
            $this->json(['success' => false, 'message' => 'device_id wajib disertakan.', 'errors' => [], 'data' => null], 422);
            return;
        }

        SyncService::registerDevice($deviceId, $body['device_name'] ?? null, Auth::id());

        $shift = CashShiftService::currentOpenShift(Auth::id());
        $results = [];

        foreach ((array) ($body['items'] ?? []) as $item) {
            $payload = $item['payload'] ?? [];
            $payload['cashier_id'] = Auth::id();
            $payload['shift_id'] = $shift['id'] ?? null;

            $results[] = SyncService::processSale([
                'uuid' => $item['uuid'],
                'device_id' => $deviceId,
                'payload' => $payload,
            ]);
        }

        $this->json([
            'success' => true,
            'message' => count($results) . ' item diproses.',
            'errors' => [],
            'data' => ['results' => $results],
        ]);
    }
}
