import { useToast } from 'vue-toastification';

/**
 * Global notification composable
 * Wraps vue-toastification with consistent theming and API
 *
 * Usage:
 * const notify = useNotification();
 * notify.success('Operation completed!');
 * notify.error('Something went wrong');
 */
export function useNotification() {
    const toast = useToast();

    return {
        /**
         * Show success notification (teal themed)
         * @param {string} message - The message to display
         * @param {object} options - Additional toast options
         */
        success: (message, options = {}) => {
            return toast.success(message, {
                timeout: 5000,
                ...options
            });
        },

        /**
         * Show error notification (red themed)
         * @param {string} message - The error message to display
         * @param {object} options - Additional toast options
         */
        error: (message, options = {}) => {
            return toast.error(message, {
                timeout: 5000,
                ...options
            });
        },

        /**
         * Show warning notification (amber themed)
         * @param {string} message - The warning message to display
         * @param {object} options - Additional toast options
         */
        warning: (message, options = {}) => {
            return toast.warning(message, {
                timeout: 5000,
                ...options
            });
        },

        /**
         * Show info notification (blue themed)
         * @param {string} message - The info message to display
         * @param {object} options - Additional toast options
         */
        info: (message, options = {}) => {
            return toast.info(message, {
                timeout: 5000,
                ...options
            });
        },

        /**
         * Show loading notification (does not auto-dismiss)
         * Returns toast ID for manual dismissal
         * @param {string} message - The loading message to display
         * @returns {number} Toast ID
         */
        loading: (message = 'Loading...') => {
            return toast.info(message, {
                timeout: false,
                closeOnClick: false,
                closeButton: false,
                draggable: false
            });
        },

        /**
         * Dismiss a specific toast by ID
         * @param {number} toastId - The toast ID to dismiss
         */
        dismiss: (toastId) => {
            toast.dismiss(toastId);
        },

        /**
         * Clear all active toasts
         */
        clear: () => {
            toast.clear();
        }
    };
}
