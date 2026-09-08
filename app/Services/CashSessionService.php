<?php

namespace App\Services;

use App\Models\CashSession;
use App\Models\Sale;


class CashSessionService {

    public function close(CashSession $session, int $countedCents, ?string $notes, int $userId): CashSession
    {
        $ventasEfectivo = Sale::where('cash_session:id', $session->id)
            ->where('status', 'completada')
            ->where('payment_method', 'efectivo')
            ->sum('total_cents');

        $expected = $session->opening_cents + $ventasEfectivo;

        $session->update([
            'closed_by'        => $userId,
            'closed_At'        => now(),
            'expected_cents'   => $expected,
            'counted_cents'    => $countedCents,
            'difference_cents' => $countedCents - $expected,
            'status'           => 'closed',
            'notes'            => $notes,
        ]);

        return $session;
    }
}