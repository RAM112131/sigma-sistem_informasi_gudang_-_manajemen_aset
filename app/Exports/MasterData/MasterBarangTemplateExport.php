<?php

declare(strict_types=1);

namespace App\Exports\MasterData;

use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

final class MasterBarangTemplateExport implements Export, WithMultipleSheets
{
    /** @return list<Export> */
    public function sheets(): array
    {
        return [new MasterBarangTemplateDataSheet];
    }
}
