import { ref, computed, onMounted } from 'vue';
import Dexie from 'dexie';
import axios from 'axios';

// IndexedDB for offline storage
const db = new Dexie('ChnubberShopDB');
db.version(1).stores({
    pendingActions: '++id, timestamp, type',
    items: 'id, list_type, name',
    categories: 'id, name',
});

export function useOfflineSync() {
    const isOnline = ref(navigator.onLine);
    const isSyncing = ref(false);
    const syncError = ref(null);
    const pendingCount = ref(0);

    const updateOnlineStatus = () => {
        isOnline.value = navigator.onLine;
    };

    const addPendingAction = async (action) => {
        await db.pendingActions.add({
            ...action,
            timestamp: new Date().toISOString(),
        });
        await updatePendingCount();
    };

    const getPendingActions = async () => {
        return await db.pendingActions.toArray();
    };

    const clearPendingActions = async () => {
        await db.pendingActions.clear();
        await updatePendingCount();
    };

    const updatePendingCount = async () => {
        pendingCount.value = await db.pendingActions.count();
    };

    const syncPendingActions = async () => {
        if (!isOnline.value || isSyncing.value) {
            return { success: false, message: 'Offline or already syncing' };
        }

        isSyncing.value = true;
        syncError.value = null;

        try {
            const actions = await getPendingActions();

            if (actions.length === 0) {
                isSyncing.value = false;
                return { success: true, message: 'No pending actions' };
            }

            const response = await axios.post('/api/sync', { actions });

            const { synced_count, conflict_count, error_count } = response.data;

            // Clear successfully synced actions
            if (synced_count > 0) {
                await clearPendingActions();
            }

            isSyncing.value = false;

            return {
                success: true,
                synced: synced_count,
                conflicts: conflict_count,
                errors: error_count,
                message: `${synced_count} Änderungen synchronisiert`,
            };
        } catch (error) {
            console.error('Sync failed:', error);
            syncError.value = error.message;
            isSyncing.value = false;

            return {
                success: false,
                message: 'Synchronisation fehlgeschlagen',
                error: error.message,
            };
        }
    };

    const cacheItem = async (item) => {
        await db.items.put(item);
    };

    const getCachedItems = async (listType = null) => {
        if (listType) {
            return await db.items.where('list_type').equals(listType).toArray();
        }
        return await db.items.toArray();
    };

    const cacheCategory = async (category) => {
        await db.categories.put(category);
    };

    const getCachedCategories = async () => {
        return await db.categories.toArray();
    };

    onMounted(() => {
        window.addEventListener('online', () => {
            updateOnlineStatus();
            // Auto-sync when coming back online
            setTimeout(() => syncPendingActions(), 1000);
        });

        window.addEventListener('offline', updateOnlineStatus);

        updatePendingCount();
    });

    return {
        isOnline,
        isSyncing,
        syncError,
        pendingCount,
        addPendingAction,
        syncPendingActions,
        cacheItem,
        getCachedItems,
        cacheCategory,
        getCachedCategories,
        db,
    };
}
