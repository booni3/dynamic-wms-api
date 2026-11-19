<?php

namespace Booni3\DynamicWms\Tests;

use Booni3\DynamicWms\Api\Orders;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

class OrdersTest extends TestCase
{
    public function test_it_sends_a_customs_declaration_order_as_the_exact_json_body(): void
    {
        $history = [];
        $orders = $this->ordersWithResponses([
            new Response(200, [], '{"id":50264787}'),
        ], $history);
        $expectedBody = $this->customsOrderFixture();
        $params = $expectedBody;
        $items = $params['items'];
        unset($params['items']);

        $result = $orders->createOrder($params, $items);

        $request = $history[0]['request'];
        $actualBody = json_decode((string) $request->getBody(), true);
        $this->assertSame(['id' => 50264787], $result);
        $this->assertSame('POST', $request->getMethod());
        $this->assertSame('order', (string) $request->getUri());
        $this->assertSame('application/json', $request->getHeaderLine('Content-Type'));
        $this->assertSame($expectedBody, $actualBody);
        $this->assertSame('physical-line-1', $actualBody['items'][0]['line_id']);
        $this->assertNull($actualBody['customs_declaration']['taxation']);
        $this->assertSame([], $actualBody['customs_declaration']['charges']);
    }

    public function test_it_passes_unknown_nested_customs_fields_to_dynamic(): void
    {
        $history = [];
        $orders = $this->ordersWithResponses([
            new Response(200, [], '{}'),
        ], $history);
        $fixture = $this->customsOrderFixture();
        $items = $fixture['items'];
        unset($fixture['items']);
        $fixture['customs_declaration']['unknown_nested_field'] = ['sentinel' => true];

        $orders->createOrder($fixture, $items);

        $body = json_decode((string) $history[0]['request']->getBody(), true);
        $this->assertSame(
            ['sentinel' => true],
            $body['customs_declaration']['unknown_nested_field']
        );
    }

    public function test_it_preserves_the_legacy_form_encoded_order_request(): void
    {
        $history = [];
        $orders = $this->ordersWithResponses([
            new Response(200, [], '{}'),
        ], $history);
        $items = [[
            'sku' => 'LEGACY-SKU',
            'qty' => 2,
            'unit_cost' => 12.50,
        ]];
        $expectedBody = [
            'company_name' => '',
            'full_name' => 'Legacy Customer',
            'email' => '',
            'telephone' => '',
            'address1' => '',
            'address2' => '',
            'town' => '',
            'region' => '',
            'postcode' => '',
            'country_code' => '',
            'unique_id' => 'legacy-order-1',
            'order_reference' => '',
            'source' => '',
            'sub_source' => '',
            'shipping_service' => 'domestic',
            'shipping_note' => '',
            'pick_note' => '',
            'received_at' => '',
            'earliest_ship_date' => '',
            'latest_ship_date' => '',
            'invoice_terms' => '',
            'importer_tax_id' => '',
            'items' => $items,
            'bundles' => null,
        ];

        $orders->createOrder([
            'full_name' => 'Legacy Customer',
            'unique_id' => 'legacy-order-1',
            'shipping_service' => 'domestic',
        ], $items);

        $request = $history[0]['request'];
        $this->assertSame('POST', $request->getMethod());
        $this->assertSame('order', (string) $request->getUri());
        $this->assertSame('application/x-www-form-urlencoded', $request->getHeaderLine('Content-Type'));
        $this->assertSame(http_build_query($expectedBody), (string) $request->getBody());
    }

    public function test_it_deletes_an_order_using_the_order_resource_endpoint(): void
    {
        $history = [];
        $orders = $this->ordersWithResponses([
            new Response(204),
        ], $history);

        $result = $orders->deleteOrder(50264787);

        $this->assertSame([], $result);
        $this->assertSame('DELETE', $history[0]['request']->getMethod());
        $this->assertSame('order/50264787', (string) $history[0]['request']->getUri());
    }

    public function test_it_forwards_optional_registration_country_for_json_and_form_orders(): void
    {
        foreach ([false, true] as $json) {
            foreach (['IE', null] as $country) {
                $history = [];
                $orders = $this->ordersWithResponses([new Response(200, [], '{}')], $history);
                $params = ['registration_country_code' => $country, 'importer_tax_id' => 'IM0000000000'];
                if ($json) {
                    $params['customs_declaration'] = $this->customsOrderFixture()['customs_declaration'];
                }
                $orders->createOrder($params, []);
                $raw = (string) $history[0]['request']->getBody();
                if ($json) {
                    $body = json_decode($raw, true);
                    $this->assertArrayHasKey('registration_country_code', $body);
                    $this->assertSame($country, $body['registration_country_code']);
                } else {
                    parse_str($raw, $body);
                    if ($country === null) {
                        $this->assertArrayNotHasKey('registration_country_code', $body);
                    } else {
                        $this->assertSame($country, $body['registration_country_code']);
                    }
                }
            }
        }
    }

    public function test_it_forwards_service_due_dates_for_each_create_encoding(): void
    {
        foreach (['form', 'declaration', 'money'] as $encoding) {
            $history = [];
            $orders = $this->ordersWithResponses([new Response(200, [], '{}')], $history);
            $params = ['service_due_date' => '2026-12-01'];
            if ($encoding === 'declaration') {
                $params['customs_declaration'] = $this->customsOrderFixture()['customs_declaration'];
            } elseif ($encoding === 'money') {
                $params['customs_money'] = [
                    'schema_version' => 1,
                    'currency' => 'GBP',
                    'source' => ['system' => 'test', 'reference' => 'order-1', 'integrity_hash' => 'sha256:'.str_repeat('a', 64)],
                    'sales' => [[
                        'sale_line_id' => 'sale-1', 'net_minor' => 100, 'vat_minor' => 20, 'gross_minor' => 120,
                        'physical_lines' => [['line_id' => 'line-1', 'sku' => 'GOODS', 'quantity' => 1]],
                    ]],
                    'delivery' => ['net_minor' => 0, 'vat_minor' => 0, 'gross_minor' => 0],
                ];
            }

            $orders->createOrder($params, []);

            $request = $history[0]['request'];
            if ($encoding === 'form') {
                parse_str((string) $request->getBody(), $body);
            } else {
                $body = json_decode((string) $request->getBody(), true);
            }
            $this->assertSame('2026-12-01', $body['service_due_date']);
        }
    }

    public function test_it_omits_an_absent_service_due_date_from_create(): void
    {
        $history = [];
        $orders = $this->ordersWithResponses([new Response(200, [], '{}')], $history);

        $orders->createOrder([], []);

        parse_str((string) $history[0]['request']->getBody(), $body);
        $this->assertArrayNotHasKey('service_due_date', $body);
    }

    public function test_it_preserves_date_changes_null_clears_and_omission_in_json_order_updates(): void
    {
        foreach ([
            ['service_due_date' => '2026-12-01'],
            ['service_due_date' => null],
            ['address1' => '10 Example Street'],
            ['address1' => '10 Example Street', 'service_due_date' => '2026-12-02'],
            ['address1' => '10 Example Street', 'service_due_date' => null],
        ] as $params) {
            $history = [];
            $orders = $this->ordersWithResponses([new Response(200, [], '{"id":50264787}')], $history);

            $result = $orders->updateOrder(50264787, $params);

            $request = $history[0]['request'];
            $this->assertSame(['id' => 50264787], $result);
            $this->assertSame('PUT', $request->getMethod());
            $this->assertSame('order/50264787', (string) $request->getUri());
            $this->assertSame('application/json', $request->getHeaderLine('Content-Type'));
            $this->assertSame($params, json_decode((string) $request->getBody(), true));
        }
    }

    private function ordersWithResponses(array $responses, array &$history): Orders
    {
        $mock = new MockHandler($responses);
        $stack = HandlerStack::create($mock);
        $stack->push(Middleware::history($history));

        return new Orders(new Client(['handler' => $stack]), 'test-token');
    }

    private function customsOrderFixture(): array
    {
        $fixture = json_decode(
            file_get_contents(__DIR__.'/Fixtures/order-create-customs-declaration-v1.json'),
            true
        );

        if (!is_array($fixture)) {
            throw new \RuntimeException('The customs order fixture must contain valid JSON.');
        }

        return $fixture;
    }
}
