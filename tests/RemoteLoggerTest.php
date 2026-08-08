<?php

namespace RemoteLogger\Tests;

use RemoteLogger\RemoteLogger;

class RemoteLoggerTest extends TestCase
{
    public function test_it_manages_global_logging_context(): void
    {
        $this->assertNull(RemoteLogger::getCategory());
        $this->assertNull(RemoteLogger::getSubcategory());

        RemoteLogger::setCategory('billing');
        RemoteLogger::setSubcategory('invoices');

        $this->assertSame('billing', RemoteLogger::getCategory());
        $this->assertSame('invoices', RemoteLogger::getSubcategory());

        RemoteLogger::setContext('orders', 'checkout');

        $this->assertSame('orders', RemoteLogger::getCategory());
        $this->assertSame('checkout', RemoteLogger::getSubcategory());

        RemoteLogger::setContext(null);

        $this->assertNull(RemoteLogger::getCategory());
        $this->assertNull(RemoteLogger::getSubcategory());

        RemoteLogger::setContext('temporary', 'context');
        RemoteLogger::flush();

        $this->assertNull(RemoteLogger::getCategory());
        $this->assertNull(RemoteLogger::getSubcategory());
    }
}
