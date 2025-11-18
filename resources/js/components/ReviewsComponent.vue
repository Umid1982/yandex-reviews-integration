<template>
    <div class="flex gap-6">
        <!-- Reviews List -->
        <div class="flex-1">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">Яндекс Карты</h2>

            <div v-if="loading" class="text-center py-8">
                <p class="text-gray-600">Загрузка отзывов...</p>
            </div>

            <div v-else-if="error" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                {{ error }}
            </div>

            <div v-else-if="reviews.length === 0" class="text-center py-8">
                <p class="text-gray-600">Отзывы не найдены</p>
            </div>

            <div v-else class="space-y-4">
                <div v-for="review in reviews" :key="review.id"
                     class="bg-white border border-gray-200 rounded-lg p-6 shadow-sm">
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <p class="text-sm text-gray-500">{{ formatDate(review.date) }}</p>
                            <div class="flex items-center mt-1">
                                <svg class="w-4 h-4 text-red-500 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/>
                                </svg>
                                <span class="text-sm text-gray-600">{{ review.branch || 'Филиал 1' }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <p class="font-semibold text-gray-900">{{ review.author || 'Аноним' }}</p>
                        <p v-if="review.phone" class="text-sm text-gray-600">{{ review.phone }}</p>
                    </div>

                    <div class="flex items-center mb-3">
                        <div class="flex">
                            <svg v-for="i in 5" :key="i"
                                 :class="i <= review.rating ? 'text-yellow-400' : 'text-gray-300'"
                                 class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                            </svg>
                        </div>
                    </div>

                    <p class="text-gray-700">{{ review.text }}</p>
                </div>
            </div>
        </div>

        <!-- Rating Box -->
        <div class="w-64">
            <div class="bg-white border border-gray-200 rounded-lg p-6 shadow-sm">
                <div class="text-center">
                    <div class="text-4xl font-bold text-gray-900 mb-2">{{ rating || '0.0' }}</div>
                    <div class="flex justify-center mb-4">
                        <div class="flex">
                            <svg v-for="i in 5" :key="i"
                                 :class="i <= Math.floor(rating) ? 'text-yellow-400' : i <= rating ? 'text-yellow-400 opacity-50' : 'text-gray-300'"
                                 class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                            </svg>
                        </div>
                    </div>
                    <p class="text-gray-600">Всего отзывов: {{ totalReviews.toLocaleString('ru-RU') }}</p>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import axios from 'axios';

export default {
    name: 'ReviewsComponent',
    data() {
        return {
            reviews: [],
            rating: 0,
            totalReviews: 0,
            loading: true,
            error: null,
        };
    },
    mounted() {
        this.fetchReviews();
    },
    methods: {
        async fetchReviews() {
            this.loading = true;
            this.error = null;

            try {
                const response = await axios.get('/api/reviews');

                if (response.data.success) {
                    this.reviews = response.data.data.reviews || [];
                    this.rating = response.data.data.rating || 0;
                    this.totalReviews = response.data.data.total_reviews || 0;
                } else {
                    this.error = response.data.message || 'Ошибка при загрузке отзывов';
                }
            } catch (error) {
                this.error = error.response?.data?.message || 'Не удалось загрузить отзывы';
                console.error('Error fetching reviews:', error);
            } finally {
                this.loading = false;
            }
        },
        formatDate(dateString) {
            if (!dateString) return '';

            const date = new Date(dateString);
            const day = String(date.getDate()).padStart(2, '0');
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const year = date.getFullYear();
            const hours = String(date.getHours()).padStart(2, '0');
            const minutes = String(date.getMinutes()).padStart(2, '0');

            return `${day}.${month}.${year} ${hours}:${minutes}`;
        },
    },
};
</script>

