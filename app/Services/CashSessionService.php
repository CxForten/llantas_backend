<?php

namespace App\Services;

use App\Models\CashSession;
use App\Models\Sale;
use RuntimeException;

class CashSessionService
{
    
    public function currentSession(int $businessId): ?CashSession
    {
        return CashSession::where('business_id', $businessId)
            ->where('status', 'open')
            ->latest('opened_at')
            ->first();
    }

    public function open(int $businessId, int $userId, int $openingCents, ?string $notes = null): CashSession
    {
        if ($this->currentSession($businessId)) {
            throw new RuntimeException('Ya hay una sesión de caja abierta. Ciérrala antes de abrir otra.');
        }

        return CashSession::create([
            'business_id'   => $businessId,
            'opened_by'     => $userId,
            'opened_at'     => now(),
            'opening_cents' => $openingCents,
            'status'        => 'open',
            'notes'         => $notes,
        ]);
    }

    public function close(CashSession $session, int $countedCents, ?string $notes, int $userId): CashSession
    {
        if ($session->status !== 'open') {
            throw new RuntimeException('Esta sesión de caja ya está cerrada.');
        }

        $summary  = $this->summary($session);
        $expected = $summary['expected_cents'];

        $session->update([
            'closed_by'        => $userId,
            'closed_at'        => now(),
            'expected_cents'   => $expected,
            'counted_cents'    => $countedCents,
            'difference_cents' => $countedCents - $expected,
            'status'           => 'closed',
            'notes'            => $notes ?? $session->notes,
        ]);

        return $session->fresh();
    }

    public function summary(CashSession $session): array
    {
        $base = Sale::where('cash_session_id', $session->id)
            ->where('status', 'completada');

        $cash     = (int) (clone $base)->where('payment_method', 'efectivo')->sum('total_cents');
        $card     = (int) (clone $base)->where('payment_method', 'tarjeta')->sum('total_cents');
        $transfer = (int) (clone $base)->where('payment_method', 'transferencia')->sum('total_cents');

        $row = (clone $base)->selectRaw('
            COUNT(*) as sales_count,
            COALESCE(SUM(total_cents), 0)           as total_cents,
            COALESCE(SUM(business_income_cents), 0) as income_cents,
            COALESCE(SUM(gross_margin_cents), 0)    as margin_cents,
            COALESCE(SUM(card_fee_cents), 0)        as card_fee_cents,
            COALESCE(SUM(discount_cents), 0)        as discount_cents
        ')->first();

        $voided = (int) Sale::where('cash_session_id', $session->id)
            ->where('status', 'anulada')
            ->count();

        return [
            'sales_count'    => (int) $row->sales_count,
            'voided_count'   => $voided,
            'opening_cents'  => (int) $session->opening_cents,

            'cash_cents'     => $cash,
            'card_cents'     => $card,
            'transfer_cents' => $transfer,


            'expected_cents' => (int) $session->opening_cents + $cash,

            'total_cents'    => (int) $row->total_cents,
            'income_cents'   => (int) $row->income_cents,
            'margin_cents'   => (int) $row->margin_cents,
            'card_fee_cents' => (int) $row->card_fee_cents,
            'discount_cents' => (int) $row->discount_cents,
        ];
    }
}
