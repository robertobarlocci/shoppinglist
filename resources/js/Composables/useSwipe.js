import { ref, onMounted, onUnmounted } from 'vue';

export function useSwipe(element, { onSwipeLeft, onSwipeRight, threshold = 50 }) {
    const isSwiping = ref(false);
    const swipeDirection = ref(null);
    const swipeDistance = ref(0);

    let touchStartX = 0;
    let touchStartY = 0;
    let touchEndX = 0;
    let touchEndY = 0;

    const handleTouchStart = (e) => {
        touchStartX = e.changedTouches[0].screenX;
        touchStartY = e.changedTouches[0].screenY;
        isSwiping.value = true;
    };

    const handleTouchMove = (e) => {
        if (!isSwiping.value) return;

        touchEndX = e.changedTouches[0].screenX;
        touchEndY = e.changedTouches[0].screenY;

        const deltaX = touchEndX - touchStartX;
        const deltaY = touchEndY - touchStartY;

        // Only track horizontal swipes
        if (Math.abs(deltaX) > Math.abs(deltaY)) {
            swipeDistance.value = deltaX;

            if (deltaX > threshold) {
                swipeDirection.value = 'right';
            } else if (deltaX < -threshold) {
                swipeDirection.value = 'left';
            } else {
                swipeDirection.value = null;
            }
        }
    };

    const handleTouchEnd = () => {
        if (!isSwiping.value) return;

        const deltaX = touchEndX - touchStartX;
        const deltaY = touchEndY - touchStartY;

        // Check if horizontal swipe
        if (Math.abs(deltaX) > Math.abs(deltaY) && Math.abs(deltaX) > threshold) {
            if (deltaX > 0 && onSwipeRight) {
                onSwipeRight();
            } else if (deltaX < 0 && onSwipeLeft) {
                onSwipeLeft();
            }
        }

        // Reset
        isSwiping.value = false;
        swipeDirection.value = null;
        swipeDistance.value = 0;
        touchStartX = 0;
        touchStartY = 0;
        touchEndX = 0;
        touchEndY = 0;
    };

    onMounted(() => {
        if (element.value) {
            element.value.addEventListener('touchstart', handleTouchStart);
            element.value.addEventListener('touchmove', handleTouchMove);
            element.value.addEventListener('touchend', handleTouchEnd);
        }
    });

    onUnmounted(() => {
        if (element.value) {
            element.value.removeEventListener('touchstart', handleTouchStart);
            element.value.removeEventListener('touchmove', handleTouchMove);
            element.value.removeEventListener('touchend', handleTouchEnd);
        }
    });

    return {
        isSwiping,
        swipeDirection,
        swipeDistance,
    };
}
