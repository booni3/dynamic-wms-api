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

    private function ordersWithResponses(array $responses, array &$history): Orders
    {
        $mock = new MockHandler($responses);
        $stack = HandlerStack::create($mock);
        $stack->push(Middleware::history($history));

        return new Orders(new Client(['handler' => $stack]), 'test-token');
    }
}
