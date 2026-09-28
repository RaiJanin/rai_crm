<?php

use App\Contracts\Attachable;
use App\Contracts\Commentable;
use App\Models\Account;
use App\Models\Attachment;
use App\Models\Comment;
use App\Models\Company;
use App\Models\Contact;
use App\Models\CrmModel;
use App\Models\CrmRecord;
use App\Models\Deal;
use App\Models\Task;
use App\Models\Ticket;
use App\Policies\AttachmentPolicy;
use App\Policies\CommentPolicy;
use App\Policies\OwnedRecordPolicy;
use App\Services\Crm\CompanyService;
use App\Services\Crm\ContactService;
use App\Services\Crm\DealService;
use App\Services\Crm\RecordService;
use App\Services\Crm\TaskService;
use App\Services\Crm\TicketService;
use App\Services\Reports\AccountReport;
use App\Services\Reports\OverviewReport;
use App\Services\Reports\Report;
use App\Services\Trash\CompanyBin;
use App\Services\Trash\ContactBin;
use App\Services\Trash\ContactOwnedBin;
use App\Services\Trash\DealBin;
use App\Services\Trash\TicketBin;
use App\Services\Trash\TrashBin;

arch('every CRM model extends CrmModel')
    ->expect([Company::class, Contact::class, Deal::class, Task::class, Ticket::class, Comment::class, Attachment::class])
    ->toExtend(CrmModel::class);

arch('trashable records extend CrmRecord and accept attachments')
    ->expect([Company::class, Contact::class, Deal::class, Task::class, Ticket::class])
    ->toExtend(CrmRecord::class)
    ->toImplement(Attachable::class);

arch('companies and contacts are reportable accounts')
    ->expect([Company::class, Contact::class])
    ->toExtend(Account::class);

arch('accounts, deals and tickets accept notes')
    ->expect([Company::class, Contact::class, Deal::class, Ticket::class])
    ->toImplement(Commentable::class);

arch('base classes are abstract')
    ->expect([CrmModel::class, CrmRecord::class, Account::class, RecordService::class, Report::class, TrashBin::class, ContactOwnedBin::class, OwnedRecordPolicy::class])
    ->toBeAbstract();

arch('record services share the RecordService template')
    ->expect([CompanyService::class, ContactService::class, DealService::class, TaskService::class, TicketService::class])
    ->toExtend(RecordService::class);

arch('reports share the Report base')
    ->expect([AccountReport::class, OverviewReport::class])
    ->toExtend(Report::class);

arch('each trash bin extends TrashBin')
    ->expect([DealBin::class, TicketBin::class, ContactBin::class, CompanyBin::class])
    ->toExtend(TrashBin::class);

arch('deal and ticket bins share the contact-owned rules')
    ->expect([DealBin::class, TicketBin::class])
    ->toExtend(ContactOwnedBin::class);

arch('ownership policies share one rule')
    ->expect([CommentPolicy::class, AttachmentPolicy::class])
    ->toExtend(OwnedRecordPolicy::class);

arch('controllers delegate data access to services')
    ->expect('App\Http\Controllers')
    ->not->toUse(['Illuminate\Support\Facades\DB', 'Illuminate\Support\Facades\Storage']);
