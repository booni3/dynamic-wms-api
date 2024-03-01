<?php

namespace Booni3\DynamicWms\Api;

class Deliveries extends ApiClient
{
    public function getDeliveries($page = 1)
    {
        return $this->get('delivery', compact('page'));
    }

    public function getDelivery($deliveryId)
    {
        return $this->get('delivery/' . $deliveryId);
    }

    public function createDelivery(array $params, array $items = [])
    {
        return $this->post('delivery', [
            'delivery_type' => $params['delivery_type'] ?? '',
            'supplier_name' => $params['supplier_name'] ?? '',
            'carrier_name' => $params['carrier_name'] ?? '',
            'supplier_ref' => $params['supplier_ref'] ?? '',
            'merchant_ref' => $params['merchant_ref'] ?? '',
            'expected_at' => $params['expected_at'] ?? '',
            'items' => $items
        ]);
    }
    public function updateDelivery($deliveryId, array $params)
    {
        $payload = [
            'supplier_name' => $params['supplier_name'] ?? null,
            'carrier_name' => $params['carrier_name'] ?? null,
            'supplier_ref' => $params['supplier_ref'] ?? null,
            'merchant_ref' => $params['merchant_ref'] ?? null,
            'expected_at' => $params['expected_at'] ?? null,
            'expected_at_state' => $params['expected_at_state'] ?? null,
        ];

        return $this->put("delivery/$deliveryId", array_filter($payload, fn($row) => $row !== null));
    }
}
