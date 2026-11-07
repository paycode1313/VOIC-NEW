import Alpine from 'alpinejs';
import Chart from 'chart.js/auto';
import examRoom from './exam-room';

window.Alpine = Alpine;
window.Chart = Chart;

Alpine.data('examRoom', examRoom);

Alpine.start();
