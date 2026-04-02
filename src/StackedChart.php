<?php

namespace Versioon\NovaChartJS;

use Illuminate\Support\Str;

class StackedChart extends BaseChartJsCard
{
    public $width = 'full';

    public function component(): string
    {
        return 'stacked-chart';
    }

    public function title(string $title): static
    {
        if (!isset($this->meta['_uriKey'])) {
            $this->setUriKey(Str::slug($title));
        }

        return $this->withMeta(['title' => $title]);
    }

    public function chartData(?string $value = null): array
    {
        return ['labels' => [], 'datasets' => []];
    }
}
