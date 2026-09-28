<?php

namespace Database\Seeders;

use App\Enums\DealStage;
use App\Enums\TaskStatus;
use App\Enums\TicketChannel;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Enums\TicketType;
use App\Enums\UserRole;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Deal;
use App\Models\Task;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;

/**
 * Seeds a believable sales pipeline for a B2B operations-software vendor.
 *
 * All companies and people are fictional. Dates are relative to today so the
 * dashboard, overdue flags and monthly reports always look current.
 */
class CrmDemoSeeder extends Seeder
{
    use WithoutModelEvents;

    /** @var array<string, User> */
    private array $team = [];

    public function run(): void
    {
        $this->team = [
            'admin' => $this->user('Test User', 'test@example.com', UserRole::Admin),
            'maria' => $this->user('Maria Santos', 'maria.santos@example.com'),
            'james' => $this->user('James Carter', 'james.carter@example.com'),
            'priya' => $this->user('Priya Nair', 'priya.nair@example.com'),
        ];

        foreach ($this->accounts() as $account) {
            $this->seedAccount($account);
        }

        foreach ($this->inboundLeads() as $lead) {
            $this->seedContact(null, $lead, $lead['owner']);
        }
    }

    /**
     * @param  array<string, mixed>  $account
     */
    private function seedAccount(array $account): void
    {
        $owner = $account['owner'];

        $company = $this->backdate(new Company([
            ...$account['company'],
            'created_by' => $this->team[$owner]->id,
        ]), $account['since']);

        $contacts = [];

        foreach ($account['contacts'] as $key => $contact) {
            $contacts[$key] = $this->seedContact($company, [...$contact, 'since' => $account['since']], $owner);
        }

        $deals = [];

        foreach ($account['deals'] as $deal) {
            $deals[$deal['title']] = $this->seedDeal($company, $contacts[$deal['contact']], $deal);
        }

        foreach ($this->supportTickets()[$company->name] ?? [] as $ticket) {
            $this->seedTicket($company, $contacts[$ticket['contact']], $deals[$ticket['deal'] ?? ''] ?? null, $ticket);
        }

        foreach ($account['notes'] ?? [] as [$author, $daysAgo, $body]) {
            $this->note($company, $author, $daysAgo, $body);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function seedContact(?Company $company, array $data, string $owner): Contact
    {
        $contact = $this->backdate(new Contact([
            'company_id' => $company?->company_id,
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'title' => $data['title'],
            'source' => $data['source'],
            'created_by' => $this->team[$owner]->id,
        ]), $data['since']);

        foreach ($data['tasks'] ?? [] as $task) {
            $this->task($contact, null, $task);
        }

        foreach ($data['notes'] ?? [] as [$author, $daysAgo, $body]) {
            $this->note($contact, $author, $daysAgo, $body);
        }

        foreach ($data['deals'] ?? [] as $deal) {
            $this->seedDeal(null, $contact, $deal);
        }

        return $contact;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function seedDeal(?Company $company, Contact $contact, array $data): Deal
    {
        $stage = $data['stage'];

        $deal = new Deal([
            'title' => $data['title'],
            'contact_id' => $contact->contact_id,
            'company_id' => $company?->company_id,
            'value' => $data['value'],
            'stage' => $stage,
            'expected_close_date' => now()->addDays($data['close'])->toDateString(),
            'notes' => $data['summary'] ?? null,
            'created_by' => $this->team[$data['owner']]->id,
        ]);

        $deal->closed_at = $stage->isClosed() ? now()->addDays($data['close'])->setTime(15, 30) : null;
        $this->backdate($deal, $data['created']);

        foreach ($data['tasks'] ?? [] as $task) {
            $this->task($contact, $deal, $task);
        }

        foreach ($data['notes'] ?? [] as [$author, $daysAgo, $body]) {
            $this->note($deal, $author, $daysAgo, $body);
        }

        return $deal;
    }

    /**
     * @param  array{0: string, 1: TaskStatus, 2: int, 3: string}  $task  [description, status, due in days, assignee]
     */
    private function task(Contact $contact, ?Deal $deal, array $task, ?Ticket $ticket = null): void
    {
        [$description, $status, $dueInDays, $assignee] = $task;

        $this->backdate(new Task([
            'deal_id' => $deal?->deal_id,
            'ticket_id' => $ticket?->ticket_id,
            'contact_id' => $contact->contact_id,
            'description' => $description,
            'responsible_person_id' => $this->team[$assignee]->id,
            'status' => $status,
            'due_date' => now()->addDays($dueInDays)->toDateString(),
        ]), max(1, 7 - $dueInDays));
    }

    /**
     * Seed a support ticket with SLA timestamps and its conversation.
     *
     * Model events are off while seeding, so SLA targets are set here from the
     * same priority rules the app uses. Times in `$data` are hours: `opened` is
     * hours ago; `responded` / `resolved` / conversation are hours after opening.
     *
     * @param  array<string, mixed>  $data
     */
    private function seedTicket(Company $company, Contact $contact, ?Deal $deal, array $data): void
    {
        /** @var TicketPriority $priority */
        $priority = $data['priority'];
        /** @var TicketStatus $status */
        $status = $data['status'];

        $openedAt = now()->subMinutes((int) ($data['opened'] * 60));
        $after = fn (float $hours) => $openedAt->addMinutes((int) ($hours * 60));

        $resolvedAt = isset($data['resolved']) ? $after($data['resolved']) : null;

        $ticket = new Ticket([
            'subject' => $data['subject'],
            'description' => $data['description'],
            'contact_id' => $contact->contact_id,
            'company_id' => $company->company_id,
            'deal_id' => $deal?->deal_id,
            'type' => $data['type'],
            'priority' => $priority,
            'status' => $status,
            'channel' => $data['channel'],
            'assignee_id' => isset($data['assignee']) ? $this->team[$data['assignee']]->id : null,
            'created_by' => $this->team[$data['logged_by'] ?? $data['assignee'] ?? 'admin']->id,
            'resolution' => $data['resolution'] ?? null,
            'satisfaction' => $data['satisfaction'] ?? null,
        ]);

        $ticket->forceFill([
            'first_response_due_at' => $openedAt->addHours($priority->firstResponseHours()),
            'resolution_due_at' => $openedAt->addHours($priority->resolutionHours()),
            'first_responded_at' => isset($data['responded']) ? $after($data['responded']) : null,
            'resolved_at' => $resolvedAt,
            'closed_at' => $status === TicketStatus::Closed ? $resolvedAt?->addDays(2) : null,
            'created_at' => $openedAt,
            'updated_at' => $resolvedAt ?? $openedAt,
        ])->save();

        foreach ($data['conversation'] ?? [] as [$author, $isPublic, $hoursAfter, $body]) {
            $at = $after($hoursAfter);

            $ticket->comments()->make([
                'user_id' => $this->team[$author]->id,
                'body' => $body,
                'is_public' => $isPublic,
            ])->forceFill(['created_at' => $at, 'updated_at' => $at])->save();
        }

        foreach ($data['tasks'] ?? [] as $task) {
            $this->task($contact, $deal, $task, $ticket);
        }
    }

    private function note(Company|Contact|Deal $record, string $author, int $daysAgo, string $body): void
    {
        $comment = $record->comments()->make([
            'user_id' => $this->team[$author]->id,
            'body' => $body,
        ]);

        $this->backdate($comment, $daysAgo);
    }

    /**
     * Save a model as if it had been created some days ago.
     *
     * @template TModel of Model
     *
     * @param  TModel  $model
     * @return TModel
     */
    private function backdate(Model $model, int $daysAgo): Model
    {
        $timestamp = now()->subDays($daysAgo)->setTime(rand(8, 17), rand(0, 59));

        $model->forceFill(['created_at' => $timestamp, 'updated_at' => $timestamp])->save();

        return $model;
    }

    private function user(string $name, string $email, UserRole $role = UserRole::Member): User
    {
        return User::where('email', $email)->first()
            ?? User::factory()->create(['name' => $name, 'email' => $email, 'role' => $role]);
    }

    /**
     * Client accounts. Deal `close` is days from today: the expected close date
     * for open deals, or (negative) when the deal was won/lost.
     *
     * @return list<array<string, mixed>>
     */
    private function accounts(): array
    {
        $done = TaskStatus::Done;
        $pending = TaskStatus::Pending;
        $working = TaskStatus::InProgress;

        return [
            [
                'owner' => 'maria',
                'since' => 150,
                'company' => [
                    'name' => 'Harborline Logistics',
                    'industry' => 'Logistics',
                    'website' => 'https://harborlinelogistics.com',
                    'phone' => '+1 510-555-0142',
                    'address' => '2200 Embarcadero Way, Oakland, CA 94606',
                ],
                'contacts' => [
                    'dana' => [
                        'first_name' => 'Dana', 'last_name' => 'Whitfield', 'title' => 'VP of Operations',
                        'email' => 'dana.whitfield@harborlinelogistics.com', 'phone' => '+1 510-555-0147', 'source' => 'Trade show',
                    ],
                    'marcus' => [
                        'first_name' => 'Marcus', 'last_name' => 'Bell', 'title' => 'IT Manager',
                        'email' => 'm.bell@harborlinelogistics.com', 'phone' => '+1 510-555-0163', 'source' => 'Trade show',
                    ],
                ],
                'deals' => [
                    [
                        'title' => 'Warehouse scanner integration', 'contact' => 'marcus', 'owner' => 'maria',
                        'value' => 12500, 'stage' => DealStage::Won, 'created' => 140, 'close' => -64,
                        'summary' => 'Integrate existing Zebra handhelds with inventory module. Fixed-fee implementation.',
                        'tasks' => [
                            ['Send SOW for scanner integration', $done, -120, 'maria'],
                            ['Hand off to onboarding team', $done, -62, 'priya'],
                        ],
                        'notes' => [
                            ['maria', 70, 'Marcus confirmed budget approval. PO expected this week.'],
                            ['priya', 58, 'Kickoff done. Their team is sharp — go-live targeted in 3 weeks.'],
                        ],
                    ],
                    [
                        'title' => 'Fleet tracking platform – 120 vehicles', 'contact' => 'dana', 'owner' => 'maria',
                        'value' => 48000, 'stage' => DealStage::Qualified, 'created' => 21, 'close' => 30,
                        'summary' => "Expansion after the scanner project. Replacing their current GPS vendor whose contract ends in Q1.\nDecision makers: Dana (ops) + CFO. Marcus owns the technical review.",
                        'tasks' => [
                            ['Schedule platform demo with dispatch leads', $done, -10, 'maria'],
                            ['Prepare ROI summary for CFO (fuel + idle time savings)', $working, 3, 'maria'],
                            ['Send security questionnaire answers to Marcus', $pending, 6, 'james'],
                        ],
                        'notes' => [
                            ['maria', 19, 'Discovery call with Dana. Pain points: no live ETA for customers, drivers still phoning in POD. Current vendor charges $42/vehicle/month.'],
                            ['maria', 9, 'Demo went well — dispatch team loved the route replay. Dana wants numbers before looping in the CFO.'],
                        ],
                    ],
                ],
                'notes' => [
                    ['maria', 30, 'Account in good standing. Marcus mentioned they might open a second warehouse in Sacramento next year.'],
                ],
            ],
            [
                'owner' => 'james',
                'since' => 95,
                'company' => [
                    'name' => 'Brightpath Dental Group',
                    'industry' => 'Healthcare',
                    'website' => 'https://brightpathdental.com',
                    'phone' => '+1 512-555-0118',
                    'address' => '901 Congress Ave, Suite 400, Austin, TX 78701',
                ],
                'contacts' => [
                    'elena' => [
                        'first_name' => 'Elena', 'last_name' => 'Ruiz', 'title' => 'Practice Director',
                        'email' => 'eruiz@brightpathdental.com', 'phone' => '+1 512-555-0121', 'source' => 'Referral',
                    ],
                    'kevin' => [
                        'first_name' => 'Kevin', 'last_name' => 'Osei', 'title' => 'Office Manager',
                        'email' => 'kevin.osei@brightpathdental.com', 'phone' => '+1 512-555-0129', 'source' => 'Referral',
                    ],
                ],
                'deals' => [
                    [
                        'title' => 'Staff training package', 'contact' => 'kevin', 'owner' => 'james',
                        'value' => 4800, 'stage' => DealStage::Lost, 'created' => 90, 'close' => -40,
                        'summary' => 'Two-day on-site training for front-desk staff.',
                        'notes' => [
                            ['james', 40, 'Lost — they decided to run training internally using our recorded videos. Keep relationship warm.'],
                        ],
                    ],
                    [
                        'title' => 'Patient scheduling suite – 6 clinics', 'contact' => 'elena', 'owner' => 'james',
                        'value' => 36000, 'stage' => DealStage::Proposal, 'created' => 34, 'close' => 12,
                        'summary' => 'Online booking, SMS reminders and chair utilization reporting across all six clinics.',
                        'tasks' => [
                            ['Send revised proposal with 3-year pricing', $done, -4, 'james'],
                            ['Follow up with Elena on proposal feedback', $pending, 1, 'james'],
                            ['Confirm HIPAA BAA template with legal', $working, 5, 'admin'],
                        ],
                        'notes' => [
                            ['james', 30, 'Referred by Dr. Patel. Their no-show rate is ~14% — reminders alone should pay for the system.'],
                            ['james', 6, 'Elena asked for a 3-year option with annual billing. Revised proposal sent.'],
                            ['admin', 2, 'Legal is OK with their BAA redlines except the indemnity clause. Discussing Thursday.'],
                        ],
                    ],
                ],
            ],
            [
                'owner' => 'priya',
                'since' => 60,
                'company' => [
                    'name' => 'Cedar & Pine Outdoor Co.',
                    'industry' => 'Retail',
                    'website' => 'https://cedarandpine.com',
                    'phone' => '+1 503-555-0184',
                    'address' => '415 NW 11th Ave, Portland, OR 97209',
                ],
                'contacts' => [
                    'sofia' => [
                        'first_name' => 'Sofia', 'last_name' => 'Lindqvist', 'title' => 'Head of E-commerce',
                        'email' => 'sofia@cedarandpine.com', 'phone' => '+1 503-555-0188', 'source' => 'Website',
                    ],
                    'tom' => [
                        'first_name' => 'Tom', 'last_name' => 'Achebe', 'title' => 'Director of Store Operations',
                        'email' => 'tom.achebe@cedarandpine.com', 'phone' => '+1 503-555-0191', 'source' => 'Website',
                        'notes' => [
                            ['priya', 12, 'Tom prefers calls before 10am Pacific. Very hands-on with the store rollout.'],
                        ],
                    ],
                ],
                'deals' => [
                    [
                        'title' => 'POS rollout – 14 stores', 'contact' => 'tom', 'owner' => 'priya',
                        'value' => 86000, 'stage' => DealStage::Won, 'created' => 55, 'close' => -9,
                        'summary' => 'Hardware bundle + software licenses for all 14 retail locations. Phased rollout over 6 weeks.',
                        'tasks' => [
                            ['Negotiate hardware discount with supplier', $done, -20, 'priya'],
                            ['Book onboarding kickoff for first 4 stores', $done, -5, 'priya'],
                            ['Ship pilot hardware to Pearl District store', $working, 2, 'james'],
                            ['Schedule rollout review after pilot week', $pending, 14, 'priya'],
                        ],
                        'notes' => [
                            ['priya', 25, 'Tom is comparing us with their incumbent. Our edge: offline mode and inventory sync with the web store.'],
                            ['priya', 9, 'Signed! Contract countersigned by their CEO. Biggest deal this quarter.'],
                        ],
                    ],
                    [
                        'title' => 'Loyalty program add-on', 'contact' => 'sofia', 'owner' => 'priya',
                        'value' => 9600, 'stage' => DealStage::Contacted, 'created' => 7, 'close' => 45,
                        'summary' => 'Upsell raised by Sofia during POS negotiations — unify online and in-store points.',
                        'tasks' => [
                            ['Send loyalty module one-pager to Sofia', $done, -3, 'priya'],
                            ['Set up discovery call with Sofia and marketing', $pending, 4, 'priya'],
                        ],
                    ],
                ],
            ],
            [
                'owner' => 'maria',
                'since' => 120,
                'company' => [
                    'name' => 'Meridian Credit Union',
                    'industry' => 'Financial services',
                    'website' => 'https://meridiancu.org',
                    'phone' => '+1 614-555-0103',
                    'address' => '77 East Broad St, Columbus, OH 43215',
                ],
                'contacts' => [
                    'rachel' => [
                        'first_name' => 'Rachel', 'last_name' => 'Kim', 'title' => 'Chief Information Officer',
                        'email' => 'rkim@meridiancu.org', 'phone' => '+1 614-555-0107', 'source' => 'Cold outreach',
                    ],
                    'victor' => [
                        'first_name' => 'Victor', 'last_name' => 'Hale', 'title' => 'Procurement Lead',
                        'email' => 'vhale@meridiancu.org', 'phone' => '+1 614-555-0110', 'source' => 'Cold outreach',
                    ],
                ],
                'deals' => [
                    [
                        'title' => 'Security audit services', 'contact' => 'victor', 'owner' => 'maria',
                        'value' => 18000, 'stage' => DealStage::Lost, 'created' => 115, 'close' => -95,
                        'notes' => [
                            ['maria', 95, 'Lost on price — went with a local firm at roughly 60% of our quote. Victor said they would revisit next fiscal year.'],
                        ],
                    ],
                    [
                        'title' => 'Member portal modernization', 'contact' => 'rachel', 'owner' => 'maria',
                        'value' => 124000, 'stage' => DealStage::Proposal, 'created' => 48, 'close' => 25,
                        'summary' => "Replace legacy member self-service portal. Needs SSO with their core banking system.\nFormal RFP process — procurement (Victor) must sign off.",
                        'tasks' => [
                            ['Submit RFP response', $done, -18, 'maria'],
                            ['Prepare reference call with another credit union client', $working, 2, 'maria'],
                            ['Follow up with Victor on procurement timeline', $pending, -2, 'maria'],
                            ['Draft implementation plan for SSO integration', $pending, 8, 'james'],
                        ],
                        'notes' => [
                            ['maria', 44, 'Rachel is sponsoring internally. Board approved the portal budget in their last meeting.'],
                            ['maria', 16, 'Shortlisted — down to us and one other vendor. Final presentations next month.'],
                            ['james', 5, 'Their core banking vendor confirmed SAML support. No custom connector needed.'],
                        ],
                    ],
                ],
            ],
            [
                'owner' => 'james',
                'since' => 160,
                'company' => [
                    'name' => 'Summit Ridge Construction',
                    'industry' => 'Construction',
                    'website' => 'https://summitridgebuild.com',
                    'phone' => '+1 303-555-0156',
                    'address' => '1480 Wynkoop St, Denver, CO 80202',
                ],
                'contacts' => [
                    'luis' => [
                        'first_name' => 'Luis', 'last_name' => 'Moreno', 'title' => 'Project Controls Manager',
                        'email' => 'lmoreno@summitridgebuild.com', 'phone' => '+1 303-555-0159', 'source' => 'Partner',
                    ],
                ],
                'deals' => [
                    [
                        'title' => 'Equipment maintenance module', 'contact' => 'luis', 'owner' => 'james',
                        'value' => 15400, 'stage' => DealStage::Won, 'created' => 155, 'close' => -130,
                        'notes' => [
                            ['james', 130, 'Closed. Luis will be our champion for the field app conversation later this year.'],
                        ],
                    ],
                    [
                        'title' => 'Field reporting app – 40 crews', 'contact' => 'luis', 'owner' => 'james',
                        'value' => 22000, 'stage' => DealStage::Lead, 'created' => 4, 'close' => 60,
                        'summary' => 'Daily logs, photo reports and safety checklists from the job site.',
                        'tasks' => [
                            ['Call Luis to qualify field app requirements', $pending, 2, 'james'],
                        ],
                    ],
                ],
            ],
            [
                'owner' => 'priya',
                'since' => 80,
                'company' => [
                    'name' => 'Bluefin Hospitality Group',
                    'industry' => 'Hospitality',
                    'website' => 'https://bluefinhotels.com',
                    'phone' => '+1 619-555-0171',
                    'address' => '1050 Harbor Dr, San Diego, CA 92101',
                ],
                'contacts' => [
                    'aisha' => [
                        'first_name' => 'Aisha', 'last_name' => 'Rahman', 'title' => 'Director of Guest Experience',
                        'email' => 'aisha.rahman@bluefinhotels.com', 'phone' => '+1 619-555-0174', 'source' => 'Webinar',
                    ],
                    'ben' => [
                        'first_name' => 'Ben', 'last_name' => 'Carroway', 'title' => 'General Manager',
                        'email' => 'bcarroway@bluefinhotels.com', 'phone' => '+1 619-555-0177', 'source' => 'Referral',
                    ],
                ],
                'deals' => [
                    [
                        'title' => 'Annual support renewal', 'contact' => 'ben', 'owner' => 'priya',
                        'value' => 7200, 'stage' => DealStage::Won, 'created' => 30, 'close' => -3,
                        'tasks' => [
                            ['Send renewal quote to Ben', $done, -20, 'priya'],
                            ['Update support contract end date in billing', $pending, 3, 'admin'],
                        ],
                        'notes' => [
                            ['priya', 3, 'Renewed for 12 months at the same rate. Ben asked about housekeeping scheduling — passed to Aisha thread.'],
                        ],
                    ],
                    [
                        'title' => 'Housekeeping scheduling – 3 hotels', 'contact' => 'aisha', 'owner' => 'priya',
                        'value' => 27000, 'stage' => DealStage::Contacted, 'created' => 12, 'close' => 35,
                        'summary' => 'Room status board and staff scheduling for the Harbor, Gaslamp and La Jolla properties.',
                        'tasks' => [
                            ['Send case study from the resort client', $done, -6, 'priya'],
                            ['Arrange site visit at the Harbor property', $pending, 9, 'priya'],
                        ],
                        'notes' => [
                            ['priya', 11, 'Aisha attended our webinar and booked a call. Rooms are often ready late because status is tracked on paper.'],
                        ],
                    ],
                ],
            ],
            [
                'owner' => 'james',
                'since' => 45,
                'company' => [
                    'name' => 'Greenfield Agritech',
                    'industry' => 'Agriculture',
                    'website' => 'https://greenfieldagritech.com',
                    'phone' => '+1 559-555-0135',
                    'address' => '3300 N Blackstone Ave, Fresno, CA 93726',
                ],
                'contacts' => [
                    'hannah' => [
                        'first_name' => 'Hannah', 'last_name' => 'Mueller', 'title' => 'Chief Operating Officer',
                        'email' => 'hannah.mueller@greenfieldagritech.com', 'phone' => '+1 559-555-0138', 'source' => 'LinkedIn',
                    ],
                ],
                'deals' => [
                    [
                        'title' => 'Cold-chain monitoring pilot', 'contact' => 'hannah', 'owner' => 'james',
                        'value' => 19500, 'stage' => DealStage::Qualified, 'created' => 40, 'close' => -5,
                        'summary' => 'Temperature sensors + alerts for 8 refrigerated storage units. 90-day paid pilot.',
                        'tasks' => [
                            ['Get sensor hardware lead time from supplier', $done, -15, 'james'],
                            ['Chase Hannah for pilot sign-off', $pending, -3, 'james'],
                        ],
                        'notes' => [
                            ['james', 38, 'Hannah lost a full cooler of produce last summer due to a compressor failure — strong urgency.'],
                            ['james', 8, 'Pilot delayed: they are waiting on a grant decision. Expected close slipping, will update date after next call.'],
                        ],
                    ],
                ],
            ],
            [
                'owner' => 'maria',
                'since' => 110,
                'company' => [
                    'name' => 'Atlas Metalworks',
                    'industry' => 'Manufacturing',
                    'website' => 'https://atlasmetalworks.com',
                    'phone' => '+1 414-555-0192',
                    'address' => '5600 W Burnham St, Milwaukee, WI 53219',
                ],
                'contacts' => [
                    'omar' => [
                        'first_name' => 'Omar', 'last_name' => 'Haddad', 'title' => 'Plant Manager',
                        'email' => 'ohaddad@atlasmetalworks.com', 'phone' => '+1 414-555-0195', 'source' => 'Trade show',
                    ],
                    'julia' => [
                        'first_name' => 'Julia', 'last_name' => 'Brenner', 'title' => 'Finance Director',
                        'email' => 'jbrenner@atlasmetalworks.com', 'phone' => '+1 414-555-0198', 'source' => 'Trade show',
                    ],
                ],
                'deals' => [
                    [
                        'title' => 'Production scheduling license – 2 plants', 'contact' => 'omar', 'owner' => 'maria',
                        'value' => 58000, 'stage' => DealStage::Won, 'created' => 100, 'close' => -47,
                        'tasks' => [
                            ['Send final contract to Julia for signature', $done, -50, 'maria'],
                        ],
                        'notes' => [
                            ['maria', 60, 'Julia pushed for net-60 payment terms. Agreed in exchange for a 2-year commitment.'],
                            ['maria', 47, 'Contract signed for both the Burnham and West Allis plants.'],
                        ],
                    ],
                    [
                        'title' => 'Predictive maintenance pilot', 'contact' => 'omar', 'owner' => 'maria',
                        'value' => 30000, 'stage' => DealStage::Lead, 'created' => 2, 'close' => 90,
                        'summary' => 'Omar asked about vibration sensors on the CNC line after two unplanned outages in August.',
                        'tasks' => [
                            ['Book intro call with our IoT solutions engineer', $pending, 5, 'maria'],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Support tickets per account, keyed by company name. Customers who bought
     * something raise incidents and requests; prospects ask pre-sales questions.
     *
     * @return array<string, list<array<string, mixed>>>
     */
    private function supportTickets(): array
    {
        $new = TicketStatus::New;
        $open = TicketStatus::Open;
        $pending = TicketStatus::Pending;
        $onHold = TicketStatus::OnHold;
        $resolved = TicketStatus::Resolved;
        $closed = TicketStatus::Closed;

        return [
            'Harborline Logistics' => [
                [
                    'contact' => 'marcus', 'deal' => 'Warehouse scanner integration',
                    'subject' => 'Handheld scanners log out every 20 minutes',
                    'description' => "Since the go-live, warehouse staff on the night shift get logged out of the handhelds roughly every 20 minutes and lose the pick list they were working on.\n\nAffects all 18 devices in the Oakland warehouse.",
                    'type' => TicketType::Incident, 'priority' => TicketPriority::High, 'channel' => TicketChannel::Email,
                    'status' => $closed, 'assignee' => 'priya', 'opened' => 42 * 24,
                    'responded' => 1.5, 'resolved' => 19,
                    'resolution' => 'Session timeout on the scanner profile was set to 20 minutes. Raised to 10 hours for warehouse roles and pushed the updated profile to all devices.',
                    'satisfaction' => 5,
                    'conversation' => [
                        ['priya', true, 1.5, 'Hi Marcus, thanks for flagging this. Could you confirm the device model and whether it happens on both Wi-Fi networks?'],
                        ['priya', false, 3, 'Reproduced on a test Zebra TC52 — session timeout on the profile is 20 min. Checking with onboarding why it was not set to the warehouse default.'],
                        ['priya', true, 18, 'We have pushed an updated profile with a 10-hour session for warehouse roles. Devices pick it up on next sync; please let us know if anyone is still logged out.'],
                    ],
                ],
                [
                    'contact' => 'dana',
                    'subject' => 'Add Sacramento warehouse as a new site',
                    'description' => 'We are opening a second warehouse in Sacramento in November. Please set it up as a new site with its own bin locations. Floor plan attached to the email.',
                    'type' => TicketType::Request, 'priority' => TicketPriority::Low, 'channel' => TicketChannel::Email,
                    'status' => $pending, 'assignee' => 'james', 'opened' => 50,
                    'responded' => 5,
                    'conversation' => [
                        ['james', true, 5, 'Happy to set this up, Dana. Could you send the bin naming convention you want for Sacramento (e.g. aisle-rack-level) and the go-live date?'],
                        ['james', false, 6, 'Waiting on bin naming from Dana before we build the site.'],
                    ],
                ],
            ],
            'Brightpath Dental Group' => [
                [
                    'contact' => 'elena', 'deal' => 'Patient scheduling suite – 6 clinics',
                    'subject' => 'Where is patient data stored? (HIPAA question)',
                    'description' => 'Before we sign, our compliance officer needs to know which data centres patient records are stored in, and whether backups leave the US.',
                    'type' => TicketType::Question, 'priority' => TicketPriority::Medium, 'channel' => TicketChannel::Email,
                    'status' => $open, 'assignee' => 'admin', 'logged_by' => 'james', 'opened' => 30,
                    'responded' => 4,
                    'conversation' => [
                        ['admin', true, 4, 'Hi Elena — all patient data is stored in US-East and US-West regions; backups stay in-country. I will send our data processing addendum and SOC 2 report by tomorrow.'],
                        ['james', false, 6, 'This is blocking the scheduling suite deal — please prioritise the SOC 2 report.'],
                    ],
                ],
            ],
            'Cedar & Pine Outdoor Co.' => [
                [
                    'contact' => 'tom', 'deal' => 'POS rollout – 14 stores',
                    'subject' => 'Pearl District POS not syncing offline sales',
                    'description' => "The Pearl District pilot store lost internet for an hour this morning. Sales rung up offline are still not showing in the back office, so stock levels are wrong.\n\nWe open the next four stores on Monday and need confidence this works.",
                    'type' => TicketType::Incident, 'priority' => TicketPriority::Urgent, 'channel' => TicketChannel::Phone,
                    'status' => $open, 'assignee' => 'james', 'logged_by' => 'priya', 'opened' => 6,
                    'responded' => 0.5,
                    'conversation' => [
                        ['priya', false, 0.2, 'Tom called in, quite worried about Monday. Escalating to James as urgent.'],
                        ['james', true, 0.5, 'Tom, we are on it. Please do not clear the terminal cache — the offline transactions are queued locally and we can replay them.'],
                        ['james', false, 3, 'Sync queue stuck on a transaction with a discontinued SKU. Working on a fix to skip and flag invalid lines instead of blocking the queue.'],
                    ],
                    'tasks' => [
                        ['Replay queued offline transactions at Pearl District', TaskStatus::InProgress, 0, 'james'],
                        ['Confirm fix before Monday rollout of 4 stores', TaskStatus::Pending, 2, 'priya'],
                    ],
                ],
                [
                    'contact' => 'tom',
                    'subject' => 'Receipt printer shows old store address',
                    'description' => 'Receipts printed at the Pearl District store still show the address of the old Burnside location.',
                    'type' => TicketType::Problem, 'priority' => TicketPriority::Low, 'channel' => TicketChannel::Web,
                    'status' => $new, 'opened' => 2,
                ],
            ],
            'Meridian Credit Union' => [
                [
                    'contact' => 'victor', 'deal' => 'Member portal modernization',
                    'subject' => 'RFP clarification: SSO requirements in section 4.2',
                    'description' => 'Section 4.2 of our RFP asks for SAML 2.0 SSO. Please confirm whether your portal also supports SCIM provisioning for member accounts.',
                    'type' => TicketType::Question, 'priority' => TicketPriority::High, 'channel' => TicketChannel::Email,
                    'status' => $resolved, 'assignee' => 'maria', 'opened' => 12 * 24,
                    'responded' => 3, 'resolved' => 18,
                    'resolution' => 'Confirmed SAML 2.0 and SCIM 2.0 support; sent the integration guide and added the answer to the RFP response.',
                    'satisfaction' => 4,
                    'conversation' => [
                        ['maria', true, 3, 'Hi Victor, yes — we support SAML 2.0 and SCIM 2.0. I am checking with engineering for the exact attribute mapping and will follow up today.'],
                        ['maria', true, 17, 'Attached is the integration guide with the SCIM attribute mapping. We have also updated section 4.2 of our RFP response.'],
                    ],
                ],
            ],
            'Summit Ridge Construction' => [
                [
                    'contact' => 'luis', 'deal' => 'Equipment maintenance module',
                    'subject' => 'Maintenance reminders are emailed twice',
                    'description' => 'Every service reminder for our excavators arrives twice, a minute apart. Crew leads are starting to ignore them.',
                    'type' => TicketType::Problem, 'priority' => TicketPriority::Medium, 'channel' => TicketChannel::Email,
                    'status' => $onHold, 'assignee' => 'james', 'opened' => 5 * 24,
                    'responded' => 3,
                    'conversation' => [
                        ['james', true, 3, 'Thanks Luis — we can see the duplicates in the mail log. Looking into it now.'],
                        ['james', false, 26, 'Root cause is a scheduler bug in the reminders service. Engineering has a fix planned for release 2.14 — on hold until it ships.'],
                        ['james', true, 27, 'Quick update: we found the cause and a fix is scheduled for our next release. As a workaround you can mute the second reminder rule under Settings → Notifications.'],
                    ],
                ],
                [
                    'contact' => 'luis', 'deal' => 'Equipment maintenance module',
                    'subject' => 'How do I export equipment history to Excel?',
                    'description' => 'Our auditor wants the full service history of each machine for the last year in a spreadsheet.',
                    'type' => TicketType::Question, 'priority' => TicketPriority::Low, 'channel' => TicketChannel::Chat,
                    'status' => $closed, 'assignee' => 'priya', 'opened' => 60 * 24,
                    'responded' => 0.3, 'resolved' => 1,
                    'resolution' => 'Walked Luis through Reports → Equipment history → Export (XLSX) with a 12-month date range.',
                    'satisfaction' => 5,
                    'conversation' => [
                        ['priya', true, 0.3, 'Hi Luis! Go to Reports → Equipment history, set the date range to the last 12 months and click Export → XLSX.'],
                    ],
                ],
            ],
            'Bluefin Hospitality Group' => [
                [
                    'contact' => 'ben', 'deal' => 'Annual support renewal',
                    'subject' => 'Renewal invoice shows the wrong tax amount',
                    'description' => 'The renewal invoice applies sales tax, but our account is tax-exempt (certificate on file since last year).',
                    'type' => TicketType::Billing, 'priority' => TicketPriority::Medium, 'channel' => TicketChannel::Email,
                    'status' => $resolved, 'assignee' => 'admin', 'logged_by' => 'priya', 'opened' => 4 * 24,
                    'responded' => 10, 'resolved' => 30,
                    'resolution' => 'Tax exemption was missing on the new billing profile. Re-applied the certificate and issued a corrected invoice.',
                    'satisfaction' => 3,
                    'conversation' => [
                        ['admin', true, 10, 'Apologies for the delay, Ben. You are right — the exemption did not carry over to the new billing profile. Fixing it now.'],
                        ['admin', true, 29, 'A corrected invoice without sales tax has been sent to accounts@bluefinhotels.com.'],
                    ],
                ],
                [
                    'contact' => 'aisha',
                    'subject' => 'Complaint: slow replies from support last month',
                    'description' => 'Two of our front-desk requests in August took more than a week to get an answer. We expect better given our support plan.',
                    'type' => TicketType::Complaint, 'priority' => TicketPriority::High, 'channel' => TicketChannel::Phone,
                    'status' => $open, 'assignee' => 'priya', 'opened' => 20,
                    'responded' => 2.5,
                    'conversation' => [
                        ['priya', true, 2.5, 'Aisha, I am sorry about the delays in August. I am reviewing both requests now and will call you tomorrow with what happened and what we are changing.'],
                        ['priya', false, 4, 'Both August tickets sat unassigned over a holiday weekend. Proposing weekend rota coverage for hospitality accounts.'],
                    ],
                    'tasks' => [
                        ['Call Aisha with review of the August tickets', TaskStatus::Pending, 1, 'priya'],
                    ],
                ],
            ],
            'Atlas Metalworks' => [
                [
                    'contact' => 'omar', 'deal' => 'Production scheduling license – 2 plants',
                    'subject' => 'Scheduling board freezes with 500+ work orders',
                    'description' => 'When the West Allis plant loads the full week (about 540 work orders) the scheduling board freezes for 30+ seconds and sometimes crashes the browser tab.',
                    'type' => TicketType::Problem, 'priority' => TicketPriority::High, 'channel' => TicketChannel::Email,
                    'status' => $open, 'assignee' => 'james', 'opened' => 28,
                    'responded' => 2,
                    'conversation' => [
                        ['james', true, 2, 'Hi Omar, thanks for the detail. Could you share a HAR file from Chrome while the board loads? That will show us where the time goes.'],
                        ['james', false, 20, 'HAR shows the board rendering every work order at once. Engineering suggests enabling virtualised rows — testing on a copy of their data.'],
                    ],
                    'tasks' => [
                        ['Reproduce freeze with Atlas West Allis dataset', TaskStatus::InProgress, 0, 'james'],
                    ],
                ],
                [
                    'contact' => 'julia', 'deal' => 'Production scheduling license – 2 plants',
                    'subject' => 'Add 15 user seats for the West Allis plant',
                    'description' => 'Please add 15 more planner seats to our licence for the West Allis team, billed pro rata.',
                    'type' => TicketType::Request, 'priority' => TicketPriority::Medium, 'channel' => TicketChannel::Email,
                    'status' => $resolved, 'assignee' => 'maria', 'opened' => 20 * 24,
                    'responded' => 2, 'resolved' => 26,
                    'resolution' => 'Added 15 seats and sent the pro-rata invoice.',
                    'satisfaction' => 5,
                    'conversation' => [
                        ['maria', true, 2, 'Hi Julia, happy to. I will send a pro-rata quote for the 15 seats this afternoon.'],
                    ],
                ],
                [
                    'contact' => 'julia',
                    'subject' => 'Copy of the July invoice',
                    'description' => 'Our auditors need a PDF copy of the July invoice.',
                    'type' => TicketType::Billing, 'priority' => TicketPriority::Low, 'channel' => TicketChannel::Email,
                    'status' => $closed, 'assignee' => 'admin', 'opened' => 35 * 24,
                    'responded' => 3, 'resolved' => 3.2,
                    'resolution' => 'Sent the July invoice PDF.',
                    'satisfaction' => 4,
                    'conversation' => [
                        ['admin', true, 3, 'Hi Julia, the July invoice is attached. You can also download past invoices under Billing → History.'],
                    ],
                ],
            ],
        ];
    }

    /**
     * Individual inbound leads without a company on file yet.
     *
     * @return list<array<string, mixed>>
     */
    private function inboundLeads(): array
    {
        return [
            [
                'owner' => 'james', 'since' => 5,
                'first_name' => 'Chris', 'last_name' => 'Donovan', 'title' => 'Owner, Donovan HVAC Services',
                'email' => 'chris@donovanhvac.com', 'phone' => '+1 602-555-0113', 'source' => 'Webinar',
                'deals' => [
                    [
                        'title' => 'Starter plan – 10 seats', 'owner' => 'james',
                        'value' => 3000, 'stage' => DealStage::Lead, 'created' => 5, 'close' => 21,
                        'summary' => 'Small field-service team. Price sensitive — start on monthly billing.',
                    ],
                ],
                'tasks' => [
                    ['Reply to Chris with starter plan pricing', TaskStatus::Pending, 0, 'james'],
                ],
            ],
            [
                'owner' => 'priya', 'since' => 2,
                'first_name' => 'Nina', 'last_name' => 'Petrova', 'title' => 'Operations Consultant',
                'email' => 'nina.petrova@example.com', 'phone' => '+1 206-555-0166', 'source' => 'Website',
                'notes' => [
                    ['priya', 1, 'Filled in the contact form. Consults for several mid-size warehouses — potential referral partner rather than a buyer.'],
                ],
                'tasks' => [
                    ['Intro call with Nina about the partner program', TaskStatus::Pending, 3, 'priya'],
                ],
            ],
            [
                'owner' => 'maria', 'since' => 16,
                'first_name' => 'Samuel', 'last_name' => 'Okafor', 'title' => 'Procurement Specialist',
                'email' => 'sokafor@example.org', 'phone' => '+1 404-555-0120', 'source' => 'Cold outreach',
                'notes' => [
                    ['maria', 16, 'Left voicemail and sent an intro email.'],
                    ['maria', 9, 'Second follow-up, no response yet. Will try once more next week, then park.'],
                ],
                'tasks' => [
                    ['Final follow-up email to Samuel', TaskStatus::Pending, -1, 'maria'],
                ],
            ],
        ];
    }
}
