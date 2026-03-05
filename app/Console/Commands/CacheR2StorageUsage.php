<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class CacheR2StorageUsage extends Command
{
    protected $signature = 'r2:cache-storage-usage {--ttl=60}';
    protected $description = 'Calcula el espacio usado en el bucket R2 y lo guarda en caché (TTL en minutos).';

    public function handle(): int
    {
        $ttl = (int) $this->option('ttl');
        if ($ttl < 1) {
            $ttl = 60;
        }

        try {
            $disk = Storage::disk('r2');
            $adapter = $disk->getAdapter();
            $client = $adapter->getClient();
            $bucket = $adapter->getBucket();

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

            Cache::put('r2_storage_used_bytes', $totalBytes, now()->addMinutes($ttl));
            $this->info('R2 almacenamiento usado: ' . round($totalBytes / 1024 / 1024 / 1024, 2) . ' GB (cacheado ' . $ttl . ' min).');
            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Error: ' . $e->getMessage());
            Cache::forget('r2_storage_used_bytes');
            return self::FAILURE;
        }
    }
}
