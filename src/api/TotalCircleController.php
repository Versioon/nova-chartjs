<?php

namespace Versioon\NovaChartJS\Api;

use Illuminate\Support\Carbon;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cache;
use Laravel\Nova\Http\Requests\NovaRequest;
use Illuminate\Foundation\Validation\ValidatesRequests;

class TotalCircleController extends Controller
{
    use ValidatesRequests;
    /**
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function handle(NovaRequest $request)
    {
        if ($request->input('model')) {
            $request->merge(['model' => urldecode($request->input('model'))]);
        }

        $options = is_string($request->options) ? json_decode($request->options, true) : $request->input('options', []);
        $join = is_string($request->join) ? json_decode($request->join, true) : $request->input('join', []);

        $advanceFilterSelected = $options['advanceFilterSelected'] ?? false;
        $dataForLast = $options['latestData'] ?? 3;
        $calculation = $options['sum'] ?? 1;
        $request->validate(['model' => ['bail', 'required', 'min:1', 'string']]);
        $model = $request->input('model');
        $modelInstance = new $model;
        $tableName = $modelInstance->getConnection()->getTablePrefix() . $modelInstance->getTable();
        $xAxisColumn = $request->input('col_xaxis') ?? $tableName . '.created_at';
        $cacheKey = hash('md4', $model . (int) (bool) $request->input('expires'));
        $dataSet = Cache::get($cacheKey);
        if (!$dataSet) {
            $labelList = [];
            $xAxis = [];
            $yAxis = [];
            $seriesSql = '';
            $defaultColor = ['#ffcc5c', '#91e8e1', '#ff6f69', '#88d8b0', '#b088d8', '#d8b088', '#88b0d8', '#6f69ff', '#7cb5ec', '#434348', '#90ed7d', '#8085e9', '#f7a35c', '#f15c80', '#e4d354', '#2b908f', '#f45b5b', '#91e8e1', '#E27D60', '#85DCB', '#E8A87C', '#C38D9E', '#41B3A3', '#67c4a7', '#992667', '#ff4040', '#ff7373', '#d2d2d2'];
            if (isset($request->series)) {
                foreach ($request->series as $seriesKey => $serieslist) {
                    $seriesData = (object) $serieslist;
                    $filter = (object) $seriesData->filter;
                    $labelList[$seriesKey] = $seriesData->label;
                    if (empty($filter->value) && isset($filter->operator) && ($filter->operator == 'IS NULL' || $filter->operator == 'IS NOT NULL')) {
                        $seriesSql .= ', SUM(CASE WHEN ' . $filter->key . ' ' . $filter->operator . ' then ' . $calculation . ' else 0 end) as "' . $labelList[$seriesKey] . '"';
                    } elseif (empty($filter->value)) {
                        $seriesSql .= ', SUM(CASE WHEN ';
                        $countFilter = count((array) $filter);
                        foreach ($filter as $keyFilter => $listFilter) {
                            $listFilter = (object) $listFilter;
                            $seriesSql .= ' ' . $listFilter->key . ' ' . ($listFilter->operator ?? '=') . " '" . $listFilter->value . "' ";
                            $seriesSql .= $countFilter - 1 != $keyFilter ? ' AND ' : '';
                        }
                        $seriesSql .= 'then ' . $calculation . ' else 0 end) as "' . $labelList[$seriesKey] . '"';
                    } else {
                        $seriesSql .= ', SUM(CASE WHEN ' . $filter->key . ' ' . ($filter->operator ?? '=') . " '" . $filter->value . "' then " . $calculation . ' else 0 end) as "' . $labelList[$seriesKey] . '"';
                    }
                }
            }
            if (count($join)) {
                $joinInformation = $join;
                $query = $model::selectRaw('SUM(' . $calculation . ') counted' . $seriesSql)
                    ->join($joinInformation['joinTable'], $joinInformation['joinColumnFirst'], $joinInformation['joinEqual'], $joinInformation['joinColumnSecond']);
            } else {
                $query = $model::selectRaw('SUM(' . $calculation . ') counted' . $seriesSql);
            }

            if (is_numeric($advanceFilterSelected)) {
                $query->where($xAxisColumn, '>=', Carbon::now()->subDays($advanceFilterSelected));
            } elseif ($advanceFilterSelected == 'YTD') {
                $query->whereBetween($xAxisColumn, [Carbon::now()->firstOfYear()->startOfDay(), Carbon::now()]);
            } elseif ($advanceFilterSelected == 'QTD') {
                $query->whereBetween($xAxisColumn, [Carbon::now()->firstOfQuarter()->startOfDay(), Carbon::now()]);
            } elseif ($advanceFilterSelected == 'MTD') {
                $query->whereBetween($xAxisColumn, [Carbon::now()->firstOfMonth()->startOfDay(), Carbon::now()]);
            } elseif ($dataForLast != '*') {
                $query->where($xAxisColumn, '>=', Carbon::now()->firstOfMonth()->subMonth($dataForLast - 1));
            }

            if ($options['queryFilter'] ?? false) {
                $queryFilter = $options['queryFilter'];
                foreach ($queryFilter as $qF) {
                    if (isset($qF['value']) && !is_array($qF['value'])) {
                        if (isset($qF['operator'])) {
                            $query->where($qF['key'], $qF['operator'], $qF['value']);
                        } else {
                            $query->where($qF['key'], $qF['value']);
                        }
                    } else {
                        if ($qF['operator'] == 'IS NULL') {
                            $query->whereNull($qF['key']);
                        } elseif ($qF['operator'] == 'IS NOT NULL') {
                            $query->whereNotNull($qF['key']);
                        } elseif ($qF['operator'] == 'IN') {
                            $query->whereIn($qF['key'], $qF['value']);
                        } elseif ($qF['operator'] == 'NOT IN') {
                            $query->whereIn($qF['key'], $qF['value']);
                        } elseif ($qF['operator'] == 'BETWEEN') {
                            $query->whereBetween($qF['key'], $qF['value']);
                        } elseif ($qF['operator'] == 'NOT BETWEEN') {
                            $query->whereNotBetween($qF['key'], $qF['value']);
                        }
                    }
                }
            }
            $dataSet = $query->get();
            $xAxis = collect($labelList);
            if (isset($request->series)) {
                $countKey = 0;
                foreach ($request->series as $sKey => $sData) {
                    $dataSeries = (object) $sData;
                    foreach ($dataSet as $dataDetail) {
                        $yAxis[0]['backgroundColor'][$sKey] = $dataSeries->backgroundColor ?? $defaultColor[$sKey];
                        $yAxis[0]['borderColor'][$sKey] = $dataSeries->borderColor ?? '#FFF';
                        $yAxis[0]['data'][$sKey] = $dataDetail[$dataSeries->label];
                    }
                    $countKey++;
                }
            } else {
                throw new ThrowError('You need to have at least 1 series parameters for this type of chart. <br/>Check documentation: https://github.com/coroo/nova-chartjs');
            }
            if ($request->input('expires')) {
                Cache::put($cacheKey, $dataSet, Carbon::parse($request->input('expires')));
            }
        }

        return response()->json(
            ['dataset' => [
                'xAxis' => $xAxis,
                'yAxis' => $yAxis,
            ],
            ]);
    }
}
