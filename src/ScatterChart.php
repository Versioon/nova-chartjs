<?php

namespace Versioon\NovaChartJS;

class ScatterChart extends BaseChartJsCard
{
    public $width = 'full';

    public function component(): string
    {
        return 'scatter-chart';
    }

    public function chartData(?string $value = null): array
    {
        return ['labels' => [], 'datasets' => []];
    }
}
