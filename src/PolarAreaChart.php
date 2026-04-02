<?php

namespace Versioon\NovaChartJS;

class PolarAreaChart extends BaseChartJsCard
{
    public $width = 'full';

    public function component(): string
    {
        return 'polar-area-chart';
    }

    public function chartData(?string $value = null): array
    {
        return ['labels' => [], 'datasets' => []];
    }
}
