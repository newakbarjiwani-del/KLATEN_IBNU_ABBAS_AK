<?php

namespace App\Database\Connectors;

use Illuminate\Database\Connectors\MySqlConnector;
use PDOException;

class TimeoutMySqlConnector extends MySqlConnector
{
    public function connect(array $config)
    {
        $this->guardReachable($config);

        return parent::connect($config);
    }

    private function guardReachable(array $config): void
    {
        $socket = (string) ($config['unix_socket'] ?? '');

        if ($socket !== '') {
            return;
        }

        $host = (string) ($config['host'] ?? '127.0.0.1');
        $port = (int) ($config['port'] ?? 3306);
        $errorCode = 0;
        $errorMessage = '';
        $connection = @fsockopen($host, $port, $errorCode, $errorMessage, 3);

        if ($connection !== false) {
            fclose($connection);

            return;
        }

        throw new PDOException("SQLSTATE[HY000] [2002] {$errorMessage}", 2002);
    }
}
