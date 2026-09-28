import type {
    DealStage,
    SlaState,
    TaskStatus,
    TicketPriority,
    TicketStatus,
} from '@/types';

const highlight = 'bg-highlight text-highlight-foreground';
const success = 'bg-success-soft text-success-foreground';
const danger = 'bg-danger-soft text-danger-foreground';

/**
 * Open stages use the amber highlight; won/lost use status colors, which
 * stay separate from the brand palette.
 */
export const dealStageColors: Record<DealStage, string> = {
    lead: highlight,
    contacted: highlight,
    qualified: highlight,
    proposal: highlight,
    won: success,
    lost: danger,
};

/** Solid markers for pipeline columns and report bars: blue deepens as a deal progresses. */
export const dealStageAccents: Record<DealStage, string> = {
    lead: 'bg-blue-300',
    contacted: 'bg-blue-400',
    qualified: 'bg-blue-500',
    proposal: 'bg-primary-dark dark:bg-blue-600',
    won: 'bg-success',
    lost: 'bg-danger',
};

export const taskStatusColors: Record<TaskStatus, string> = {
    pending: highlight,
    in_progress: 'bg-secondary text-secondary-foreground',
    done: success,
};

/** Solid markers for task status bars. */
export const taskStatusAccents: Record<TaskStatus, string> = {
    pending: 'bg-cta',
    in_progress: 'bg-primary',
    done: 'bg-success',
};

export const taskStatusLabels: Record<TaskStatus, string> = {
    pending: 'Pending',
    in_progress: 'In progress',
    done: 'Done',
};

const neutral = 'bg-muted text-muted-foreground';
const info = 'bg-secondary text-secondary-foreground';

/** Amber = needs attention, gray = waiting, green = done. */
export const ticketStatusColors: Record<TicketStatus, string> = {
    new: info,
    open: highlight,
    pending: neutral,
    on_hold: neutral,
    resolved: success,
    closed: neutral,
};

export const ticketStatusAccents: Record<TicketStatus, string> = {
    new: 'bg-primary',
    open: 'bg-cta',
    pending: 'bg-gray-400',
    on_hold: 'bg-gray-500',
    resolved: 'bg-success',
    closed: 'bg-gray-300',
};

export const ticketPriorityColors: Record<TicketPriority, string> = {
    low: neutral,
    medium: info,
    high: highlight,
    urgent: danger,
};

export const ticketPriorityAccents: Record<TicketPriority, string> = {
    low: 'bg-gray-400',
    medium: 'bg-primary',
    high: 'bg-cta',
    urgent: 'bg-danger',
};

export const slaColors: Record<SlaState, string> = {
    on_track: info,
    due_soon: highlight,
    breached: danger,
    met: success,
    missed: danger,
};

export const slaLabels: Record<SlaState, string> = {
    on_track: 'On track',
    due_soon: 'Due soon',
    breached: 'SLA breached',
    met: 'SLA met',
    missed: 'SLA missed',
};

export const ticketStatusLabels: Record<TicketStatus, string> = {
    new: 'New',
    open: 'Open',
    pending: 'Pending customer',
    on_hold: 'On hold',
    resolved: 'Resolved',
    closed: 'Closed',
};
