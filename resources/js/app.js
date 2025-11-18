import './bootstrap';
import { createApp } from 'vue';
import ReviewsComponent from './components/ReviewsComponent.vue';

const app = createApp({});

// регистрируем в kebab-case
app.component('reviews-component', ReviewsComponent);

const reviewsElement = document.getElementById('reviews-app');
if (reviewsElement) {
    app.mount('#reviews-app');
}
