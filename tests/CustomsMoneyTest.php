<?php

namespace Booni3\DynamicWms\Tests;

use Booni3\DynamicWms\Api\Orders;
use Booni3\DynamicWms\Data\CustomsMoney;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class CustomsMoneyTest extends TestCase
{
    public function test_money_uses_json_and_exposes_the_accepted_money(): void
    {
        $history = [];
        $money = CustomsMoney::fromArray($this->money());
        $orders = $this->orders($history, ['customs_money' => $money->toArray()]);

        $response = $orders->createOrder([
            'country_code' => 'DE',
            'customs_money' => $money,
        ], [['line_id' => 'a', 'sku' => 'GOODS', 'qty' => 1]]);

        $request = $history[0]['request'];
        $body = json_decode((string) $request->getBody(), true);
        $this->assertSame('application/json', $request->getHeaderLine('Content-Type'));
        $this->assertSame($money->toArray(), $body['customs_money']);
        $this->assertSame('a', $body['items'][0]['line_id']);
        $this->assertSame($money->toArray(), $orders->acceptedCustomsMoney($response)->toArray());
        $this->assertNull($orders->acceptedCustomsMoney([]));
        $this->assertNull($orders->acceptedCustomsMoney(['customs_money' => null]));
    }

    public function test_array_money_is_canonical_and_does_not_persist_to_the_next_request(): void
    {
        $history = [];
        $orders = $this->orders($history);
        $money = $this->money();
        $money['sales'][] = $money['sales'][0];
        $money['sales'][0]['sale_line_id'] = 'z';
        $money['sales'][0]['physical_lines'][0]['line_id'] = 'z';
        $money['sales'][1]['physical_lines'][] = ['line_id' => '0', 'sku' => 'EXTRA', 'quantity' => 1];

        $orders->createOrder(['customs_money' => $money], []);
        $orders->createOrder([], []);

        $body = json_decode((string) $history[0]['request']->getBody(), true);
        $this->assertSame(['sale', 'z'], array_column($body['customs_money']['sales'], 'sale_line_id'));
        $this->assertSame(['0', 'a'], array_column($body['customs_money']['sales'][0]['physical_lines'], 'line_id'));
        $this->assertSame('application/x-www-form-urlencoded', $history[1]['request']->getHeaderLine('Content-Type'));
        $this->assertStringNotContainsString('customs_money', (string) $history[1]['request']->getBody());
    }

    public function test_money_and_declaration_are_rejected_before_transport(): void
    {
        $history = [];
        $orders = $this->orders($history);
        try {
            $orders->createOrder(['customs_money' => $this->money(), 'customs_declaration' => []], []);
            $this->fail('Expected mutual exclusion.');
        } catch (InvalidArgumentException $exception) {
            $this->assertSame([], $history);
        }
    }

    public function test_invalid_shapes_are_rejected(): void
    {
        foreach ($this->invalidMoney() as $case) {
            try {
                CustomsMoney::fromArray($case[0]);
                $this->fail('Expected invalid customs money to be rejected.');
            } catch (InvalidArgumentException $exception) {
                $this->assertNotSame('', $exception->getMessage());
            }
        }
    }

    private function invalidMoney(): array
    {
        $cases = [];
        foreach ([
            ['currency', 'EUR'], ['schema_version', '1'], ['sales', []],
            ['source', []], ['delivery', null], ['unexpected', true],
        ] as $change) {
            $money = $this->money();
            $money[$change[0]] = $change[1];
            $cases[] = [$money];
        }
        foreach ([['net_minor', 0], ['vat_minor', -1], ['gross_minor', 119], ['net_minor', 100.0]] as $change) {
            $money = $this->money();
            $money['sales'][0][$change[0]] = $change[1];
            $cases[] = [$money];
        }
        $money = $this->money();
        $money['sales'][0]['physical_lines'][0]['quantity'] = 0;
        $cases[] = [$money];
        $money = $this->money();
        $money['source']['integrity_hash'] = 'sha256:'.str_repeat('A', 64);
        $cases[] = [$money];
        $money = $this->money();
        $money['sales'][] = $money['sales'][0];
        $cases[] = [$money];
        $money['sales'][1]['sale_line_id'] = 'second';
        $cases[] = [$money];
        $money = $this->money();
        $money['delivery']['gross_minor'] = 1;
        $cases[] = [$money];
        $money = $this->money();
        $money['sales'][0]['net_minor'] = PHP_INT_MAX;
        $money['sales'][0]['gross_minor'] = PHP_INT_MAX;
        $cases[] = [$money];

        return $cases;
    }

    private function money(): array
    {
        return [
            'schema_version' => 1,
            'currency' => 'GBP',
            'source' => ['system' => 'oms-linnworks', 'reference' => 'retained-order', 'integrity_hash' => 'sha256:'.str_repeat('a', 64)],
            'sales' => [[
                'sale_line_id' => 'sale', 'net_minor' => 100, 'vat_minor' => 20, 'gross_minor' => 120,
                'physical_lines' => [['line_id' => 'a', 'sku' => 'GOODS', 'quantity' => 1]],
            ]],
            'delivery' => ['net_minor' => 0, 'vat_minor' => 0, 'gross_minor' => 0],
        ];
    }

    private function orders(array &$history, array $response = []): Orders
    {
        $stack = HandlerStack::create(new MockHandler([
            new Response(200, [], json_encode($response)),
            new Response(200, [], '{}'),
        ]));
        $stack->push(Middleware::history($history));

        return new Orders(new Client(['handler' => $stack]), 'test-token');
    }
}
