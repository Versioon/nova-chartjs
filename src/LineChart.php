<?php

namespace Versioon\NovaChartJS;

class LineChart extends BaseChartJsCard
{
    public $width = 'full';

    public function component(): string
    {
        return 'stripe-chart';
    }

    public function chartData(?string $value = null): array
    {
        return ['labels' => [], 'datasets' => []];
    }
}
