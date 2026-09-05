<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\CampayService;

class PaymentController extends Controller
{
    protected CampayService $campay;

    public function __construct(CampayService $campay)
    {
        $this->campay = $campay;
    }

    public function mobilePay(Request $request)
    {
        $request->validate([
            'payment_phone' => ['required', 'string', 'max:20'],
            'payment_operator' => ['required', 'in:mtn,orange'],
            'amount' => ['nullable', 'integer'],
        ]);

        $phone = $request->input('payment_phone');
        $operator = $request->input('payment_operator');
        $amount = (int) ($request->input('amount') ?? 100);

        $result = $this->campay->stimulate($phone, $operator, $amount);

        if (! $result['success']) {
            return response()->json(['message' => 'Paiement mobile échoué', 'error' => $result['error'] ?? null], 422);
        }

        return response()->json(['transaction_id' => $result['transaction_id'], 'raw' => $result['raw'] ?? null]);
    }
}
