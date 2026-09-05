<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class CampayService
{
    public function stimulate(string $phone, string $operator, int $amount = 100): array
    {
        $apiUrl = env('CAMPAY_API_URL');
        $apiKey = env('CAMPAY_API_KEY');

        // If no API key configured, simulate success for local/dev/testing
        if (empty($apiUrl) || empty($apiKey)) {
            return [
                'success' => true,
                'transaction_id' => 'SIM-' . Str::upper(Str::random(12)),
                'raw' => ['simulated' => true],
            ];
        }

        // Example request structure for Campay; adjust per Campay docs
        try {
            $resp = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Accept' => 'application/json',
            ])->post($apiUrl . '/stimulation', [
                'phone' => $phone,
                'operator' => $operator,
                'amount' => $amount,
            ]);

            $payload = $resp->json();

            if ($resp->successful() && ! empty($payload['transaction_id'])) {
                return [
                    'success' => true,
                    'transaction_id' => $payload['transaction_id'],
                    'raw' => $payload,
                ];
            }

            return [
                'success' => false,
                'error' => $payload ?? $resp->body(),
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}
