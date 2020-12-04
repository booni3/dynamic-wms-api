<?php

namespace Booni3\DynamicWms\Api;

class Orders extends ApiClient
{
    public function getOrders($page = 1)
    {
        return $this->get('order', compact('page'));
    }

    public function getOrder($orderId)
    {
        return $this->get('order/' . $orderId);
    }

    public function getOrderByReference($orderRef)
    {
        return $this->get('get-order-by-ref',[
            'order_reference' => $orderRef
        ]);
    }

    public function createOrder(array $params, array $items)
    {
        return $this->post('order', [
            'company_name' => $params['company_name'] ?? '',
            'full_name' => $params['full_name'] ?? '',
            'email' => $params['email'] ?? '',
            'telephone' => $params['telephone'] ?? '',
            'address1' => $params['address1'] ?? '',
            'address2' => $params['address2'] ?? '',
            'town' => $params['town'] ?? '',
            'region' => $params['region'] ?? '',
            'postcode' => $params['postcode'] ?? '',
            'country_code' => $params['country_code'] ?? '',
            'unique_id' => $params['unique_id'] ?? '',
            'order_reference' => $params['order_reference'] ?? '',
            'source' => $params['source'] ?? '',
            'sub_source' => $params['sub_source'] ?? '',
            'shipping_service' => $params['shipping_service'] ?? '',
            'shipping_note' => $params['shipping_note'] ?? '',
            'received_at' => $params['received_at'] ?? '',
            'earliest_ship_date' => $params['earliest_ship_date'] ?? '',
            'latest_ship_date' => $params['latest_ship_date'] ?? '',
            'items' => $items
        ]);
    }
}