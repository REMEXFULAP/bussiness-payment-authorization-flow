<?php

namespace Remex\SDK;

use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class RemesitaPaymentAutorizationFlow
{
    private HttpClientInterface $client;
    private string $apiToken;
    private string $businessUnitId;
    private string $baseUrl = 'https://api.remesita.com';

    public function __construct(string $apiToken, string $businessUnitId)
    {
        $this->client = HttpClient::create();
        $this->apiToken = $apiToken;
        $this->businessUnitId = $businessUnitId;
    }

    /**
     * Inicia un pago simple
     */
    public function initiatePayment(
        float $amount,
        string $account,
        string $concept,
        ?string $savedToken = null,
        ?string $customId = null,
        ?string $ipnUrl = null
    ): array {
        $payload = [
            'businessUnitId' => $this->businessUnitId,
            'amount' => $amount,
            'concept' => $concept,
            'account' => $account,
            'feeAssumedBy' => 'payer',
        ];

        if ($savedToken) {
            $payload['paymentAuthorizationToken'] = $savedToken;
        }

        if ($customId) {
            $payload['customId'] = $customId;
        }

        if ($ipnUrl) {
            $payload['ipnUrl'] = $ipnUrl;
        }

        $response = $this->client->request('POST', $this->baseUrl . '/rest/v1/payment/initiate', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiToken,
                'Content-Type' => 'application/json',
            ],
            'json' => $payload,
        ]);

        return $response->toArray();
    }

    /**
     * Solicita envío de código de autorización
     */
    public function requestAuthorizationCode(string $paymentSession, string $channelId): array
    {
        $response = $this->client->request('POST', $this->baseUrl . '/rest/v1/payment/authorization/request', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiToken,
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'paymentSession' => $paymentSession,
                'channel' => $channelId,
            ],
        ]);

        return $response->toArray();
    }

    /**
     * Valida el código de autorización
     */
    public function validateAuthorizationCode(
        string $paymentSession,
        string $paymentAuthorizationToken,
        string $code
    ): array {
        $response = $this->client->request('POST', $this->baseUrl . '/rest/v1/payment/authorization/validate', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiToken,
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'paymentSession' => $paymentSession,
                'paymentAuthorizationToken' => $paymentAuthorizationToken,
                'code' => $code,
            ],
        ]);

        return $response->toArray();
    }
}
