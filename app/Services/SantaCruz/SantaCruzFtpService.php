<?php

namespace App\Services\SantaCruz;

use App\Contracts\SantaCruzFtpClientInterface;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class SantaCruzFtpService implements SantaCruzFtpClientInterface
{
    /** @var resource|null */
    private $connection = null;

    private function connect(): void
    {
        if ($this->connection !== null) {
            return;
        }

        $host = config('santacruz.ftp.host');
        if (! $host) {
            throw new RuntimeException('Falta configurar SANTA_CRUZ_FTP_HOST en .env');
        }

        $port = config('santacruz.ftp.port', 21);
        $timeout = config('santacruz.ftp.timeout', 30);

        if (! function_exists('ftp_connect')) {
            $ini = php_ini_loaded_file() ?: '(ninguno)';
            throw new RuntimeException(
                'La extensión PHP «ftp» no está cargada (SAPI: '.PHP_SAPI.", php.ini: {$ini}). En producción suele bastar con la extensión «curl» y el cliente alternativo; si no, habilitá «ftp» en el PHP del servidor web."
            );
        }

        $conn = @\ftp_connect($host, (int) $port, (int) $timeout);
        if ($conn === false) {
            throw new RuntimeException('No se pudo conectar al FTP de Santa Cruz (host/puerto).');
        }

        $user = (string) config('santacruz.ftp.username');
        $pass = (string) config('santacruz.ftp.password');
        if ($user === '' || $pass === '') {
            \ftp_close($conn);
            throw new RuntimeException(
                'FTP Santa Cruz: usuario o contraseña vacíos en la configuración. Si editaste el .env en el servidor, ejecutá `php artisan config:clear` o volvé a generar la caché con `php artisan config:cache` para que se apliquen los valores.'
            );
        }

        if (! @\ftp_login($conn, $user, $pass)) {
            \ftp_close($conn);
            throw new RuntimeException(
                'Credenciales FTP de Santa Cruz rechazadas por el servidor (usuario o contraseña incorrectos, o cuenta sin acceso desde esta IP). Revisá mayúsculas/minúsculas y que el .env en producción no tenga comillas de más; claves con # o espacios deben ir entre comillas dobles. Si usás `config:cache`, regenerala tras cambiar el .env.'
            );
        }

        if (config('santacruz.ftp.passive', true)) {
            @\ftp_pasv($conn, true);
        }

        $path = $this->normalizeFtpPath((string) config('santacruz.ftp.path', '/'));
        if ($path !== '' && $path !== '/') {
            if (! @\ftp_chdir($conn, $path)) {
                \ftp_close($conn);
                throw new RuntimeException('No se pudo acceder a la carpeta FTP: '.$path);
            }
        }

        $this->connection = $conn;
    }

    private function normalizeFtpPath(string $path): string
    {
        $path = trim($path);
        if ($path === '') {
            return '/';
        }
        if ($path[0] !== '/') {
            return '/'.$path;
        }

        return rtrim($path, '/') ?: '/';
    }

    public function disconnect(): void
    {
        if (is_resource($this->connection)) {
            @\ftp_close($this->connection);
        }
        $this->connection = null;
    }

    public function __destruct()
    {
        $this->disconnect();
    }

    public function listXmlFiles(): array
    {
        $this->connect();
        $list = @\ftp_nlist($this->connection, '.');
        if ($list === false) {
            return [];
        }

        $out = [];
        foreach ($list as $item) {
            $base = basename($item);
            if (str_ends_with(strtolower($base), '.xml')) {
                $out[] = $base;
            }
        }
        sort($out);

        return array_values(array_unique($out));
    }

    public function getFileContents(string $basename): string
    {
        $this->connect();
        $basename = basename($basename);
        $tmp = tempnam(sys_get_temp_dir(), 'scz_');
        if ($tmp === false) {
            throw new RuntimeException('No se pudo crear archivo temporal.');
        }

        try {
            if (! @\ftp_get($this->connection, $tmp, $basename, \FTP_BINARY)) {
                throw new RuntimeException('No se pudo descargar el archivo: '.$basename);
            }
            $content = (string) file_get_contents($tmp);

            return $content;
        } finally {
            @unlink($tmp);
        }
    }

    public function moveToProcessed(string $basename): void
    {
        $this->connect();
        $basename = basename($basename);
        $sub = (string) config('santacruz.ftp.processed_subpath', 'procesados');

        @\ftp_mkdir($this->connection, $sub);

        $dest = $sub.'/'.$basename;
        if (! @\ftp_rename($this->connection, $basename, $dest)) {
            Log::error('SantaCruzFtp: rename fallido', ['from' => $basename, 'to' => $dest]);
            throw new RuntimeException('No se pudo mover el archivo a procesados: '.$basename.' → '.$dest);
        }
    }
}
