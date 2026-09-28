export type DealStage =
    | 'lead'
    | 'contacted'
    | 'qualified'
    | 'proposal'
    | 'won'
    | 'lost';

export type TaskStatus = 'pending' | 'in_progress' | 'done';

export type Option<T extends string = string> = {
    value: T;
    label: string;
};

export type UserOption = {
    id: number;
    name: string;
};

export type Company = {
    company_id: number;
    hash_id: string;
    name: string;
    industry: string | null;
    website: string | null;
    phone: string | null;
    address: string | null;
    created_at: string;
    deleted_at?: string | null;
    contacts_count?: number;
    deals_count?: number;
    contacts?: Contact[];
    deals?: Deal[];
    comments?: Comment[];
    attachments?: Attachment[];
};

export type Contact = {
    contact_id: number;
    hash_id: string;
    company_id: number | null;
    first_name: string;
    last_name: string | null;
    full_name: string;
    email: string | null;
    phone: string | null;
    title: string | null;
    source: string | null;
    created_at: string;
    deleted_at?: string | null;
    deals_count?: number;
    company?: Pick<Company, 'company_id' | 'hash_id' | 'name'> | null;
    deals?: Deal[];
    tasks?: Task[];
    comments?: Comment[];
    attachments?: Attachment[];
};

export type Deal = {
    deal_id: number;
    hash_id: string;
    title: string;
    contact_id: number;
    company_id: number | null;
    value: string;
    stage: DealStage;
    expected_close_date: string | null;
    closed_at: string | null;
    notes: string | null;
    created_at: string;
    updated_at: string;
    deleted_at?: string | null;
    contact?: Pick<Contact, 'contact_id' | 'hash_id' | 'full_name'> &
        Partial<Contact>;
    company?: Pick<Company, 'company_id' | 'hash_id' | 'name'> | null;
    creator?: UserOption | null;
    tasks?: Task[];
    comments?: Comment[];
    attachments?: Attachment[];
};

export type Task = {
    task_id: number;
    hash_id: string;
    deal_id: number | null;
    contact_id: number | null;
    ticket_id: number | null;
    description: string;
    responsible_person_id: number | null;
    status: TaskStatus;
    due_date: string | null;
    created_at: string;
    responsible?: UserOption | null;
    deal?: Pick<Deal, 'deal_id' | 'hash_id' | 'title'> | null;
    contact?: Pick<Contact, 'contact_id' | 'hash_id' | 'full_name'> | null;
    ticket?: Pick<
        Ticket,
        'ticket_id' | 'hash_id' | 'reference' | 'subject'
    > | null;
};

export type Comment = {
    comment_id: number;
    hash_id: string;
    user_id: number | null;
    body: string;
    is_public: boolean;
    created_at: string;
    user?: UserOption | null;
};

export type Attachment = {
    attachment_id: number;
    hash_id: string;
    uploaded_by: number | null;
    file_name: string;
    mime_type: string | null;
    file_size: number;
    created_at: string;
    uploader?: UserOption | null;
};

export type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

export type Paginated<T> = {
    data: T[];
    links: PaginationLink[];
    current_page: number;
    last_page: number;
    from: number | null;
    to: number | null;
    total: number;
};

export type ActivityItem = {
    key: string;
    kind: 'comment' | 'deal';
    user: string | null;
    text: string;
    subject: {
        type: 'deal' | 'contact' | 'company' | 'ticket';
        id: string;
        label: string;
    } | null;
    at: string;
};

export type TicketStatus =
    | 'new'
    | 'open'
    | 'pending'
    | 'on_hold'
    | 'resolved'
    | 'closed';

export type TicketPriority = 'low' | 'medium' | 'high' | 'urgent';

export type TicketType =
    | 'question'
    | 'incident'
    | 'problem'
    | 'request'
    | 'complaint'
    | 'billing';

export type TicketChannel =
    | 'email'
    | 'phone'
    | 'chat'
    | 'web'
    | 'walk_in'
    | 'social';

export type SlaState = 'on_track' | 'due_soon' | 'breached' | 'met' | 'missed';

export type Sla = {
    state: SlaState;
    due_at: string | null;
    target: 'first_response' | 'resolution' | null;
};

export type Ticket = {
    ticket_id: number;
    hash_id: string;
    reference: string;
    subject: string;
    description: string;
    contact_id: number;
    company_id: number | null;
    deal_id: number | null;
    type: TicketType;
    priority: TicketPriority;
    status: TicketStatus;
    channel: TicketChannel;
    assignee_id: number | null;
    first_response_due_at: string | null;
    resolution_due_at: string | null;
    first_responded_at: string | null;
    resolved_at: string | null;
    closed_at: string | null;
    resolution: string | null;
    satisfaction: number | null;
    created_at: string;
    deleted_at?: string | null;
    sla?: Sla;
    contact?: Pick<Contact, 'contact_id' | 'hash_id' | 'full_name'> &
        Partial<Contact>;
    company?: Pick<Company, 'company_id' | 'hash_id' | 'name'> | null;
    deal?: Pick<Deal, 'deal_id' | 'hash_id' | 'title' | 'stage'> | null;
    assignee?: UserOption | null;
    creator?: UserOption | null;
    tasks?: Task[];
    comments?: Comment[];
    attachments?: Attachment[];
};
