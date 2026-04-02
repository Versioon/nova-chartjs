<?php

namespace Versioon\NovaChartJS;

class DoughnutChart extends BaseChartJsCard
{
    public $width = 'full';

    public function component(): string
    {
        return 'doughnut-chart';
    }

    public function chartData(?string $value = null): array
    {
        return ['labels' => [], 'datasets' => []];
    }
}
