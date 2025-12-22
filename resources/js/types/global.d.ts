export {};

declare global {
    interface AppUserRole {
        id: number;
        name: string;
        label?: string;
    }

    interface AppUser {
        id: number;
        name: string;
        email: string;
        role?: AppUserRole;
    }

    interface Window {
        axios: any;
    }
}
