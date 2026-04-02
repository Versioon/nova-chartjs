const mix = require('laravel-mix');

require('./nova.mix');

mix
  .setPublicPath('dist')
  .js('resources/js/nova-chartjs.js', 'js')
  .css('resources/css/nova-chartjs.css', 'css')
  .vue({ version: 3 })
  .nova('versioon/nova-chart-js');
