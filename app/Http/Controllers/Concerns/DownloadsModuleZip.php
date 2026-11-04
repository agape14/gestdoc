<?php

namespace App\Http\Controllers\Concerns;

use App\Services\StructuredZipService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

trait DownloadsModuleZip
{
    abstract protected function zipModuleQuery(Request $request): Builder;

    protected function zipDownloadBasename(): string
    {
        return 'documentos';
    }

    protected function zipEagerLoads(): array
    {
        return ['documentos'];
    }

    public function downloadZip(Request $request)
    {
        $query = $this->zipModuleQuery($request);
        $eager = array_values(array_filter($this->zipEagerLoads()));
        if ($eager) {
            $query->with($eager);
        }

        if ($request->boolean('all')) {
            $records = $query->get();
        } else {
            $ids = $request->input('ids', []);
            if (! is_array($ids) || $ids === []) {
                return redirect()->back()->with('error', 'Seleccione al menos un registro.');
            }
            $records = $query->whereIn('id', $ids)->get();
        }

        $types = $request->input('types', []);
        $types = is_array($types) ? array_values(array_filter(array_map('strval', $types))) : [];

        $entries = [];
        foreach ($records as $record) {
            $entries = array_merge($entries, StructuredZipService::entriesFromRecord($record, $types));
        }

        if ($entries === []) {
            return redirect()->back()->with('error', 'No hay archivos para descargar con los tipos seleccionados.');
        }

        $name = $this->zipDownloadBasename() . '-' . now()->format('Y-m-d-His') . '.zip';

        return StructuredZipService::streamDownload($entries, $name);
    }
}
