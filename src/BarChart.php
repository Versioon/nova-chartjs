<?php

namespace Versioon\NovaChartJS;

class BarChart extends BaseChartJsCard
{
    public $width = 'full';

    public function component(): string
    {
        return 'bar-chart';
    }

    public function chartData(?string $value = null): array
    {
        return ['labels' => [], 'datasets' => []];
    }
}
