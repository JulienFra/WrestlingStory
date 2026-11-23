export type Option = {
    value: string;
    label: string;
};

export type CostTotals = {
    calls: number;
    succeeded: number;
    failed: number;
    quotaRefused: number;
    cost: number;
    inputTokens: number;
    outputTokens: number;
    failureRate: number;
};

export type DayCost = {
    date: string;
    calls: number;
    failed: number;
    cost: number;
};

export type UserCost = {
    name: string;
    tier: string | null;
    calls: number;
    failed: number;
    cost: number;
    callsToday: number;
    dailyLimit: number | null;
};

export type RecentCall = {
    id: number;
    createdAt: string | null;
    user: string;
    feature: string;
    provider: string;
    model: string;
    inputTokens: number;
    outputTokens: number;
    cost: number;
    durationMs: number;
    success: boolean;
    error: string | null;
};

export type AdminUserRow = {
    id: number;
    name: string;
    email: string;
    role: string;
    tier: string;
    callsToday: number;
    dailyLimit: number;
};
