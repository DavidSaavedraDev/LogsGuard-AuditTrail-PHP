<?php

declare(strict_types=1);

namespace LogsGuard\Config;

use PDO;
use PDOException;
use RuntimeException;

final class Database
{
    private static ?PDO $connection = null;

    public static function getConnection(): PDO
    {
        if (self::$connection instanceof PDO) {
            return self::$connection;
        }

        $config = self::loadConfiguration();
        $driver = strtolower($config['DB_DRIVER'] ?? 'mysql');

        if (!in_array($driver, ['mysql', 'pgsql'], true)) {
            throw new RuntimeException('DB_DRIVER debe ser mysql o pgsql.');
        }

        $host = $config['DB_HOST'] ?? '127.0.0.1';
        $port = $config['DB_PORT'] ?? ($driver === 'mysql' ? '3306' : '5432');
        $database = $config['DB_NAME'] ?? '';
        $user = $config['DB_USER'] ?? '';
        $password = $config['DB_PASSWORD'] ?? '';

        if ($database === '' || $user === '') {
            throw new RuntimeException('Faltan DB_NAME o DB_USER en la configuración.');
        }

        $dsn = $driver === 'mysql'
            ? sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $host, $port, $database)
            : sprintf('pgsql:host=%s;port=%s;dbname=%s', $host, $port, $database);

        try {
            self::$connection = new PDO($dsn, $user, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $exception) {
            error_log('LogsGuard: no se pudo conectar a la base de datos: ' . $exception->getMessage());
            throw new RuntimeException('No fue posible conectar con la base de datos.', 0, $exception);
        }

        return self::$connection;
    }

    /**
     * @return array<string, string>
     */
    private static function loadConfiguration(): array
    {
        $configuration = [];
        $envFile = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . '.env';

        if (is_readable($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            if ($lines === false) {
                throw new RuntimeException('No fue posible leer el archivo .env.');
            }

            foreach ($lines as $line) {
                $line = trim($line);
                if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
                    continue;
                }

                [$key, $value] = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);
                $value = trim($value, "\"'");

                if ($key !== '') {
                    $configuration[$key] = $value;
                }
            }
        }

        foreach (array_keys($configuration) as $key) {
            $environmentValue = getenv($key);
            if ($environmentValue !== false) {
                $configuration[$key] = $environmentValue;
            }
        }

        foreach (['DB_DRIVER', 'DB_HOST', 'DB_PORT', 'DB_NAME', 'DB_USER', 'DB_PASSWORD'] as $key) {
            $environmentValue = getenv($key);
            if ($environmentValue !== false) {
                $configuration[$key] = $environmentValue;
            }
        }

        return $configuration;
    }
}
