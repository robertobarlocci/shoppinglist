import { defineStore } from 'pinia';
import { ref } from 'vue';
import axios from 'axios';

export const useCategoriesStore = defineStore('categories', () => {
    const categories = ref([]);
    const loading = ref(false);
    const error = ref(null);

    const fetchCategories = async () => {
        loading.value = true;
        error.value = null;

        try {
            const response = await axios.get('/api/categories');
            categories.value = response.data.data || response.data;
        } catch (err) {
            error.value = err.message;
            console.error('Error fetching categories:', err);
        } finally {
            loading.value = false;
        }
    };

    const createCategory = async (categoryData) => {
        try {
            const response = await axios.post('/api/categories', categoryData);
            categories.value.push(response.data.data);
            return response.data.data;
        } catch (err) {
            error.value = err.message;
            throw err;
        }
    };

    const updateCategory = async (id, categoryData) => {
        try {
            const response = await axios.put(`/api/categories/${id}`, categoryData);
            const index = categories.value.findIndex(cat => cat.id === id);
            if (index !== -1) {
                categories.value[index] = response.data.data;
            }
            return response.data.data;
        } catch (err) {
            error.value = err.message;
            throw err;
        }
    };

    const deleteCategory = async (id) => {
        try {
            await axios.delete(`/api/categories/${id}`);
            const index = categories.value.findIndex(cat => cat.id === id);
            if (index !== -1) {
                categories.value.splice(index, 1);
            }
        } catch (err) {
            error.value = err.message;
            throw err;
        }
    };

    return {
        categories,
        loading,
        error,
        fetchCategories,
        createCategory,
        updateCategory,
        deleteCategory,
    };
});
