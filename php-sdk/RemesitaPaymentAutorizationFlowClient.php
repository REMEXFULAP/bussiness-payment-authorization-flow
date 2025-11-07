<?php

namespace Remesita\SDK;

use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Remesita Payment SDK
 * 
 * SDK para integración con la API de pagos de Remesita
 * 
 * @package Remesita\SDK
 * @version 1.0.0
 */
class RemesitaPaymentAutorizationFlowClient
{
    private HttpClientInterface $client;
    private string $apiToken;
    private string $businessUnitId;
    private string $baseUrl;

    /**
     * Constructor
     * 
     * @param string $apiToken Token de autenticación de la API
     * @param string $businessUnitId ID del negocio
     * @param string $baseUrl URL base de la API (por defecto: https://remesita.com)
     */
    public function __construct(string $apiToken, string $businessUnitId, string $baseUrl = 'https://remesita.com')
    {
        $this->client = HttpClient::create();
        $this->apiToken = $apiToken;
        $this->businessUnitId = $businessUnitId;
        $this->baseUrl = $baseUrl;
    }

    /**
     * Inicia un pago
     * 
     * @param array $params Parámetros del pago
     *   - amount (float): Monto a cobrar
     *   - account (string): Wallet address o teléfono del cliente
     *   - concept (string): Concepto del pago
     *   - savedToken (string|null): Token de autorización guardado
     *   - customId (string|null): ID personalizado
     *   - ipnUrl (string|null): URL para webhooks
     *   - feeAssumedBy (string): 'payer' o 'collector'
     *   - distribution (array|null): Distribución de fondos
     *   - subscription (array|null): Configuración de suscripción
     * 
     * @return array Respuesta de la API
     * @throws RemesitaException
     */
    public function initiatePayment(array $params): array
    {
        $payload = [
            'businessUnitId' => $this->businessUnitId,
            'amount' => $params['amount'],
            'concept' => $params['concept'],
            'account' => $params['account'],
            'feeAssumedBy' => $params['feeAssumedBy'] ?? 'payer',
        ];

        if (isset($params['savedToken'])) {
            $payload['paymentAuthorizationToken'] = $params['savedToken'];
        }

        if (isset($params['customId'])) {
            $payload['customId'] = $params['customId'];
        }

        if (isset($params['ipnUrl'])) {
            $payload['ipnUrl'] = $params['ipnUrl'];
        }

        if (isset($params['distribution'])) {
            $this->validateDistribution($params['amount'], $params['distribution']);
            $payload['distribution'] = $params['distribution'];
        }

        if (isset($params['subscription'])) {
            $payload['subscription'] = $params['subscription'];
        }

        return $this->request('POST', '/rest/v1/payment/initiate', $payload);
    }

    /**
     * Solicita código de autorización
     * 
     * @param string $paymentSession ID de sesión de pago
     * @param string $channelId ID del canal (SMS, email, etc)
     * @return array
     * @throws RemesitaException
     */
    public function requestAuthCode(string $paymentSession, string $channelId): array
    {
        return $this->request('POST', '/rest/v1/payment/authorization/request', [
            'paymentSession' => $paymentSession,
            'channel' => $channelId,
        ]);
    }

    /**
     * Valida código de autorización
     * 
     * @param string $paymentSession ID de sesión de pago
     * @param string $authToken Token de autorización
     * @param string $code Código de 6 dígitos
     * @return array
     * @throws RemesitaException
     */
    public function validateAuthCode(string $paymentSession, string $authToken, string $code): array
    {
        return $this->request('POST', '/rest/v1/payment/authorization/validate', [
            'paymentSession' => $paymentSession,
            'paymentAuthorizationToken' => $authToken,
            'code' => $code,
        ]);
    }

    /**
     * Genera un payment link
     * 
     * @param array $params Parámetros del link
     * @return array
     * @throws RemesitaException
     */
    public function generatePaymentLink(array $params): array
    {
        $payload = array_merge(['businessUnitId' => $this->businessUnitId], $params);
        return $this->request('POST', '/rest/v1/payment-link', $payload);
    }

    /**
     * Lista los payment links
     * 
     * @param int $page Página
     * @param int $pageSize Tamaño de página
     * @return array
     * @throws RemesitaException
     */
    public function listPaymentLinks(int $page = 1, int $pageSize = 25): array
    {
        return $this->request('GET', "/rest/v1/payment/{$this->businessUnitId}/links?pg={$page}&pgSize={$pageSize}");
    }

    /**
     * Elimina un payment link
     * 
     * @param string $linkId ID del link
     * @return array
     * @throws RemesitaException
     */
    public function deletePaymentLink(string $linkId): array
    {
        return $this->request('DELETE', "/rest/v1/payment/{$this->businessUnitId}/link/{$linkId}");
    }

    /**
     * Procesa un reembolso
     * 
     * @param string $reference Referencia de la orden
     * @param float $amount Monto a reembolsar
     * @param string $reason Motivo del reembolso
     * @return array
     * @throws RemesitaException
     */
    public function refundPayment(string $reference, float $amount, string $reason): array
    {
        return $this->request('POST', '/rest/v1/payment/refund', [
            'businessUnitId' => $this->businessUnitId,
            'reference' => $reference,
            'amount' => $amount,
            'reason' => $reason,
        ]);
    }

    /**
     * Obtiene métodos de pago disponibles para un SKU
     * 
     * @param string $sku Tipo de servicio
     * @param string|null $countryIso ISO del país
     * @return array
     * @throws RemesitaException
     */
    public function getPaymentMethods(string $sku, ?string $countryIso = null): array
    {
        $url = "/rest/v1/payment/methods/{$sku}";
        if ($countryIso) {
            $url .= "/{$countryIso}";
        }
        return $this->request('GET', $url);
    }

    /**
     * Calcula cuánto queda para el comercio después de distribución
     * 
     * @param float $totalAmount Monto total
     * @param array $distribution Configuración de distribución
     * @return float
     */
    public function calculateMerchantAmount(float $totalAmount, array $distribution): float
    {
        $distributed = 0;

        foreach ($distribution as $item) {
            if (isset($item['fixed_amount'])) {
                $distributed += $item['fixed_amount'];
            }

            if (isset($item['percentage_amount'])) {
                $distributed += $totalAmount * ($item['percentage_amount'] / 100);
            }
        }

        return $totalAmount - $distributed;
    }

    /**
     * Valida configuración de distribución
     * 
     * @param float $totalAmount Monto total
     * @param array $distribution Configuración
     * @throws RemesitaException
     */
    private function validateDistribution(float $totalAmount, array $distribution): void
    {
        $percentSum = 0;
        $fixedSum = 0;

        foreach ($distribution as $item) {
            if (!isset($item['account'])) {
                throw new RemesitaException('Cada distribución debe tener un account');
            }

            if (isset($item['percentage_amount'])) {
                $percentSum += $item['percentage_amount'];
            }

            if (isset($item['fixed_amount'])) {
                $fixedSum += $item['fixed_amount'];
            }
        }

        if ($percentSum > 100) {
            throw new RemesitaException('La suma de porcentajes no puede exceder 100%');
        }

        $totalDistribution = $fixedSum + ($totalAmount * $percentSum / 100);
        
        if ($totalDistribution > $totalAmount) {
            throw new RemesitaException(
                "La distribución total ({$totalDistribution}) excede el monto a cobrar ({$totalAmount})"
            );
        }
    }

    /**
     * Ejecuta una petición HTTP
     * 
     * @param string $method Método HTTP
     * @param string $endpoint Endpoint
     * @param array|null $data Datos a enviar
     * @return array
     * @throws RemesitaException
     */
    private function request(string $method, string $endpoint, ?array $data = null): array
    {
        try {
            $options = [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiToken,
                    'Content-Type' => 'application/json',
                ],
            ];

            if ($data && $method !== 'GET') {
                $options['json'] = $data;
            }

            $response = $this->client->request($method, $this->baseUrl . $endpoint, $options);
            
            return $response->toArray();
            
        } catch (\Exception $e) {
            throw new RemesitaException(
                'Error en la petición a Remesita: ' . $e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }
}

/**
 * Excepción personalizada para el SDK
 */
class RemesitaException extends \Exception
{
}
