import { defineStore } from 'pinia';
import { ref } from 'vue';
import axios from 'axios';

export const useActivitiesStore = defineStore('activities', () => {
    const activities = ref([]);
    const loading = ref(false);
    const error = ref(null);
    const hasMore = ref(true);
    const currentPage = ref(1);

    const fetchActivities = async (page = 1) => {
        loading.value = true;
        error.value = null;

        try {
            const response = await axios.get('/api/activities', {
                params: { page, per_page: 50 }
            });

            if (page === 1) {
                activities.value = response.data.data;
            } else {
                activities.value.push(...response.data.data);
            }

            currentPage.value = page;
            hasMore.value = response.data.meta?.next_page_url !== null;
        } catch (err) {
            error.value = err.message;
            console.error('Error fetching activities:', err);
        } finally {
            loading.value = false;
        }
    };

    const fetchUnread = async () => {
        try {
            const response = await axios.get('/api/activities/unread');
            return response.data.data || response.data;
        } catch (err) {
            console.error('Error fetching unread activities:', err);
            return [];
        }
    };

    const markAsRead = async () => {
        try {
            await axios.post('/api/activities/mark-read');
        } catch (err) {
            console.error('Error marking activities as read:', err);
        }
    };

    const loadMore = async () => {
        if (hasMore.value && !loading.value) {
            await fetchActivities(currentPage.value + 1);
        }
    };

    return {
        activities,
        loading,
        error,
        hasMore,
        currentPage,
        fetchActivities,
        fetchUnread,
        markAsRead,
        loadMore,
    };
});
