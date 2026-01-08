import Swal from 'sweetalert2';
import { sweetalertTheme } from '@/utils/sweetalertTheme';

/**
 * Global alert/confirmation dialog composable
 * Wraps SweetAlert2 with consistent theming matching the application's teal brand
 *
 * Usage:
 * const alert = useAlert();
 *
 * // Confirmation dialog
 * const confirmed = await alert.confirm({
 *   title: 'Delete Item',
 *   message: 'Are you sure you want to delete this item?',
 *   confirmText: 'Delete',
 *   cancelText: 'Cancel'
 * });
 *
 * // Simple alerts
 * await alert.success('Operation completed successfully!');
 * await alert.error('An error occurred');
 */
export function useAlert() {
    return {
        /**
         * Show confirmation dialog
         * @param {object} options - Configuration options
         * @param {string} options.title - Dialog title
         * @param {string} options.message - Dialog message/text
         * @param {string} options.confirmText - Confirm button text (default: 'Confirm')
         * @param {string} options.cancelText - Cancel button text (default: 'Cancel')
         * @returns {Promise<boolean>} True if confirmed, false if cancelled
         */
        confirm: async ({ title, message, confirmText = 'Confirm', cancelText = 'Cancel' }) => {
            const result = await Swal.fire({
                title,
                text: message,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: confirmText,
                cancelButtonText: cancelText,
                reverseButtons: true,
                focusCancel: false,
                ...sweetalertTheme
            });
            return result.isConfirmed;
        },

        /**
         * Show success alert
         * @param {string} message - Success message
         * @param {string} title - Alert title (default: 'Success')
         * @returns {Promise<void>}
         */
        success: async (message, title = 'Success') => {
            await Swal.fire({
                title,
                text: message,
                icon: 'success',
                confirmButtonText: 'OK',
                ...sweetalertTheme
            });
        },

        /**
         * Show error alert
         * @param {string} message - Error message
         * @param {string} title - Alert title (default: 'Error')
         * @returns {Promise<void>}
         */
        error: async (message, title = 'Error') => {
            await Swal.fire({
                title,
                text: message,
                icon: 'error',
                confirmButtonText: 'OK',
                ...sweetalertTheme
            });
        },

        /**
         * Show warning alert
         * @param {string} message - Warning message
         * @param {string} title - Alert title (default: 'Warning')
         * @returns {Promise<void>}
         */
        warning: async (message, title = 'Warning') => {
            await Swal.fire({
                title,
                text: message,
                icon: 'warning',
                confirmButtonText: 'OK',
                ...sweetalertTheme
            });
        },

        /**
         * Show info alert
         * @param {string} message - Info message
         * @param {string} title - Alert title (default: 'Information')
         * @returns {Promise<void>}
         */
        info: async (message, title = 'Information') => {
            await Swal.fire({
                title,
                text: message,
                icon: 'info',
                confirmButtonText: 'OK',
                ...sweetalertTheme
            });
        },

        /**
         * Show custom alert with full control
         * @param {object} options - SweetAlert2 options object
         * @returns {Promise<object>} SweetAlert2 result object
         */
        custom: async (options) => {
            return await Swal.fire({
                ...sweetalertTheme,
                ...options
            });
        }
    };
}
