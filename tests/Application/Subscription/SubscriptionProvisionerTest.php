<?php

namespace App\Tests\Application\Subscription;

use App\Application\Subscription\SubscriptionProvisioner;
use App\Integration\Payload\PayloadClientInterface;
use PHPUnit\Framework\TestCase;

final class SubscriptionProvisionerTest extends TestCase
{
    public function testProvisionInitialDelegatesToClient(): void
    {
        $mock = $this->createMock(PayloadClientInterface::class);
        $mock->expects(self::once())
            ->method('createSubscription')
            ->with(6, 'sub_123', 'active', 'paddle')
            ->willReturn(['id' => 1, 'subscriptionId' => 'sub_123']);

        $prov = new SubscriptionProvisioner($mock);
        $res = $prov->provisionInitial(6, 'sub_123', 'active');

        self::assertSame('sub_123', $res['subscriptionId']);
    }

    public function testListUsesClient(): void
    {
        $mock = $this->createMock(PayloadClientInterface::class);
        $mock->expects(self::once())
            ->method('listSubscriptions')
            ->with(2, 5)
            ->willReturn(['docs' => [1, 2, 3]]);

        $prov = new SubscriptionProvisioner($mock);
        $res = $prov->list(2, 5);

        self::assertArrayHasKey('docs', $res);
    }
}
