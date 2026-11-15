import Alpine from 'alpinejs';
import Chart from 'chart.js/auto';
import examRoom from './exam-room';
import { speechText } from './pronunciation';

window.Alpine = Alpine;
window.Chart = Chart;
window.voicSpeechText = speechText;

Alpine.data('examRoom', examRoom);

Alpine.start();
