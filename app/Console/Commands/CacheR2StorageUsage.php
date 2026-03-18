<?php

namespace App\Console\Commands;

use Aws\S3\S3Client;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;

class CacheR2StorageUsage extends Command
{
    protected $signature = 'r2:cache-storage-usage {--ttl=1500 : Minutos que vive la caché (por defecto 1500 ≈ 25 h; el scheduler la renueva cada hora)}';
    protected $description = 'Calcula el espacio usado en el bucket R2 y lo guarda en caché. Tras expirar el TTL el dashboard muestra vacío hasta la próxima ejecución (programar: php artisan schedule:run en cron).';

    public function handle(): int
    {
        $ttl = (int) $this->option('ttl');
        if ($ttl < 1) {
            $ttl = 1500;
        }

        $config = Config::get('filesystems.disks.r2', []);
        $bucket = $config['bucket'] ?? null;
        if (empty($bucket)) {
            $this->error('Configuración R2 incompleta: falta AWS_BUCKET en .env');
            Cache::forget('r2_storage_used_bytes');
            Cache::forget('r2_storage_updated_at');
            return self::FAILURE;
        }

        try {
            $client = new S3Client([
                'version' => 'latest',
                'region' => $config['region'] ?? 'auto',
                'endpoint' => $config['endpoint'] ?? null,
                'use_path_style_endpoint' => $config['use_path_style_endpoint'] ?? false,
                'credentials' => [
                    'key' => $config['key'] ?? '',
                    'secret' => $config['secret'] ?? '',
                ],
            ]);

            $totalBytes = 0;
            $continuationToken = null;

            do {
                $params = ['Bucket' => $bucket];
                if ($continuationToken) {
                    $params['ContinuationToken'] = $continuationToken;
                }

                $result = $client->listObjectsV2($params);
                foreach ($result->get('Contents') ?? [] as $object) {
                    $totalBytes += (int) ($object['Size'] ?? 0);
                }
                $continuationToken = $result->get('NextContinuationToken');
            } while ($continuationToken);

            $expiresAt = now()->addMinutes($ttl);
            Cache::put('r2_storage_used_bytes', $totalBytes, $expiresAt);
            Cache::put('r2_storage_updated_at', now()->timestamp, $expiresAt);
            $this->info('R2 almacenamiento usado: ' . round($totalBytes / 1024 / 1024 / 1024, 2) . ' GB (caché válida ' . $ttl . ' min; expira ~' . $expiresAt->format('Y-m-d H:i') . ').');
            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Error: ' . $e->getMessage());
            Cache::forget('r2_storage_used_bytes');
            Cache::forget('r2_storage_updated_at');
            return self::FAILURE;
        }
    }
}
