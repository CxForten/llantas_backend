<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CloseCashSessionRequest;
use App\Http\Requests\OpenCashSessionRequest;
use App\Http\Resources\CashSessionResource;
use App\Models\CashSession;
use App\Services\CashSessionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use RuntimeException;

class CashSessionController extends Controller
{
    public function __construct(private CashSessionService $service) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $sessions = CashSession::with('openedBy:id,name', 'closedBy:id,name')
            ->where('business_id', $request->user()->business_id)
            ->orderByDesc('opened_at')
            ->paginate((int) $request->query('per_page', 25));

        return CashSessionResource::collection($sessions);
    }

    /** La sesión abierta ahora mismo, o null. El POS pregunta esto al arrancar. */
    public function current(Request $request): JsonResponse
    {
        $session = $this->service->currentSession($request->user()->business_id);

        if (! $session) {
            return response()->json(['data' => null]);
        }

        return response()->json([
            'data' => (new CashSessionResource(
                $session->load('openedBy:id,name')
            ))->additional([
                'live' => $this->service->summary($session),
            ])->resolve(),
            'live' => $this->service->summary($session),
        ]);
    }

    public function open(OpenCashSessionRequest $request): JsonResponse
    {
        try {
            $session = $this->service->open(
                businessId:    $request->user()->business_id,
                userId:        $request->user()->id,
                openingCents:  (int) $request->validated('opening_cents'),
                notes:         $request->validated('notes'),
            );
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return (new CashSessionResource($session->load('openedBy:id,name')))
            ->response()
            ->setStatusCode(201);
    }

    public function close(CloseCashSessionRequest $request): JsonResponse
    {
        $session = $this->service->currentSession($request->user()->business_id);

        if (! $session) {
            return response()->json(['message' => 'No hay una sesión de caja abierta.'], 422);
        }

        $session = $this->service->close(
            session:       $session,
            countedCents:  (int) $request->validated('counted_cents'),
            notes:         $request->validated('notes'),
            userId:        $request->user()->id,
        );

        return response()->json([
            'message' => 'Caja cerrada.',
            'data'    => new CashSessionResource($session->load('openedBy:id,name', 'closedBy:id,name')),
            'summary' => $this->service->summary($session),
        ]);
    }

    /** Resumen de una sesión ya cerrada (para reimprimir el cierre). */
    public function show(Request $request, CashSession $cashSession): JsonResponse
    {
        abort_if($cashSession->business_id !== $request->user()->business_id, 404);

        return response()->json([
            'data'    => new CashSessionResource($cashSession->load('openedBy:id,name', 'closedBy:id,name')),
            'summary' => $this->service->summary($cashSession),
        ]);
    }
}
