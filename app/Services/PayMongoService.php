<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class PayMongoService
{
    private string $baseUrl = 'https://api.paymongo.com';

    private function client()
    {
        $key = config('services.paymongo.secret_key');

        if (!is_string($key) || $key === '') {
            throw new RuntimeException(
                'PayMongo secret key is not configured.'
            );
        }

        return Http::withBasicAuth($key, '')
            ->acceptJson()
            ->asJson()
            ->timeout(30);
    }

    public function createCheckout(
        int $amountCentavos,
        string $reference,
        string $successUrl,
        string $cancelUrl
    ): array {
        if ($amountCentavos <= 0) {
            throw new RuntimeException(
                'Checkout amount must be positive.'
            );
        }

        if (!filter_var($successUrl, FILTER_VALIDATE_URL)
            || !filter_var($cancelUrl, FILTER_VALIDATE_URL)) {
            throw new RuntimeException(
                'Valid checkout return URLs are required.'
            );
        }

        $response = $this->client()->post(
            $this->baseUrl . '/v2/checkout_sessions',
            [
                'data' => [
                    'attributes' => [
                        'line_items' => [
                            [
                                'name' => 'RentTrack Billing',
                                'quantity' => 1,
                                'amount' => $amountCentavos,
                                'currency' => 'PHP',
                            ],
                        ],
                        'payment_method_types' => [
                            'gcash',
                        ],
                        'success_url' => $successUrl,
                        'cancel_url' => $cancelUrl,
                        'reference_number' => $reference,
                    ],
                ],
            ]
        );

        $response->throw();

        $data = $response->json('data');

        if (!is_array($data)
            || empty($data['id'])
            || empty($data['attributes']['checkout_url'])) {
            throw new RuntimeException(
                'Invalid checkout response from PayMongo.'
            );
        }

        return $data;
    }
}
