<?php

namespace App\Services\SantaCruz;

use App\Contracts\SantaCruzFtpClientInterface;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Cliente FTP Santa Cruz vía libcurl (extensión «curl»), para entornos sin extensión «ftp»
 * (p. ej. Apache usando otro php.ini que la CLI).
 */
class SantaCruzFtpCurlService implements SantaCruzFtpClientInterface
{
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

    /**
     * Ruta remota relativa al host (sin barra inicial), segmentos codificados para URL.
     */
    private function encodedRemotePath(string $suffix = ''): string
    {
        $base = trim($this->normalizeFtpPath((string) config('santacruz.ftp.path', '/')), '/');
        $suffix = str_replace('\\', '/', $suffix);
        $suffix = trim($suffix, '/');
        $full = $base === '' ? $suffix : ($suffix === '' ? $base : $base.'/'.$suffix);
        if ($full === '') {
            return '';
        }
        $segments = explode('/', $full);

        return implode('/', array_map('rawurlencode', $segments));
    }

    private function ftpBaseUrl(): string
    {
        $host = (string) config('santacruz.ftp.host');
        if ($host === '') {
            throw new RuntimeException('Falta configurar SANTA_CRUZ_FTP_HOST en .env');
        }
        $port = (int) config('santacruz.ftp.port', 21);
        $path = $this->encodedRemotePath('');

        return 'ftp://'.$host.':'.$port.($path !== '' ? '/'.$path : '');
    }

    private function urlDirectory(): string
    {
        return rtrim($this->ftpBaseUrl(), '/').'/';
    }

    private function urlFile(string $basename): string
    {
        $basename = basename($basename);
        $encodedFile = rawurlencode($basename);
        $base = rtrim($this->ftpBaseUrl(), '/');

        return $base.'/'.$encodedFile;
    }

    /** @return \CurlHandle|resource */
    private function newCurl(string $url)
    {
        $ch = curl_init($url);
        if ($ch === false) {
            throw new RuntimeException('FTP (cURL): no se pudo inicializar la sesión.');
        }

        $timeout = (int) config('santacruz.ftp.timeout', 30);
        $user = (string) config('santacruz.ftp.username');
        $pass = (string) config('santacruz.ftp.password');
        if ($user === '' || $pass === '') {
            throw new RuntimeException(
                'FTP Santa Cruz: usuario o contraseña vacíos en la configuración. Si editaste el .env en el servidor, ejecutá `php artisan config:clear` o volvé a generar la caché con `php artisan config:cache`.'
            );
        }

        curl_setopt($ch, \CURLOPT_USERNAME, $user);
        curl_setopt($ch, \CURLOPT_PASSWORD, $pass);
        curl_setopt($ch, \CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, \CURLOPT_CONNECTTIMEOUT, $timeout);
        curl_setopt($ch, \CURLOPT_TIMEOUT, $timeout);

        if (config('santacruz.ftp.passive', true)) {
            curl_setopt($ch, \CURLOPT_FTP_USE_EPSV, false);
            if (\defined('CURLOPT_FTP_SKIPPASV_IP')) {
                curl_setopt($ch, \CURLOPT_FTP_SKIPPASV_IP, 1);
            }
        }

        if (\defined('CURLOPT_IPRESOLVE') && \defined('CURL_IPRESOLVE_V4')) {
            curl_setopt($ch, \CURLOPT_IPRESOLVE, \CURL_IPRESOLVE_V4);
        }

        return $ch;
    }

    public function listXmlFiles(): array
    {
        $ch = $this->newCurl($this->urlDirectory());
        curl_setopt($ch, \CURLOPT_DIRLISTONLY, true);
        $body = curl_exec($ch);
        $errno = curl_errno($ch);
        $err = curl_error($ch);
        curl_close($ch);
        if ($body === false || $errno !== 0) {
            throw new RuntimeException('FTP (cURL) listado: '.($err !== '' ? $err : 'respuesta vacía').($errno ? ' (#'.$errno.')' : ''));
        }

        $out = [];
        foreach (preg_split('/\r\n|\r|\n/', trim((string) $body)) as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            $base = basename($line);
            if (str_ends_with(strtolower($base), '.xml')) {
                $out[] = $base;
            }
        }
        sort($out);

        return array_values(array_unique($out));
    }

    public function getFileContents(string $basename): string
    {
        $basename = basename($basename);
        $ch = $this->newCurl($this->urlFile($basename));
        $data = curl_exec($ch);
        $errno = curl_errno($ch);
        $err = curl_error($ch);
        curl_close($ch);
        if ($data === false || $errno !== 0) {
            throw new RuntimeException('FTP (cURL) descarga «'.$basename.'»: '.($err !== '' ? $err : 'fallo desconocido').($errno ? ' (#'.$errno.')' : ''));
        }

        return (string) $data;
    }

    public function moveToProcessed(string $basename): void
    {
        $basename = basename($basename);
        $sub = trim((string) config('santacruz.ftp.processed_subpath', 'procesados'), '/');
        if ($sub === '') {
            $sub = 'procesados';
        }

        $ch = $this->newCurl($this->urlDirectory());
        curl_setopt($ch, \CURLOPT_QUOTE, ['MKD '.$sub]);
        curl_setopt($ch, \CURLOPT_NOBODY, true);
        curl_exec($ch);
        curl_close($ch);

        $ch = $this->newCurl($this->urlDirectory());
        curl_setopt($ch, \CURLOPT_QUOTE, ['RNFR '.$basename, 'RNTO '.$sub.'/'.$basename]);
        curl_setopt($ch, \CURLOPT_NOBODY, true);
        $ok = curl_exec($ch);
        $errno = curl_errno($ch);
        $err = curl_error($ch);
        curl_close($ch);

        if ($ok === false || $errno !== 0) {
            Log::error('SantaCruzFtp (cURL): rename fallido', ['from' => $basename, 'to' => $sub.'/'.$basename, 'errno' => $errno, 'error' => $err]);
            throw new RuntimeException('No se pudo mover el archivo a procesados: '.$basename.' → '.$sub.'/'.$basename);
        }
    }
}
