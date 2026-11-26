import type { PageProps } from '@inertiajs/core';

export interface AuthUser {
    id: number;
    name: string;
    email: string;
    roles: string[];
}

export interface SharedPageProps extends PageProps {
    app: {
        name: string;
    };
    auth: {
        user: AuthUser | null;
    };
    navigation: {
        admin: AdminNavigationItem[];
    };
}

export interface AdminNavigationItem {
    label: string;
    href: string;
    description: string;
    badge?: number;
}

export const workspaceNames = ['Student', 'Teacher', 'Admin'] as const;

export type Workspace = (typeof workspaceNames)[number];
