<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Plainte;

class PaymentWebhookController extends Controller
{
    public function handle(Request $request)
    {
        // Optional simple secret header validation
        $secret = env('CAMPAY_WEBHOOK_SECRET');
        $header = $request->header('X-Campay-Webhook-Secret');
        if (! empty($secret) && $header !== $secret) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $data = $request->validate([
            'transaction_id' => ['required', 'string'],
            'status' => ['required', 'string'], // expected: success|failed
            'reference' => ['nullable', 'string'],
        ]);

        $txn = $data['transaction_id'];
        $status = strtolower($data['status']);

        $plainte = Plainte::where('payment_txn_id', $txn)->first();
        if (! $plainte) {
            return response()->json(['message' => 'Not found'], 404);
        }

        if (in_array($status, ['success', 'successful', 'ok'], true)) {
            $plainte->update(['payment_status' => 'successful', 'paid' => true]);
        } else {
            $plainte->update(['payment_status' => 'failed']);
        }

        return response()->json(['message' => 'updated']);
    }
}
