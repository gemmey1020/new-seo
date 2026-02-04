
import { createMachine, assign } from 'xstate';
import { hudApi, SystemStatus } from '../api/client';

// CONSTANTS (GOVERNANCE)
const HOLD_THRESHOLD = 1200; // ms
const HOVER_DELAY = 150; // ms

interface HudContextData {
    why: string;
    risk_factors: string[];
    trend: 'stable' | 'drifting' | 'improving';
}

interface HudContext {
    systemStatus: SystemStatus;
    statusContext: HudContextData;
    holdDuration: number;
    simData: { current: string; projected: string } | null;
    error: string | null;
}

type HudEvent =
    | { type: 'HOVER_START' }
    | { type: 'HOVER_END' }
    | { type: 'HOLD_START' }
    | { type: 'HOLD_END' } // Premature release
    | { type: 'COMMIT_SUCCESS' }
    | { type: 'COMMIT_FAILURE', error: string }
    | { type: 'TICK', elapsed: number }
    | { type: 'DATA_UPDATED', status: SystemStatus, context: HudContextData };

export const hudMachine = createMachine<HudContext, HudEvent>({
    id: 'hud',
    initial: 'booting',
    context: {
        systemStatus: 'locked', // Default safe
        statusContext: {
            why: 'Loading...',
            risk_factors: [],
            trend: 'stable'
        } as HudContextData,
        holdDuration: 0,
        simData: null,
        error: null
    },
    states: {
        booting: {
            invoke: {
                src: () => hudApi.getState(),
                onDone: {
                    target: 'idle',
                    actions: assign({
                        systemStatus: (context, event) => event.data.system_status,
                        statusContext: (context, event) => {
                            const rawContext = event.data.context;
                            // SAFETY: Backend may return string or object
                            if (typeof rawContext === 'string') {
                                return {
                                    why: rawContext,
                                    risk_factors: [],
                                    trend: 'stable' as const
                                };
                            }
                            return rawContext as unknown as HudContextData;
                        }
                    })
                },
                onError: {
                    target: 'idle', // Fallback to locked (default)
                    actions: assign({ error: 'Connection Failed' })
                }
            }
        },
        idle: {
            on: {
                HOVER_START: 'hover_pending',
            }
        },
        hover_pending: {
            after: {
                [HOVER_DELAY]: 'observing'
            },
            on: {
                HOVER_END: 'idle'
            }
        },
        observing: {
            invoke: {
                src: () => hudApi.getSimulation(),
                onDone: {
                    actions: assign((ctx, event) => ({
                        simData: {
                            current: event.data.current_state,
                            projected: event.data.projected_state
                        }
                    }))
                },
                onError: {
                    actions: assign({ error: 'Sim Failed' })
                }
            },
            on: {
                HOVER_END: {
                    target: 'idle',
                    actions: assign({ simData: null })
                },
                HOLD_START: 'holding'
            }
        },
        holding: {
            invoke: {
                src: (context) => (cb) => {
                    const interval = setInterval(() => {
                        cb({ type: 'TICK', elapsed: 100 });
                    }, 100);
                    return () => clearInterval(interval);
                }
            },
            on: {
                TICK: {
                    actions: assign({
                        holdDuration: (ctx) => ctx.holdDuration + 100
                    }),
                    target: undefined
                },
                HOLD_END: {
                    target: 'observing',
                    actions: assign({ holdDuration: 0 })
                }
            },
            always: [
                { target: 'committing', cond: (ctx) => ctx.holdDuration >= HOLD_THRESHOLD }
            ]
        },
        committing: {
            invoke: {
                src: () => hudApi.requestCommit(),
                onDone: {
                    target: 'idle', // Reset after success
                    actions: assign({ simData: null, holdDuration: 0 }) // Clear ghost
                },
                onError: {
                    target: 'error',
                    actions: assign({ error: 'Commit Failed' })
                }
            }
        },
        error: {
            on: {
                HOVER_START: 'idle'
            }
        }
    }
});
