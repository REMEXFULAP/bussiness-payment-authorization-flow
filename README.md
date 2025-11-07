# Caso de Uso 1: Pago Simple a Wallet del Comercio

## 📋 Descripción

Este es el flujo más básico donde un comercio cobra un monto y los fondos caen directamente en la wallet asociada al business. Es ideal para:

- Ventas de productos/servicios
- Pagos únicos
- Transacciones simples sin intermediarios

## 🔄 Diagrama de Flujo

```mermaid
sequenceDiagram
    participant Cliente
    participant Merchant
    participant API Remesita
    participant Usuario Remesita
    
    Note over Merchant,API Remesita: PRIMERA VEZ (Sin token guardado)
    
    Merchant->>API Remesita: POST /payment/initiate
    Note right of Merchant: amount: 100<br/>concept: "Compra #123"<br/>account: wallet/phone<br/>SIN token
    
    API Remesita-->>Merchant: 203 two-factor-choice
    Note right of API Remesita: options: [SMS, Email, etc]<br/>paymentSession: "sess-123"
    
    Merchant->>Cliente: Mostrar opciones 2FA
    Cliente->>Merchant: Selecciona canal (SMS)
    
    Merchant->>API Remesita: POST /authorization/request
    Note right of Merchant: paymentSession: "sess-123"<br/>channel: "smsKey"
    
    API Remesita->>Usuario Remesita: Envía código SMS
    API Remesita-->>Merchant: 200 two-factor-sent
    Note right of API Remesita: paymentAuthorizationToken
    
    Usuario Remesita->>Cliente: Proporciona código
    Cliente->>Merchant: Ingresa código 123456
    
    Merchant->>API Remesita: POST /authorization/validate
    Note right of Merchant: code: 123456<br/>paymentAuthorizationToken
    
    API Remesita->>API Remesita: Procesa pago
    API Remesita-->>Merchant: 200 approved
    Note right of API Remesita: order: "RM12345"<br/>TOKEN VÁLIDO
    
    Merchant->>Merchant: Guarda token para futuro
    
    Note over Merchant,API Remesita: PAGOS SIGUIENTES (Con token guardado)
    
    Merchant->>API Remesita: POST /payment/initiate
    Note right of Merchant: amount: 50<br/>CON token guardado
    
    API Remesita->>API Remesita: Valida token y procesa
    API Remesita-->>Merchant: 200 approved
    Note right of API Remesita: Pago instantáneo
    
    API Remesita->>Merchant: Webhook IPN
    Note right of API Remesita: Notifica estado final
```

## 💻 Ejemplo de Integración - PHP SDK

### Servicio de Pago

```php
<?php

  
```
 

## 🔑 Puntos Clave

1. **Guardar el Token**: Una vez validado, guarda el `paymentAuthorizationToken` asociado al cliente
2. **Reutilizar Token**: En pagos futuros, envía el token guardado para procesamiento instantáneo
3. **Manejo de Errores**: Implementa reintentos y manejo adecuado de errores de red
4. **Webhooks**: Siempre implementa el endpoint IPN para confirmación definitiva del pago
5. **Seguridad**: Valida las firmas de los webhooks según documentación de Remesita
6. **Expiración**: Los tokens tienen validez temporal, maneja la renovación cuando expiren
7. **Límites**: Ten en cuenta los límites por nivel de cliente (diarios/mensuales)
