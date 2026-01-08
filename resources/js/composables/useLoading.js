import { ref, readonly } from 'vue';

/**
 * Global loading state management composable
 * Provides centralized loading state that can be used with LoadingSpinner component
 *
 * Usage:
 * const { isLoading, loadingMessage, startLoading, stopLoading, withLoading } = useLoading();
 *
 * // Manual control
 * startLoading('Processing...');
 * // ... do work
 * stopLoading();
 *
 * // Automatic wrapper
 * await withLoading(async () => {
 *   await fetchData();
 * }, 'Fetching data...');
 */

const isLoading = ref(false);
const loadingMessage = ref('');

export function useLoading() {
    return {
        /**
         * Read-only loading state
         */
        isLoading: readonly(isLoading),

        /**
         * Read-only loading message
         */
        loadingMessage: readonly(loadingMessage),

        /**
         * Start loading state with optional message
         * @param {string} message - The loading message to display
         */
        startLoading: (message = 'Loading...') => {
            isLoading.value = true;
            loadingMessage.value = message;
        },

        /**
         * Stop loading state and clear message
         */
        stopLoading: () => {
            isLoading.value = false;
            loadingMessage.value = '';
        },

        /**
         * Wrap an async function with loading state
         * Automatically handles start/stop and errors
         * @param {Function} asyncFn - The async function to wrap
         * @param {string} message - The loading message to display
         * @returns {Promise} The result of the async function
         */
        withLoading: async (asyncFn, message = 'Loading...') => {
            try {
                isLoading.value = true;
                loadingMessage.value = message;
                return await asyncFn();
            } catch (error) {
                throw error;
            } finally {
                isLoading.value = false;
                loadingMessage.value = '';
            }
        }
    };
}
