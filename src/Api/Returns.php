<?php

namespace Booni3\DynamicWms\Api;

class Returns extends ApiClient
{
    public function getReturns($page = 1)
    {
        return $this->get('returns', compact('page'));
    }

    public function getReturn($deliveryId)
    {
        return $this->get('returns/' . $deliveryId);
    }

    public function createReturn(array $params)
    {
        $options = array_filter([
            'customer_id' => $params['customer_id'] ?? '',
            'order_id' => $params['order_id'] ?? '',
            'fulfilment_id' => $params['fulfilment_id'] ?? '',
            'type' => $params['type'] ?? '',
            'reason' => $params['reason'] ?? '',
            'reference' => $params['reference'] ?? '',
            'expected_at' => $params['expected_at'] ?? '',
            'items' => $params['items'] ?? [],
        ], null);

        return $this->post('returns', $options);
    }
}
