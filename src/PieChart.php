<?php

namespace Versioon\NovaChartJS;

class PieChart extends BaseChartJsCard
{
    public $width = 'full';

    public function component(): string
    {
        return 'pie-chart';
    }

    public function chartData(?string $value = null): array
    {
        return ['labels' => [], 'datasets' => []];
    }
}
