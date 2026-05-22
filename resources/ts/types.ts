export type Quadrant = 'do' | 'schedule' | 'delegate' | 'eliminate';

export type TaskStatus = 'pending' | 'in_progress' | 'completed';

export interface Task {
    id: number;
    user_id: number;
    title: string;
    description: string | null;
    is_urgent: boolean;
    is_important: boolean;
    quadrant: Quadrant;
    status: TaskStatus;
    due_at: string | null;
    started_at: string | null;
    completed_at: string | null;
    estimated_minutes: number | null;
    actual_minutes: number;
    focus_minutes: number;
    points_awarded: number;
    priority_order: number;
    created_at: string;
    updated_at: string;
    tags: Tag[];
}

export interface FocusSessionRecord {
    id: number;
    user_id: number;
    task_id: number | null;
    started_at: string;
    ended_at: string | null;
    planned_minutes: number;
    actual_minutes: number;
    completed: boolean;
    interrupted: boolean;
    notes: string | null;
}

export interface UserSummary {
    id: number;
    name: string;
    email: string;
    points: number;
    level: number;
    streak_days: number;
}

export interface CompletionResponse {
    task: Task;
    points_awarded: number;
    user: UserSummary;
}

export interface StatsRow {
    day: string;
    label: string;
    tasks_completed: number;
    tasks_on_time: number;
    tasks_overdue: number;
    focus_minutes: number;
    points_earned: number;
}

export interface Tag {
    id: number;
    name: string;
    color: string;
}
