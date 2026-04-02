<?php

namespace Versioon\NovaChartJS;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;
use Laravel\Nova\Metrics\RangedMetric;
use Laravel\Nova\Http\Requests\NovaRequest;

abstract class BaseChartJsCard extends RangedMetric
{
    public $cacheForMinutes = 15;
    public $width = 'full';

    /**
     * Called by Nova's metric API on each request.
     */
    public function calculate(NovaRequest $request): array
    {
        $value = $request->range ?? $this->selectedRangeKey ?? array_key_first($this->ranges());
        $cacheKey = 'nova_chartjs_' . $this->uriKey() . '_' . $value;

        // No caching in local environment
        if (app()->isLocal()) return $this->chartData($value ? (string) $value : null);

        return Cache::remember($cacheKey, now()->addMinutes($this->cacheForMinutes), function () use ($value) {
            return $this->chartData($value ? (string) $value : null);
        });
    }

    /**
     * Override this method to return chart data for the given option value.
     * $value is the selected key from withOptions(), or null if no options are defined.
     *
     * Must return: ['labels' => [...], 'datasets' => [...]]
     */
    abstract public function chartData(?string $value = null): array;

    /**
     * Store key-value options for a <select> rendered in the card header.
     * The selected value is passed as $value to chartData() on each request.
     *
     * Example: ->withRangeOptions(['30' => '30 Days', 'YTD' => 'Year to Date'])
     */
    public function withRangeOptions(array $options): static
    {
        $this->meta['chart_options_select'] = $options;

        return $this;
    }

    /**
     * Returns the options as Nova ranges format.
     */
    public function ranges(): array
    {
        return $this->meta['chart_options_select'] ?? [];
    }

    /**
     * Returns the URI key for this metric's API endpoint.
     */
    public function uriKey(): string
    {
        return $this->meta['_uriKey'] ?? Str::slug(class_basename(static::class));
    }

    /**
     * Set a custom URI key for this card's metric endpoint.
     */
    public function setUriKey(string $key): static
    {
        $this->meta['_uriKey'] = $key;

        return $this;
    }

    // -------------------------------------------------------------------------
    // Fluent configuration methods
    // -------------------------------------------------------------------------

    public function options(array $options): static
    {
        return $this->withMeta(['options' => (object) $options]);
    }

    public function title(string $title): static
    {
        return $this->withMeta(['title' => $title]);
    }

    public function exportable(): static
    {
        return $this->withMeta(['exportable' => true]);
    }

    public function getExportFilename(): string
    {
        $title = $this->meta['title'] ?? 'chart-export';
        $timestamp = date('Y-m-d_H-i-s');

        return "{$title}_{$timestamp}.xlsx";
    }

    public function jsonSerialize(): array
    {
        return array_merge([
            'title' => $this->meta['title'] ?? '',
            'options' => $this->meta['options'] ?? (object) [],
            'exportable' => $this->meta['exportable'] ?? false,
            'height' => $this->meta['height'] ?? null,
        ], parent::jsonSerialize());
    }
}
