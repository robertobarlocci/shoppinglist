import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import axios from 'axios';

export const useMealPlansStore = defineStore('mealPlans', () => {
    const mealPlans = ref([]);
    const loading = ref(false);
    const error = ref(null);
    const currentWeekStart = ref(null);

    // Get meal plans grouped by date and meal type
    const groupedMealPlans = computed(() => {
        const grouped = {};
        mealPlans.value.forEach(plan => {
            if (!grouped[plan.date]) {
                grouped[plan.date] = {};
            }
            grouped[plan.date][plan.meal_type] = plan;
        });
        return grouped;
    });

    // Get all meal plans for a specific date
    const getMealPlansForDate = (date) => {
        return mealPlans.value.filter(plan => plan.date === date);
    };

    // Get meal plan for specific date and meal type
    const getMealPlan = (date, mealType) => {
        return mealPlans.value.find(
            plan => plan.date === date && plan.meal_type === mealType
        );
    };

    // Fetch meal plans for a specific week
    const fetchMealPlans = async (startDate = null) => {
        loading.value = true;
        error.value = null;

        try {
            const params = startDate ? { start_date: startDate } : {};
            const response = await axios.get('/api/meal-plans', { params });
            mealPlans.value = response.data.data || response.data;

            if (startDate) {
                currentWeekStart.value = startDate;
            }
        } catch (err) {
            error.value = err.message;
            console.error('Error fetching meal plans:', err);
        } finally {
            loading.value = false;
        }
    };

    // Create a new meal plan (or update if one already exists for the same date/meal_type)
    const createMealPlan = async (mealPlanData) => {
        try {
            const response = await axios.post('/api/meal-plans', mealPlanData);
            const newMeal = response.data.data;

            // Check if a meal already exists for this date/meal_type in local state
            const existingIndex = mealPlans.value.findIndex(
                plan => plan.date === newMeal.date && plan.meal_type === newMeal.meal_type
            );

            if (existingIndex !== -1) {
                // Update existing meal
                mealPlans.value[existingIndex] = newMeal;
            } else {
                // Add new meal
                mealPlans.value.push(newMeal);
            }

            return newMeal;
        } catch (err) {
            error.value = err.message;
            throw err;
        }
    };

    // Update a meal plan
    const updateMealPlan = async (id, mealPlanData) => {
        try {
            const response = await axios.put(`/api/meal-plans/${id}`, mealPlanData);
            const index = mealPlans.value.findIndex(plan => plan.id === id);
            if (index !== -1) {
                mealPlans.value[index] = response.data.data;
            }
            return response.data.data;
        } catch (err) {
            error.value = err.message;
            throw err;
        }
    };

    // Delete a meal plan
    const deleteMealPlan = async (id) => {
        try {
            await axios.delete(`/api/meal-plans/${id}`);
            const index = mealPlans.value.findIndex(plan => plan.id === id);
            if (index !== -1) {
                mealPlans.value.splice(index, 1);
            }
        } catch (err) {
            error.value = err.message;
            throw err;
        }
    };

    // Add ingredient to meal plan
    const addIngredient = async (mealPlanId, ingredientData) => {
        try {
            const response = await axios.post(
                `/api/meal-plans/${mealPlanId}/ingredients`,
                ingredientData
            );

            // Update the meal plan in the store
            const planIndex = mealPlans.value.findIndex(plan => plan.id === mealPlanId);
            if (planIndex !== -1) {
                if (!mealPlans.value[planIndex].ingredients) {
                    mealPlans.value[planIndex].ingredients = [];
                }
                mealPlans.value[planIndex].ingredients.push(response.data.ingredient);
            }

            return response.data.ingredient;
        } catch (err) {
            error.value = err.message;
            throw err;
        }
    };

    // Remove ingredient from meal plan
    const removeIngredient = async (mealPlanId, ingredientId) => {
        try {
            await axios.delete(`/api/meal-plans/${mealPlanId}/ingredients/${ingredientId}`);

            // Update the meal plan in the store
            const planIndex = mealPlans.value.findIndex(plan => plan.id === mealPlanId);
            if (planIndex !== -1 && mealPlans.value[planIndex].ingredients) {
                const ingredientIndex = mealPlans.value[planIndex].ingredients.findIndex(
                    ing => ing.id === ingredientId
                );
                if (ingredientIndex !== -1) {
                    mealPlans.value[planIndex].ingredients.splice(ingredientIndex, 1);
                }
            }
        } catch (err) {
            error.value = err.message;
            throw err;
        }
    };

    // Add all ingredients from a meal plan to shopping list
    const addIngredientsToShoppingList = async (mealPlanId) => {
        try {
            const response = await axios.post(
                `/api/meal-plans/${mealPlanId}/add-to-shopping-list`
            );
            return response.data;
        } catch (err) {
            error.value = err.message;
            throw err;
        }
    };

    // Search for meal titles (autocomplete)
    const searchMeals = async (query) => {
        if (query.length < 2) {
            return [];
        }

        try {
            const response = await axios.get('/api/meal-plans/suggest', {
                params: { q: query }
            });
            return response.data || [];
        } catch (err) {
            console.error('Error searching meals:', err);
            return [];
        }
    };

    // Get all unique meals (meals library)
    const fetchMealsLibrary = async () => {
        try {
            const response = await axios.get('/api/meal-plans/library');
            return response.data || [];
        } catch (err) {
            error.value = err.message;
            throw err;
        }
    };

    return {
        mealPlans,
        loading,
        error,
        currentWeekStart,
        groupedMealPlans,
        getMealPlansForDate,
        getMealPlan,
        fetchMealPlans,
        createMealPlan,
        updateMealPlan,
        deleteMealPlan,
        addIngredient,
        removeIngredient,
        addIngredientsToShoppingList,
        searchMeals,
        fetchMealsLibrary,
    };
});
