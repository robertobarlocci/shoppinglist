import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import axios from 'axios';

export const useSuggestionsStore = defineStore('suggestions', () => {
    const suggestions = ref([]);
    const loading = ref(false);
    const error = ref(null);

    // Group suggestions by date and meal type
    const groupedSuggestions = computed(() => {
        const grouped = {};
        suggestions.value.forEach(suggestion => {
            const key = `${suggestion.date}_${suggestion.meal_type}`;
            if (!grouped[key]) {
                grouped[key] = [];
            }
            grouped[key].push(suggestion);
        });
        return grouped;
    });

    // Get suggestions for specific date and meal type
    const getSuggestionsFor = (date, mealType) => {
        const key = `${date}_${mealType}`;
        return groupedSuggestions.value[key] || [];
    };

    // Fetch suggestions for a specific week
    const fetchSuggestions = async (startDate = null) => {
        loading.value = true;
        error.value = null;

        try {
            const params = startDate ? { start_date: startDate } : {};
            const response = await axios.get('/api/meal-suggestions', { params });
            suggestions.value = response.data.data || response.data;
        } catch (err) {
            error.value = err.message;
            console.error('Error fetching suggestions:', err);
        } finally {
            loading.value = false;
        }
    };

    // Create a new suggestion (kids only)
    const createSuggestion = async (suggestionData) => {
        try {
            const response = await axios.post('/api/meal-suggestions', suggestionData);
            suggestions.value.push(response.data.data || response.data);
            return response.data.data || response.data;
        } catch (err) {
            error.value = err.message;
            throw err;
        }
    };

    // Delete a suggestion
    const deleteSuggestion = async (id) => {
        try {
            await axios.delete(`/api/meal-suggestions/${id}`);
            const index = suggestions.value.findIndex(s => s.id === id);
            if (index !== -1) {
                suggestions.value.splice(index, 1);
            }
        } catch (err) {
            error.value = err.message;
            throw err;
        }
    };

    // Approve a suggestion (parents only)
    const approveSuggestion = async (id) => {
        try {
            const response = await axios.post(`/api/meal-suggestions/${id}/approve`);

            // Update the suggestion in the store
            const index = suggestions.value.findIndex(s => s.id === id);
            if (index !== -1) {
                suggestions.value[index] = response.data.suggestion.data || response.data.suggestion;
            }

            return response.data;
        } catch (err) {
            error.value = err.message;
            throw err;
        }
    };

    // Reject a suggestion (parents only)
    const rejectSuggestion = async (id) => {
        try {
            const response = await axios.post(`/api/meal-suggestions/${id}/reject`);

            // Update the suggestion in the store
            const index = suggestions.value.findIndex(s => s.id === id);
            if (index !== -1) {
                suggestions.value[index] = response.data.suggestion.data || response.data.suggestion;
            }

            return response.data;
        } catch (err) {
            error.value = err.message;
            throw err;
        }
    };

    return {
        suggestions,
        loading,
        error,
        groupedSuggestions,
        getSuggestionsFor,
        fetchSuggestions,
        createSuggestion,
        deleteSuggestion,
        approveSuggestion,
        rejectSuggestion,
    };
});
