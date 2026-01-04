import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import axios from 'axios';

export const useItemsStore = defineStore('items', () => {
    const items = ref([]);
    const loading = ref(false);
    const error = ref(null);

    const quickBuyItems = computed(() =>
        items.value.filter(item => item.list_type === 'quick_buy')
    );

    const toBuyItems = computed(() =>
        items.value.filter(item => item.list_type === 'to_buy')
    );

    const inventoryItems = computed(() =>
        items.value.filter(item => item.list_type === 'inventory')
    );

    const trashItems = computed(() =>
        items.value.filter(item => item.list_type === 'trash')
    );

    // Group items by category
    const groupedToBuyItems = computed(() => {
        const grouped = {};
        toBuyItems.value.forEach(item => {
            const categoryName = item.category?.name || 'Sonstiges';
            if (!grouped[categoryName]) {
                grouped[categoryName] = [];
            }
            grouped[categoryName].push(item);
        });
        return grouped;
    });

    const groupedInventoryItems = computed(() => {
        const grouped = {};
        inventoryItems.value.forEach(item => {
            const categoryName = item.category?.name || 'Sonstiges';
            if (!grouped[categoryName]) {
                grouped[categoryName] = [];
            }
            grouped[categoryName].push(item);
        });
        return grouped;
    });

    const fetchItems = async (listType = null) => {
        loading.value = true;
        error.value = null;

        try {
            const params = listType ? { list_type: listType } : {};
            const response = await axios.get('/api/items', { params });
            items.value = response.data.data || response.data;
        } catch (err) {
            error.value = err.message;
            console.error('Error fetching items:', err);
        } finally {
            loading.value = false;
        }
    };

    const createItem = async (itemData) => {
        try {
            const response = await axios.post('/api/items', itemData);
            items.value.unshift(response.data.data);
            return response.data.data;
        } catch (err) {
            error.value = err.message;
            throw err;
        }
    };

    const updateItem = async (id, itemData) => {
        try {
            const response = await axios.put(`/api/items/${id}`, itemData);
            const index = items.value.findIndex(item => item.id === id);
            if (index !== -1) {
                items.value[index] = response.data.data;
            }
            return response.data.data;
        } catch (err) {
            error.value = err.message;
            throw err;
        }
    };

    const deleteItem = async (id) => {
        try {
            await axios.delete(`/api/items/${id}`);
            const index = items.value.findIndex(item => item.id === id);
            if (index !== -1) {
                items.value.splice(index, 1);
            }
        } catch (err) {
            error.value = err.message;
            throw err;
        }
    };

    const moveItem = async (id, toList) => {
        try {
            const response = await axios.post(`/api/items/${id}/move`, { to_list: toList });
            const index = items.value.findIndex(item => item.id === id);

            if (index !== -1) {
                if (response.data.deduplication) {
                    // Item was a duplicate - remove it and update/add the existing item
                    items.value.splice(index, 1);

                    // Update or add the existing item
                    const existingIndex = items.value.findIndex(item => item.id === response.data.data.id);
                    if (existingIndex !== -1) {
                        items.value[existingIndex] = response.data.data;
                    } else {
                        items.value.push(response.data.data);
                    }
                } else if (response.data.data) {
                    items.value[index] = response.data.data;
                } else {
                    // Item was deleted (recurring item checked)
                    items.value.splice(index, 1);
                }
            }
            return response.data;
        } catch (err) {
            error.value = err.message;
            throw err;
        }
    };

    const restoreItem = async (id) => {
        try {
            const response = await axios.post(`/api/items/${id}/restore`);
            items.value.push(response.data.data);
            return response.data.data;
        } catch (err) {
            error.value = err.message;
            throw err;
        }
    };

    const setRecurring = async (id, schedule) => {
        try {
            const response = await axios.post(`/api/items/${id}/recurring`, schedule);
            const index = items.value.findIndex(item => item.id === id);
            if (index !== -1) {
                items.value[index] = response.data.data;
            }
            return response.data.data;
        } catch (err) {
            error.value = err.message;
            throw err;
        }
    };

    const removeRecurring = async (id) => {
        try {
            const response = await axios.delete(`/api/items/${id}/recurring`);
            const index = items.value.findIndex(item => item.id === id);
            if (index !== -1) {
                items.value[index] = response.data.data;
            }
            return response.data.data;
        } catch (err) {
            error.value = err.message;
            throw err;
        }
    };

    const searchInventory = async (query) => {
        if (query.length < 2) {
            return [];
        }

        try {
            const response = await axios.get('/api/items/suggest', { params: { q: query } });
            return response.data.data || response.data;
        } catch (err) {
            console.error('Error searching inventory:', err);
            return [];
        }
    };

    return {
        items,
        loading,
        error,
        quickBuyItems,
        toBuyItems,
        inventoryItems,
        trashItems,
        groupedToBuyItems,
        groupedInventoryItems,
        fetchItems,
        createItem,
        updateItem,
        deleteItem,
        moveItem,
        restoreItem,
        setRecurring,
        removeRecurring,
        searchInventory,
    };
});
