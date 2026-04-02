<template>
  <loading-card :loading="loading" class="min-h-40 px-6 py-4">
    <div class="h-6 mb-4 flex items-center pb-0">
      <h4 class="mr-3 leading-tight text-sm font-bold">{{ card.title || 'Chart JS Integration' }}</h4>
      <div class="flex relative ml-auto flex-shrink-0">
        <SelectControl
          v-if="card.ranges && card.ranges.length > 0"
          :value="selectedRangeKey"
          @update:modelValue="handleRangeSelected"
          :options="card.ranges"
          size="xxs"
          class="ml-2 w-[8rem] shrink-0"
          :aria-label="__('Select Range')"
        />
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
import LineChart from '../scatter-chart.vue';
import IconRefresh from './Icons/IconRefresh';
import IconExternalLink from './Icons/IconExternalLink';

export default {
  components: {
    IconExternalLink,
    IconRefresh,
    LineChart,
  },

  mixins: [MetricBehavior],

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
          ...(opts.scales || {}),
          xAxes: {
            type: 'linear',
            position: 'bottom',
            stacked: true,
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

      if (opts.tooltips) {
        this.chartOptions.plugins.tooltip = opts.tooltips;
      }
    },
  },
};
</script>
