<?php

namespace Versioon\NovaChartJS;

use Laravel\Nova\Card;

abstract class ExportableCard extends Card
{
    /**
     * Enable XLSX export for this card.
     *
     * @return $this
     */
    public function exportable(): self
    {
        return $this->withMeta(['exportable' => true]);
    }

    /**
     * Get the export filename.
     */
    public function getExportFilename(): string
    {
        $title = $this->meta['title'] ?? 'chart-export';
        $timestamp = date('Y-m-d_H-i-s');

        return "{$title}_{$timestamp}.xlsx";
    }
}
