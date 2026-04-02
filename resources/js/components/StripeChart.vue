<template>
  <loading-card :loading="loading" class="min-h-40 px-6 py-4">
    <div class="h-6 mb-4 flex items-center">
      <h4 class="mr-3 leading-tight text-sm font-bold">{{ card.title }}</h4>
      <div class="flex items-center gap-2 relative ml-auto flex-shrink-0">
        <SelectControl
          v-if="card.ranges && card.ranges.length > 0"
          :value="selectedRangeKey"
          @update:modelValue="handleRangeSelected"
          :options="card.ranges"
          size="xxs"
          class="shrink-0"
          style="width: 10rem"
          :aria-label="__('Select Range')"
        />

        <button
          size="xs"
          class="cursor-pointer hover:opacity-75"
          @click="downloadXLSX()"
          v-show="card.exportable"
          :disabled="exportLoading"
          :title="exportLoading ? 'Exporting...' : 'Download XLSX'"
        >
          <icon-download v-if="!exportLoading" />
          <span v-else>...</span>
        </button>
      </div>
    </div>

    <line-chart
      v-if="!loading"
      :chart-data="datacollection"
      :options="chartOptions"
      :style="{ height: card.height && !['fixed', 'dynamic'].includes(card.height) ? card.height : 'auto' }"
    />
  </loading-card>
</template>

<script>
import { MetricBehavior } from 'laravel-nova';
import LineChart from '../stripe-chart.vue';
import IconRefresh from './Icons/IconRefresh';
import IconExternalLink from './Icons/IconExternalLink';
import IconDownload from './Icons/IconDownload';
import HandlesChartExport from '../mixins/HandlesChartExport';

export default {
  components: {
    IconExternalLink,
    IconRefresh,
    IconDownload,
    LineChart,
  },

  mixins: [MetricBehavior, HandlesChartExport],

  props: {
    card: {
      type: Object,
      required: true,
    },
  },

  data() {
    return {
      datacollection: {},
      chartOptions: {},
      loading: true,
      selectedRangeKey: null,
      exportLoading: false,
    };
  },

  created() {
    if (this.card.ranges && this.card.ranges.length > 0) {
      this.selectedRangeKey = this.card.selectedRangeKey || this.card.ranges[0].value;
    }
    this.buildOptions();
    this.fetch();
  },

  computed: {
    metricPayload() {
      return { params: { range: this.selectedRangeKey } };
    },
  },

  methods: {
    reloadPage() {
      window.location.reload();
    },

    handleRangeSelected(key) {
      this.selectedRangeKey = key;
      this.fetch();
    },

    handleFetchCallback() {
      return (response) => {
        const data = response.data.value;
        this.datacollection = {
          labels: data.labels,
          datasets: data.datasets,
        };
        this.loading = false;
      };
    },

    buildOptions() {
      const opts = this.card.options || {};
      const plugins = opts.plugins || {};
      const legend = opts.legend || {
        display: true,
        position: 'left',
        labels: { fontColor: '#7c858e', fontFamily: "'Nunito'" },
      };

      this.chartOptions = {
        ...opts,
        layout: opts.layout || {},
        scales: {
          yAxes: {
            ...(opts.scales?.yAxes || {}),
            ticks: {
              maxTicksLimit: 5,
              ...(opts.scales?.yAxes?.ticks || {}),
              font: { size: 10, ...(opts.scales?.yAxes?.ticks?.font || {}) },
              callback: function (num) {
                if (num >= 1000000000) return (num / 1000000000).toFixed(1).replace(/\.0$/, '') + 'G';
                if (num >= 1000000) return (num / 1000000).toFixed(1).replace(/\.0$/, '') + 'M';
                if (num >= 1000) return (num / 1000).toFixed(1).replace(/\.0$/, '') + 'K';
                return num;
              },
            },
          },
          xAxes: {
            ...(opts.scales?.xAxes || {}),
            ticks: {
              ...(opts.scales?.xAxes?.ticks || {}),
              font: { lineHeight: 0.8, size: 10, ...(opts.scales?.xAxes?.ticks?.font || {}) },
            },
          },
        },
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend, ...plugins },
      };

      this.applyTooltipCallbacks(opts.tooltips);
    },

    applyTooltipCallbacks(tooltips) {
      if (!tooltips) return;
      this.chartOptions.plugins.tooltip = tooltips;
      const fnFields = ['custom', 'itemSort', 'filter'];
      for (const field of fnFields) {
        if (tooltips[field]?.includes?.('function')) {
          eval('this.chartOptions.plugins.tooltip.' + field + ' = ' + tooltips[field]);
        }
      }
      if (tooltips.callbacks) {
        const cbFields = [
          'beforeTitle',
          'title',
          'afterTitle',
          'beforeBody',
          'beforeLabel',
          'label',
          'labelColor',
          'labelTextColor',
          'afterLabel',
          'afterBody',
          'beforeFooter',
          'footer',
          'afterFooter',
        ];
        for (const cb of cbFields) {
          if (tooltips.callbacks[cb]?.includes?.('function')) {
            eval('this.chartOptions.plugins.tooltip.callbacks.' + cb + ' = ' + tooltips.callbacks[cb]);
          }
        }
      }
    },
  },
};
</script>
