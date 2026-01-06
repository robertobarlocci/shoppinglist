import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import axios from 'axios';

export const useLunchboxStore = defineStore('lunchbox', () => {
    const lunchboxItems = ref([]);
    const loading = ref(false);
    const error = ref(null);
    const currentWeekStart = ref(null);

    // Get lunchbox items grouped by date and user
    const groupedByDate = computed(() => {
        const grouped = {};
        lunchboxItems.value.forEach(item => {
            if (!grouped[item.date]) {
                grouped[item.date] = [];
            }
            grouped[item.date].push(item);
        });
        return grouped;
    });

    // Get lunchbox items grouped by user (for parents viewing multiple kids)
    const groupedByUser = computed(() => {
        const grouped = {};
        lunchboxItems.value.forEach(item => {
            if (!grouped[item.user_id]) {
                grouped[item.user_id] = {
                    userId: item.user_id,
                    userName: item.user_name,
                    items: []
                };
            }
            grouped[item.user_id].items.push(item);
        });
        return grouped;
    });

    // Get all lunchbox items for a specific date
    const getItemsForDate = (date) => {
        return lunchboxItems.value.filter(item => item.date === date);
    };

    // Get lunchbox items for a specific date and user
    const getItemsForDateAndUser = (date, userId) => {
        return lunchboxItems.value.filter(
            item => item.date === date && item.user_id === userId
        );
    };

    // Fetch lunchbox items for a specific week
    const fetchLunchboxItems = async (startDate = null) => {
        loading.value = true;
        error.value = null;

        try {
            const params = startDate ? { start_date: startDate } : {};
            const response = await axios.get('/api/lunchbox', { params });
            lunchboxItems.value = response.data.data || response.data;

            if (startDate) {
                currentWeekStart.value = startDate;
            }
        } catch (err) {
            error.value = err.message;
            console.error('Error fetching lunchbox items:', err);
        } finally {
            loading.value = false;
        }
    };

    // Create a new lunchbox item
    const createLunchboxItem = async (itemData) => {
        try {
            const response = await axios.post('/api/lunchbox', itemData);
            const newItem = response.data.data;

            // Add to local state
            lunchboxItems.value.push(newItem);

            return newItem;
        } catch (err) {
            error.value = err.message;
            throw err;
        }
    };

    // Delete a lunchbox item
    const deleteLunchboxItem = async (id) => {
        try {
            await axios.delete(`/api/lunchbox/${id}`);
            const index = lunchboxItems.value.findIndex(item => item.id === id);
            if (index !== -1) {
                lunchboxItems.value.splice(index, 1);
            }
        } catch (err) {
            error.value = err.message;
            throw err;
        }
    };

    // Get autocomplete suggestions for lunchbox items
    const getAutocompleteSuggestions = async (query) => {
        if (!query || query.length < 1) {
            return [];
        }

        try {
            const response = await axios.get('/api/lunchbox/autocomplete', {
                params: { query }
            });
            return response.data || [];
        } catch (err) {
            console.error('Error fetching autocomplete suggestions:', err);
            return [];
        }
    };

    return {
        lunchboxItems,
        loading,
        error,
        currentWeekStart,
        groupedByDate,
        groupedByUser,
        getItemsForDate,
        getItemsForDateAndUser,
        fetchLunchboxItems,
        createLunchboxItem,
        deleteLunchboxItem,
        getAutocompleteSuggestions,
    };
});
