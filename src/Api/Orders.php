<?php

namespace Booni3\DynamicWms\Api;

use Booni3\DynamicWms\Data\CustomsMoney;
use InvalidArgumentException;

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
        if (array_key_exists('customs_money', $params) && array_key_exists('customs_declaration', $params)) {
            throw new InvalidArgumentException('Customs money and customs declaration are mutually exclusive.');
        }
        $parameters = [
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
            'pick_note' => $params['pick_note'] ?? '',
            'received_at' => $params['received_at'] ?? '',
            'earliest_ship_date' => $params['earliest_ship_date'] ?? '',
            'latest_ship_date' => $params['latest_ship_date'] ?? '',
            'invoice_terms' => $params['invoice_terms'] ?? '',
            'importer_tax_id' => $params['importer_tax_id'] ?? '',
            'items' => $items,
            'bundles' => $params['bundles'] ?? null,
        ];

        if (array_key_exists('customs_money', $params)) {
            $money = $params['customs_money'];
            if (is_array($money)) {
                $money = CustomsMoney::fromArray($money);
            }
            if (!$money instanceof CustomsMoney) {
                throw new InvalidArgumentException('Customs money must be a CustomsMoney object or array.');
            }
            $parameters['customs_money'] = $money->toArray();

            return $this->postJson('order', $parameters);
        }

        if (array_key_exists('customs_declaration', $params)) {
            $parameters['customs_declaration'] = $params['customs_declaration'];

            return $this->postJson('order', $parameters);
        }

        return $this->post('order', $parameters);
    }

    public function acceptedCustomsMoney(array $response): ?CustomsMoney
    {
        return isset($response['customs_money'])
            ? CustomsMoney::fromArray($response['customs_money'])
            : null;
    }

    public function hold($orderId)
    {
        return $this->post('order/' . $orderId . '/hold');
    }

    public function unhold($orderId)
    {
        return $this->post('order/' . $orderId . '/unhold');
    }

    public function cancel($orderId)
    {
        return $this->post('order/' . $orderId . '/cancel');
    }

    public function deleteOrder($orderId)
    {
        return $this->delete('order/' . $orderId);
    }
}
