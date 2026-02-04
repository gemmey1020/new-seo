
import React from 'react';
import { useMachine } from '@xstate/react';
import { hudMachine } from './core/machines/HudMachine';
import { StateZone, ContextZone, SimZone, DecisionZone } from './features/hud';
import './App.css';

const HOLD_THRESHOLD = 1200;

function App() {
  const [state, send] = useMachine(hudMachine);

  // Derived Props (Selectors)
  // Use real status from backend context (defaults to 'locked')
  const systemStatus = state.context.systemStatus;
  const isSimulating = state.matches('observing') || state.matches('holding');
  const holdProgress = Math.min(state.context.holdDuration / HOLD_THRESHOLD, 1);

  return (
    <div className="cockpit">
      {/* ZONE 1: STATE */}
      <StateZone systemStatus={systemStatus} />

      {/* ZONE 2: CONTEXT */}
      <ContextZone
        why={state.context.statusContext.why}
        trend={state.context.statusContext.trend}
        riskFactors={state.context.statusContext.risk_factors}
      />

      {/* ZONE 3: SIMULATION */}
      <SimZone
        isSimulating={isSimulating}
        current={state.context.simData?.current ?? null}
        projected={state.context.simData?.projected ?? null}
      />

      {/* ZONE 4: DECISION */}
      <DecisionZone
        canCommit={isSimulating}
        isCommitting={state.matches('committing')}
        hold Progress={holdProgress}
        onHoverStart={() => send({ type: 'HOVER_START' })}
        onHoverEnd={() => send({ type: 'HOVER_END' })}
        onHoldStart={() => send({ type: 'HOLD_START' })}
        onHoldEnd={() => send({ type: 'HOLD_END' })}
      />
    </div>
  );
}

export default App;
