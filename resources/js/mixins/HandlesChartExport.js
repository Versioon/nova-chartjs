/**
 * Mixin for handling chart data export to Excel.
 *
 * This mixin provides the downloadXLSX method that:
 * - Sends chart data to the export endpoint
 * - Triggers automatic file download
 * - Handles loading states and errors
 *
 * Requirements:
 * - Component must have `card` prop with series, title, and options
 * - Component must have `datacollection` data property with labels
 * - Component must have `exportLoading` data property
 */
export default {
  methods: {
    /**
     * Export chart data to Excel file.
     *
     * Sends a POST request to the export endpoint with:
     * - series: Chart series data
     * - labels: X-axis labels
     * - title: Chart title for filename
     *
     * The response is a blob that gets automatically downloaded
     * with filename format: {title}_{timestamp}.xlsx
     */
    downloadXLSX() {
      if (this.exportLoading) return;

      this.exportLoading = true;

      const params = {
        series: this.card.series,
        labels: this.datacollection.labels || this.card.options?.xaxis?.categories || [],
        title: this.card.title || 'chart-export',
      };

      Nova.request()
        .post('/nova-vendor/versioon/nova-chartjs/check-data/export', params, {
          responseType: 'blob',
        })
        .then((response) => {
          const url = window.URL.createObjectURL(new Blob([response.data]));
          const link = document.createElement('a');
          link.href = url;

          const timestamp = new Date().toISOString().replace(/[:.]/g, '-').slice(0, 19);
          const sanitizedTitle = this.card.title
            ? this.card.title.replace(/[^a-zA-Z0-9\-_ ]/g, '').replace(/ /g, '_')
            : 'chart-export';
          const filename = `${sanitizedTitle}_${timestamp}.xlsx`;

          link.setAttribute('download', filename);
          document.body.appendChild(link);
          link.click();
          link.remove();
          window.URL.revokeObjectURL(url);

          this.exportLoading = false;
        })
        .catch((error) => {
          console.error('Export failed:', error);
          this.exportLoading = false;

          if (this.$toasted) {
            this.$toasted.show('Failed to export chart data. Please try again.', { type: 'error' });
          }
        });
    },
  },
};
