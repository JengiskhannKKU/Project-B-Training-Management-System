import { ref } from 'vue';

/**
 * Global snackbar state management
 * Provides bottom-aligned, non-intrusive notifications
 *
 * Usage:
 * const snackbar = useSnackbar();
 *
 * snackbar.show('Item saved', {
 *   variant: 'success',
 *   action: { label: 'Undo', onClick: () => console.log('Undo clicked') }
 * });
 */

// Global state shared across all useSnackbar instances
const snackbarState = ref({
    show: false,
    message: '',
    variant: 'default',
    action: null,
    position: 'bottom-center',
    duration: 3000
});

export function useSnackbar() {
    return {
        /**
         * Current snackbar state
         */
        state: snackbarState,

        /**
         * Show snackbar with message and options
         * @param {string} message - The message to display
         * @param {object} options - Configuration options
         * @param {string} options.variant - Variant: 'default' | 'success' | 'error' | 'warning' | 'info'
         * @param {object} options.action - Action button: { label: string, onClick: function }
         * @param {string} options.position - Position: 'bottom-left' | 'bottom-center' | 'bottom-right'
         * @param {number} options.duration - Auto-dismiss duration in ms (default: 3000)
         */
        show: (message, options = {}) => {
            snackbarState.value = {
                show: true,
                message,
                variant: options.variant || 'default',
                action: options.action || null,
                position: options.position || 'bottom-center',
                duration: options.duration !== undefined ? options.duration : 3000
            };
        },

        /**
         * Show success snackbar (teal themed)
         * @param {string} message - Success message
         * @param {object} action - Optional action button
         */
        success: (message, action = null) => {
            snackbarState.value = {
                show: true,
                message,
                variant: 'success',
                action,
                position: 'bottom-center',
                duration: 3000
            };
        },

        /**
         * Show error snackbar
         * @param {string} message - Error message
         */
        error: (message) => {
            snackbarState.value = {
                show: true,
                message,
                variant: 'error',
                action: null,
                position: 'bottom-center',
                duration: 4000 // Slightly longer for errors
            };
        },

        /**
         * Show info snackbar
         * @param {string} message - Info message
         * @param {object} action - Optional action button
         */
        info: (message, action = null) => {
            snackbarState.value = {
                show: true,
                message,
                variant: 'info',
                action,
                position: 'bottom-center',
                duration: 3000
            };
        },

        /**
         * Hide/dismiss the current snackbar
         */
        hide: () => {
            snackbarState.value.show = false;
        }
    };
}
