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
}