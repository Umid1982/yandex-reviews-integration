import './bootstrap';
import { createApp } from 'vue';
import ReviewsComponent from './components/ReviewsComponent.vue';

const app = createApp({});
app.component('reviews-component', ReviewsComponent);

app.mount('#reviews-app');
