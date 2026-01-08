/**
 * Format ISO date string to readable format
 * @param dateString - ISO format date (e.g., "2026-02-16T00:00:00.000000Z")
 * @returns Formatted date (e.g., "Feb 16, 2026")
 */
export const formatDate = (dateString?: string): string => {
    if (!dateString) return '';

    // Handle ISO format dates
    const date = new Date(dateString);

    // Check if date is valid
    if (isNaN(date.getTime())) return dateString;

    // Format as "Feb 16, 2026"
    return date.toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric'
    });
};

/**
 * Format time string to 12-hour format with AM/PM
 * @param timeString - Time in format "HH:MM:SS" or "HH:MM"
 * @returns Formatted time (e.g., "10:00 AM")
 */
export const formatTime = (timeString?: string): string => {
    if (!timeString) return '';

    // Handle time format like "10:00:00" or "HH:MM:SS"
    const timeMatch = timeString.match(/(\d{1,2}):(\d{2})(?::(\d{2}))?/);

    if (!timeMatch) return timeString;

    const hours = parseInt(timeMatch[1]);
    const minutes = timeMatch[2];

    // Convert to 12-hour format with AM/PM
    const period = hours >= 12 ? 'PM' : 'AM';
    const displayHours = hours % 12 || 12;

    return `${displayHours}:${minutes} ${period}`;
};

/**
 * Format datetime to readable format
 * @param dateTimeString - ISO format datetime
 * @returns Formatted datetime (e.g., "Feb 16, 2026, 10:00 AM")
 */
export const formatDateTime = (dateTimeString?: string): string => {
    if (!dateTimeString) return '';

    const date = new Date(dateTimeString);

    if (isNaN(date.getTime())) return dateTimeString;

    const dateStr = date.toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric'
    });

    const hours = date.getHours();
    const minutes = date.getMinutes().toString().padStart(2, '0');
    const period = hours >= 12 ? 'PM' : 'AM';
    const displayHours = hours % 12 || 12;

    return `${dateStr}, ${displayHours}:${minutes} ${period}`;
};
