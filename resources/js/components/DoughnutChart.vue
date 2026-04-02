<template>
  <loading-card :loading="loading" class="min-h-40 px-6 py-4">
    <div class="h-6 mb-4 flex items-center">
      <h4 class="mr-3 leading-tight text-sm font-bold">{{ card.title || 'Chart JS Integration' }}</h4>
      <div class="flex relative ml-auto flex-shrink-0">
        <default-button size="xs" class="mr-2" @click="fetch()" v-show="card.options && card.options.btnRefresh">
          <icon-refresh />
        </default-button>
        <default-button size="xs" class="mr-2" @click="reloadPage()" v-show="card.options && card.options.btnReload">
          <icon-refresh />
        </default-button>
        <default-button
          size="xs"
          class="mr-2"
          component="a"
          :href="card.options && card.options.extLink"
          :target="card.options && card.options.extLinkIn ? card.options.extLinkIn : '_self'"
          v-show="card.options && card.options.extLink"
        >
          <icon-external-link />
        </default-button>
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
import LineChart from '../doughnut-chart.vue';
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
        this.setupPercentageTooltip(data.datasets);
        this.setupSweetAlert(data.datasets);
        this.loading = false;
      };
    },

    buildOptions() {
      const opts    = this.card.options || {};
      const plugins = opts.plugins || {};
      const legend  = opts.legend || {
        display: true,
        position: 'right',
        labels: { fontColor: '#7c858e', fontFamily: "'Nunito'" },
      };

      this.chartOptions = {
        ...opts,
        layout: opts.layout || {},
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
          'beforeTitle','title','afterTitle','beforeBody','beforeLabel','label',
          'labelColor','labelTextColor','afterLabel','afterBody','beforeFooter',
          'footer','afterFooter',
        ];
        for (const cb of cbFields) {
          if (tooltips.callbacks[cb]?.includes?.('function')) {
            eval('this.chartOptions.plugins.tooltip.callbacks.' + cb + ' = ' + tooltips.callbacks[cb]);
          }
        }
      }
    },

    setupPercentageTooltip(datasets) {
      if (!this.card.options?.showPercentage) return;
      const dataArr = datasets[0]?.data ?? [];
      const sum     = dataArr.reduce((a, b) => parseInt(a) + parseInt(b), 0);
      this.chartOptions.plugins = this.chartOptions.plugins || {};
      this.chartOptions.plugins.tooltip = {
        callbacks: {
          ...(this.chartOptions.plugins?.tooltip?.callbacks || {}),
          label: (context) =>
            context.label + ': ' + context.raw + ' (' + ((context.raw * 100) / sum).toFixed(2) + '%)',
        },
      };
    },

    setupSweetAlert(datasets) {
      const sweetAlertWithLink = this.card.options?.sweetAlert2;
      if (!sweetAlertWithLink) return;

      this.chartOptions.onClick = function (_event, element) {
        if (element.length > 0) {
          const label      = element[0].label;
          const value      = this.data.datasets[element[0].datasetIndex].data[element[0].index];
          const dataArr    = datasets[0]?.data ?? [];
          const sum        = dataArr.reduce((a, b) => parseInt(a) + parseInt(b), 0);
          const percentage = (value / sum) * 100;
          const toLink     = sweetAlertWithLink.linkTo ?? 'https://coroo.github.io/nova-chartjs/';
          const { linkTo, ...sweetAlert } = sweetAlertWithLink;
          const Swal = require('sweetalert2');
          Swal.fire({
            title: sweetAlert.title ?? '<strong>' + label + '</strong>',
            icon: sweetAlert.icon ?? 'info',
            html: sweetAlert.html ?? ('Percentage: <b>' + percentage.toFixed(2) + '%</b><br/><b>' + value + '</b> data from <b>' + sum + '</b><br/>'),
            showCloseButton: sweetAlert.showCloseButton ?? true,
            showCancelButton: sweetAlert.showCancelButton ?? true,
            focusConfirm: sweetAlert.focusConfirm ?? false,
            confirmButtonText: sweetAlert.confirmButtonText ?? '<i class="fas fa-external-link-alt"></i> See Detail',
            ...sweetAlert,
          }).then((result) => { if (result.value) window.location = toLink; });
        }
      };
    },
  },
};
</script>
