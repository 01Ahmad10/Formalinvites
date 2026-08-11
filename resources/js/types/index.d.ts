export interface User {
    id: number;
    name: string;
    email: string;
    role: 'admin' | 'support' | 'customer';
    customer_id?: number | null;
    email_verified_at?: string;
}

export type PageProps<
    T extends Record<string, unknown> = Record<string, unknown>,
> = T & {
    auth: {
        user: User;
    };
};
