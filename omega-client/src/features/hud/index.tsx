import React from 'react';

// src/features/hud/StateZone.tsx
type StateZoneProps = {
    systemStatus: 'stable' | 'attention' | 'risk' | 'locked';
};

export const StateZone: React.FC<StateZoneProps> = ({ systemStatus }) => {
    return (
        <div className={`zone zone-state status-${systemStatus}`}>
            <div className="label">SYSTEM STATE</div>
            <div className="value">{systemStatus.toUpperCase()}</div>
        </div>
    );
};

// src/features/hud/ContextZone.tsx
type ContextZoneProps = {
    why: string;
    trend: 'stable' | 'drifting' | 'improving';
    riskFactors: string[];
};

export const ContextZone: React.FC<ContextZoneProps> = ({ why, trend, riskFactors }) => {
    return (
        <div className="zone zone-context">
            <div className="block-why">
                <span className="label">WHY</span>
                <span className="value">{why}</span>
            </div>
            {riskFactors && riskFactors.length > 0 && (
                <div className="block-risk">
                    <span className="label">RISK</span>
                    <ul className="value-list">
                        {riskFactors.map((r, i) => <li key={i}>{r}</li>)}
                    </ul>
                </div>
            )}
            <div className="block-time">
                <span className="label">TIME</span>
                <span className="value">{trend.toUpperCase()} -&gt;</span>
            </div>
        </div>
    );
};

// src/features/hud/SimZone.tsx
type SimZoneProps = {
    isSimulating: boolean;
    current: string | null;
    projected: string | null;
};

export const SimZone: React.FC<SimZoneProps> = ({ isSimulating, current, projected }) => {
    if (!isSimulating) return <div className="zone zone-sim empty" />;

    return (
        <div className="zone zone-sim active">
            <div className="sim-col now">{current}</div>
            <div className="sim-arrow">-&gt;</div>
            <div className="sim-col future">{projected}</div>
        </div>
    );
};

// src/features/hud/DecisionZone.tsx
interface DecisionZoneProps {
    canCommit: boolean;
    isCommitting: boolean;
    holdProgress: number; // 0 to 1
    onHoldStart: () => void;
    onHoldEnd: () => void;
    onHoverStart?: () => void;
    onHoverEnd?: () => void;
}

export const DecisionZone: React.FC<DecisionZoneProps> = ({
    canCommit,
    isCommitting,
    holdProgress,
    onHoldStart,
    onHoldEnd,
    onHoverStart,
    onHoverEnd
}) => {
    return (
        <div className="zone zone-decision">
            <div className="label">HUMAN DECISION</div>
            <button
                className="trigger"
                disabled={!canCommit || isCommitting}
                onPointerEnter={onHoverStart}
                onPointerLeave={onHoverEnd}
                onPointerDown={onHoldStart}
                onPointerUp={onHoldEnd}
                style={{
                    background: `linear-gradient(to right, #fff ${holdProgress * 100}%, #333 ${holdProgress * 100}%)`
                }}
            >
                {isCommitting ? 'COMMITTING...' : 'HOLD TO COMMIT'}
            </button>
        </div>
    );
};
