import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import axios from 'axios';
import { useOfflineSync } from '@/Composables/useOfflineSync';

export const useItemsStore = defineStore('items', () => {
    const items = ref([]);
    const loading = ref(false);
    const error = ref(null);

    // Offline sync integration
    const {
        isOnline,
        addPendingAction,
        getCachedItems,
        cacheItem,
    } = useOfflineSync();

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

    /**
     * Generate a temporary ID for offline items
     */
    const generateTempId = () => `temp-${Date.now()}-${Math.random().toString(36).substr(2, 9)}`;

    const fetchItems = async (listType = null) => {
        loading.value = true;
        error.value = null;

        try {
            if (!isOnline.value) {
                // Offline: load from cache
                const cached = await getCachedItems(listType);
                items.value = cached;
                return;
            }

            const params = listType ? { list_type: listType } : {};
            const response = await axios.get('/api/items', { params });
            const fetchedItems = response.data.data || response.data;
            items.value = fetchedItems;

            // Cache items for offline use
            for (const item of fetchedItems) {
                await cacheItem(item);
            }
        } catch (err) {
            error.value = err.message;
            console.error('Error fetching items:', err);

            // Fallback to cached items on error
            if (!isOnline.value) {
                const cached = await getCachedItems(listType);
                items.value = cached;
            }
        } finally {
            loading.value = false;
        }
    };

    const createItem = async (itemData) => {
        // Offline handling: queue action and optimistically update UI
        if (!isOnline.value) {
            const tempItem = {
                ...itemData,
                id: generateTempId(),
                created_at: new Date().toISOString(),
                _offline: true, // Mark as offline-created
            };

            // Optimistically add to UI
            items.value.unshift(tempItem);

            // Queue for sync
            await addPendingAction({
                type: 'item:create',
                data: itemData,
            });

            // Cache the item
            await cacheItem(tempItem);

            return tempItem;
        }

        try {
            const response = await axios.post('/api/items', itemData);
            const newItem = response.data.data;
            items.value.unshift(newItem);

            // Cache the new item
            await cacheItem(newItem);

            return newItem;
        } catch (err) {
            error.value = err.message;
            throw err;
        }
    };

    const updateItem = async (id, itemData) => {
        const index = items.value.findIndex(item => item.id === id);
        const originalItem = index !== -1 ? { ...items.value[index] } : null;

        // Offline handling
        if (!isOnline.value) {
            // Optimistically update UI
            if (index !== -1) {
                items.value[index] = { ...items.value[index], ...itemData, _offline: true };
                await cacheItem(items.value[index]);
            }

            // Queue for sync (only if not a temp ID)
            if (!String(id).startsWith('temp-')) {
                await addPendingAction({
                    type: 'item:update',
                    data: { id, ...itemData },
                });
            }

            return items.value[index];
        }

        try {
            const response = await axios.put(`/api/items/${id}`, itemData);
            if (index !== -1) {
                items.value[index] = response.data.data;
                await cacheItem(response.data.data);
            }
            return response.data.data;
        } catch (err) {
            // Revert optimistic update on error
            if (originalItem && index !== -1) {
                items.value[index] = originalItem;
            }
            error.value = err.message;
            throw err;
        }
    };

    const deleteItem = async (id) => {
        const index = items.value.findIndex(item => item.id === id);
        const removedItem = index !== -1 ? items.value[index] : null;

        // Optimistically remove from UI
        if (index !== -1) {
            items.value.splice(index, 1);
        }

        // Offline handling
        if (!isOnline.value) {
            // Queue for sync (only if not a temp ID)
            if (!String(id).startsWith('temp-')) {
                await addPendingAction({
                    type: 'item:delete',
                    data: { id },
                });
            }
            return;
        }

        try {
            await axios.delete(`/api/items/${id}`);
        } catch (err) {
            // Revert optimistic removal on error
            if (removedItem) {
                items.value.splice(index, 0, removedItem);
            }
            error.value = err.message;
            throw err;
        }
    };

    const moveItem = async (id, toList) => {
        const index = items.value.findIndex(item => item.id === id);
        const originalItem = index !== -1 ? { ...items.value[index] } : null;

        // Offline handling
        if (!isOnline.value) {
            // Optimistically update UI
            if (index !== -1) {
                if (toList === 'trash') {
                    items.value.splice(index, 1);
                } else {
                    items.value[index] = { ...items.value[index], list_type: toList, _offline: true };
                    await cacheItem(items.value[index]);
                }
            }

            // Queue for sync (only if not a temp ID)
            if (!String(id).startsWith('temp-')) {
                await addPendingAction({
                    type: 'item:move',
                    data: { id, to_list: toList },
                });
            }

            return { data: items.value[index] };
        }

        try {
            const response = await axios.post(`/api/items/${id}/move`, { to_list: toList });

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
                    await cacheItem(response.data.data);
                } else {
                    // Item was deleted (recurring item checked)
                    items.value.splice(index, 1);
                }
            }
            return response.data;
        } catch (err) {
            // Revert optimistic update on error
            if (originalItem && index !== -1) {
                items.value.splice(index, 0, originalItem);
            }
            error.value = err.message;
            throw err;
        }
    };

    const restoreItem = async (id) => {
        // Note: restore typically needs online connectivity
        // as we need the actual item data from server
        try {
            const response = await axios.post(`/api/items/${id}/restore`);
            const restoredItem = response.data.data;
            items.value.push(restoredItem);
            await cacheItem(restoredItem);
            return restoredItem;
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
                await cacheItem(response.data.data);
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
                await cacheItem(response.data.data);
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

        // Offline: search cached items
        if (!isOnline.value) {
            const cached = await getCachedItems();
            return cached.filter(item =>
                item.name.toLowerCase().includes(query.toLowerCase())
            ).slice(0, 5);
        }

        try {
            const response = await axios.get('/api/items/suggest', { params: { q: query } });
            return response.data.data || response.data;
        } catch (err) {
            console.error('Error searching inventory:', err);
            // Fallback to cached search
            const cached = await getCachedItems();
            return cached.filter(item =>
                item.name.toLowerCase().includes(query.toLowerCase())
            ).slice(0, 5);
        }
    };

    /**
     * Replace temporary IDs with real IDs after sync
     * Called after successful sync to update UI
     */
    const replaceTempId = (tempId, realId, updatedItem) => {
        const index = items.value.findIndex(item => item.id === tempId);
        if (index !== -1) {
            items.value[index] = { ...updatedItem, _offline: false };
        }
    };

    return {
        items,
        loading,
        error,
        isOnline,
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
        replaceTempId,
    };
});
