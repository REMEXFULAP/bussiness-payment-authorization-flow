# Remesita Payment Authorization - PHP SDK

[![Latest Version](https://img.shields.io/badge/version-1.0.0-blue.svg)](https://github.com/remesita/php-sdk)
[![PHP Version](https://img.shields.io/badge/php-%3E%3D8.0-8892BF.svg)](https://www.php.net/)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)

SDK oficial de PHP para integrar la API de pagos de Remesita en tu aplicación.

## 📋 Tabla de Contenidos

- [Características](#-características)
- [Requisitos](#-requisitos)
- [Instalación](#-instalación)
- [Configuración Rápida](#-configuración-rápida)
- [Flujos de Integración](#-flujos-de-integración)
- [Ejemplos de Uso](#-ejemplos-de-uso)
- [Documentación Completa](#-documentación-completa)
- [Webhooks](#-webhooks)
- [Testing](#-testing)
- [Soporte](#-soporte)
- [Licencia](#-licencia)

## ✨ Características

- ✅ **Pagos Simples**: Cobra a wallets Remesita de forma segura
- ✅ **Distribución Automática**: Reparte fondos entre múltiples wallets sin costo adicional
- ✅ **Suscripciones**: Crea pagos recurrentes automáticos
- ✅ **Autenticación 2FA**: Manejo completo del flujo de autorización
- ✅ **Tokens Persistentes**: Almacena autorizaciones para pagos futuros instantáneos
- ✅ **Payment Links**: Genera enlaces de pago personalizados
- ✅ **Reembolsos**: Procesa devoluciones parciales o totales
- ✅ **Validación Automática**: Valida datos antes de enviar a la API
- ✅ **Manejo de Errores**: Excepciones claras y detalladas

## 🔧 Requisitos

- PHP >= 8.0
- Composer
- Extensión `ext-json`
- Symfony HttpClient Component >= 6.0

## 📦 Instalación

### Vía Composer

```bash
composer require remesita/php-sdk
```

### Instalación Manual

```bash
git clone https://github.com/remesita/php-sdk.git
cd php-sdk
composer install
```

## 🚀 Configuración Rápida

### 1. Obtén tus Credenciales

1. Regístrate en [Remesita.com](https://remesita.com)
2. Accede al [Dashboard de desarrolladores](https://remesita.com/developers)
3. Crea una aplicación y obtén:
   - `API Token`
   - `Business Unit ID`

### 2. Configuración Básica

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use Remesita\SDK\RemesitaClient;

// Inicializar el cliente
$remesita = new RemesitaClient(
    apiToken: 'tu_api_token_aqui',
    businessUnitId: 'tu_business_id_aqui'
);

// ¡Listo para usar!
```

### 3. Variables de Entorno (Recomendado)

```bash
# .env
REMESITA_API_TOKEN=your_api_token_here
REMESITA_BUSINESS_ID=your_business_id_here
REMESITA_WEBHOOK_URL=https://tuapp.com/webhook/remesita
```

```php
<?php

use Remesita\SDK\RemesitaClient;

$remesita = new RemesitaClient(
    apiToken: $_ENV['REMESITA_API_TOKEN'],
    businessUnitId: $_ENV['REMESITA_BUSINESS_ID']
);
```

## 🔄 Flujos de Integración

El SDK de Remesita soporta 3 flujos principales de integración:

### 1️⃣ [Pago Simple](docs/CASO-1-PAGO-SIMPLE.md)

Cobra a un cliente y recibe los fondos directamente en tu wallet.

**Ideal para:**
- E-commerce
- Servicios profesionales
- Ventas de productos digitales

```php
$result = $remesita->initiatePayment([
    'amount' => 100.00,
    'account' => '+1234567890',
    'concept' => 'Compra en MiTienda',
    'customId' => 'ORDER-123',
    'ipnUrl' => 'https://mitienda.com/webhook'
]);
```

📖 **[Ver documentación completa →](docs/CASO-1-PAGO-SIMPLE.md)**

---

### 2️⃣ [Pago con Distribución](docs/CASO-2-DISTRIBUCION.md)

Cobra un monto y distribuye automáticamente entre múltiples wallets.

**Ideal para:**
- Marketplaces
- Plataformas multi-vendor
- Sistemas de afiliados
- Reparto entre socios

```php
$result = $remesita->initiatePayment([
    'amount' => 100.00,
    'account' => '+1234567890',
    'concept' => 'Compra en Marketplace',
    'distribution' => [
        [
            'account' => 'wallet-vendedor',
            'fixed_amount' => 70.00
        ],
        [
            'account' => 'wallet-afiliado',
            'percentage_amount' => 10
        ]
        // Tu wallet recibe automáticamente el resto: $20
    ]
]);
```

📖 **[Ver documentación completa →](docs/CASO-2-DISTRIBUCION.md)**

---

### 3️⃣ [Suscripciones](docs/CASO-3-SUSCRIPCIONES.md)

Crea pagos recurrentes automáticos con distribución opcional.

**Ideal para:**
- SaaS
- Membresías
- Servicios por suscripción
- Cursos online

```php
$result = $remesita->initiatePayment([
    'amount' => 9.99,
    'account' => '+1234567890',
    'concept' => 'Suscripción Premium',
    'subscription' => [
        'amount' => 9.99,
        'frequency' => '@monthly',
        'times' => -1 // Infinito
    ]
]);
```

📖 **[Ver documentación completa →](docs/CASO-3-SUSCRIPCIONES.md)**

---

## 💡 Ejemplos de Uso

### Ejemplo Completo: Primera Compra con 2FA

```php
<?php

use Remesita\SDK\RemesitaClient;
use Remesita\SDK\RemesitaException;

$remesita = new RemesitaClient(
    $_ENV['REMESITA_API_TOKEN'],
    $_ENV['REMESITA_BUSINESS_ID']
);

try {
    // Paso 1: Iniciar pago
    $result = $remesita->initiatePayment([
        'amount' => 50.00,
        'account' => '+1234567890',
        'concept' => 'Producto XYZ',
        'customId' => 'ORDER-789',
        'ipnUrl' => 'https://mitienda.com/webhook'
    ]);

    // Paso 2: Si requiere autenticación
    if ($result['status'] === 'two-factor-choice') {
        // Mostrar opciones al usuario (SMS, Email, etc)
        $authOptions = $result['options'];
        $paymentSession = $result['paymentSession'];
        
        // Usuario selecciona canal (ej: SMS)
        $selectedChannel = $authOptions[0]['value'];
        
        // Solicitar código
        $codeResult = $remesita->requestAuthCode(
            $paymentSession,
            $selectedChannel
        );
        
        $authToken = $codeResult['paymentAuthorizationToken'];
        
        // Usuario ingresa código recibido
        $code = '123456';
        
        // Validar código
        $validation = $remesita->validateAuthCode(
            $paymentSession,
            $authToken,
            $code
        );
        
        if ($validation['status'] === 'approved') {
            // ✅ Pago exitoso
            $orderReference = $validation['order'];
            $token = $validation['paymentAuthorizationToken'];
            
            // Guardar token para futuros pagos
            saveCustomerToken($customerAccount, $token);
            
            echo "Pago procesado: {$orderReference}";
        }
    }
    
    // Paso 3: Si tenía token guardado, pago instantáneo
    if ($result['status'] === 'approved') {
        echo "Pago procesado instantáneamente: {$result['order']}";
    }
    
} catch (RemesitaException $e) {
    echo "Error: " . $e->getMessage();
}
```

### Ejemplo: Marketplace con Comisiones

```php
<?php

// Venta de $150 en un marketplace
$orderTotal = 150.00;

// Distribución:
// - Vendedor: $120 (80%)
// - Afiliado: $15 (10%)
// - Plataforma: $15 (resto)

$distribution = [
    [
        'account' => $vendor->getWalletAddress(),
        'fixed_amount' => 120.00
    ],
    [
        'account' => $affiliate->getWalletAddress(),
        'percentage_amount' => 10
    ]
];

// Calcular cuánto recibe la plataforma
$platformAmount = $remesita->calculateMerchantAmount($orderTotal, $distribution);
echo "La plataforma recibirá: \$" . $platformAmount; // $15.00

try {
    $result = $remesita->initiatePayment([
        'amount' => $orderTotal,
        'account' => $customer->getAccount(),
        'concept' => "Orden #{$order->getId()}",
        'distribution' => $distribution,
        'savedToken' => $customer->getRemesitaToken(),
        'customId' => $order->getId()
    ]);
    
    if ($result['status'] === 'approved') {
        // Actualizar orden
        $order->markAsPaid($result['order']);
