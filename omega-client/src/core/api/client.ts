
export type SystemStatus = 'stable' | 'attention' | 'risk' | 'locked';

export interface HudStateResponse {
    system_status: SystemStatus;
    context: string;
    health_score?: number;
    authority_enabled: boolean;
}

const API_BASE = 'http://127.0.0.1:8000/api';

export const hudApi = {
    async getState(): Promise<HudStateResponse> {
        try {
            const res = await fetch(`${API_BASE}/hud/state`, {
                headers: {
                    'Accept': 'application/json'
                }
            });
            if (!res.ok) throw new Error('Failed to fetch system state');
            return await res.json();
        } catch (e) {
            console.error("API Error:", e);
            // Fallback to LOCKED (Safety)
            return {
                system_status: 'locked',
                context: 'Connection Failure',
                authority_enabled: false
            };
        }
    },

    async getSimulation(): Promise<{ current_state: string; projected_state: string }> {
        const res = await fetch(`${API_BASE}/hud/simulation`, {
            headers: { 'Accept': 'application/json' }
        });
        if (!res.ok) throw new Error('Failed to fetch simulation');
        return await res.json();
    },

    async requestCommit(): Promise<{ status: string; message: string }> {
        const res = await fetch(`${API_BASE}/hud/commit`, {
            method: 'POST',
            headers: { 'Accept': 'application/json' }
        });
        if (!res.ok) throw new Error('Commit failed');
        return await res.json();
    }
};
