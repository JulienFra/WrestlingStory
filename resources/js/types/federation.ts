export type FederationCard = {
    id: number;
    name: string;
    description: string | null;
    wrestlersCount: number;
};

export type FederationDetail = {
    id: number;
    name: string;
    description: string | null;
    wrestlersCount: number;
    titlesCount: number;
    activeRivalriesCount: number;
    scheduledMainEventsCount: number;
};
