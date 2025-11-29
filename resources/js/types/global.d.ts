import type { PageProps as InertiaPageProps } from '@inertiajs/core';

declare global {
    type RoleName = 'admin' | 'trainer' | 'student';

    interface Role {
        id: number;
        name: RoleName;
        label?: string | null;
    }

    interface User {
        id: number;
        name: string;
        email: string;
        email_verified_at?: string | null;
        role?: Role | null;
    }

    interface PageProps extends InertiaPageProps {
        auth: {
            user: User;
        };
        flash?: {
            status?: string;
        };
    }
}

export {};
