/**
 * SweetAlert2 Theme Configuration
 * Matches the application's teal/cyan brand colors and Tailwind design system
 */

export const sweetalertTheme = {
    // Button colors
    confirmButtonColor: '#2f837d',
    cancelButtonColor: '#6b7280',

    // Custom CSS classes for buttons
    customClass: {
        confirmButton: 'swal2-confirm-custom',
        cancelButton: 'swal2-cancel-custom',
        popup: 'swal2-popup-custom',
        title: 'swal2-title-custom',
        htmlContainer: 'swal2-html-custom',
    },

    // Disable default button styling to use custom classes
    buttonsStyling: false,

    // Animation settings
    showClass: {
        popup: 'swal2-show',
        backdrop: 'swal2-backdrop-show'
    },
    hideClass: {
        popup: 'swal2-hide',
        backdrop: 'swal2-backdrop-hide'
    }
};

/**
 * Additional inline styles for buttons
 * These complement the CSS classes and ensure proper theming
 */
export const confirmButtonStyle = `
    background: linear-gradient(to right, #3D9792, #2d7773);
    color: white;
    font-weight: 600;
    padding: 0.5rem 1rem;
    border-radius: 0.5rem;
    border: none;
    cursor: pointer;
    transition: all 200ms ease-in-out;
    font-family: 'Prompt', sans-serif;
    font-size: 0.875rem;
`;

export const cancelButtonStyle = `
    background: white;
    color: #374151;
    font-weight: 600;
    padding: 0.5rem 1rem;
    border-radius: 0.5rem;
    border: 1px solid #d1d5db;
    cursor: pointer;
    transition: all 200ms ease-in-out;
    font-family: 'Prompt', sans-serif;
    font-size: 0.875rem;
`;
