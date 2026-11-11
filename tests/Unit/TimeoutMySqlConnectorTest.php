<?php

namespace Tests\Unit;

use App\Database\Connectors\TimeoutMySqlConnector;
use PDOException;
use Tests\TestCase;

class TimeoutMySqlConnectorTest extends TestCase
{
    public function test_a_closed_port_fails_before_the_php_time_limit(): void
    {
        $started = microtime(true);

        try {
            (new TimeoutMySqlConnector)->connect([
                'host' => '127.0.0.1',
                'port' => 1,
                'database' => 'klaten_ibnuabbas',
                'username' => 'root',
                'password' => 'secret',
                'unix_socket' => '',
            ]);
            $this->fail('Koneksi ke port tertutup harus gagal.');
        } catch (PDOException $exception) {
            $this->assertSame(2002, $exception->getCode());
            $this->assertLessThan(5, microtime(true) - $started);
        }
    }
}
