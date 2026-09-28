<?php

use App\Enums\TrashType;
use App\Http\Controllers\Crm\AttachmentController;
use App\Http\Controllers\Crm\CommentController;
use App\Http\Controllers\Crm\CompanyController;
use App\Http\Controllers\Crm\ContactController;
use App\Http\Controllers\Crm\DealController;
use App\Http\Controllers\Crm\ReportController;
use App\Http\Controllers\Crm\TaskController;
use App\Http\Controllers\Crm\TicketController;
use App\Http\Controllers\Crm\TrashController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::resource('companies', CompanyController::class)->except(['create', 'edit']);
    Route::resource('contacts', ContactController::class)->except(['create', 'edit']);

    Route::get('deals/pipeline', [DealController::class, 'pipeline'])->name('deals.pipeline');
    Route::patch('deals/{deal}/stage', [DealController::class, 'updateStage'])->name('deals.stage');
    Route::resource('deals', DealController::class)->except(['edit']);

    Route::get('tickets', [TicketController::class, 'allTickets'])->name('tickets.all-tickets');
    Route::get('tickets/mine', [TicketController::class, 'myTickets'])->name('tickets.my-tickets');
    Route::patch('tickets/{ticket}/triage', [TicketController::class, 'triage'])->name('tickets.triage');
    Route::resource('tickets', TicketController::class)->except(['index', 'edit']);

    Route::get('tasks', [TaskController::class, 'allTasks'])->name('tasks.all-tasks');
    Route::get('tasks/mine', [TaskController::class, 'myTasks'])->name('tasks.my-tasks');
    Route::resource('tasks', TaskController::class)->only(['store', 'update', 'destroy']);

    Route::post('comments', [CommentController::class, 'store'])->name('comments.store');
    Route::delete('comments/{comment}', [CommentController::class, 'destroy'])->name('comments.destroy');

    Route::post('attachments', [AttachmentController::class, 'store'])->name('attachments.store');
    Route::get('attachments/{attachment}/download', [AttachmentController::class, 'download'])->name('attachments.download');
    Route::delete('attachments/{attachment}', [AttachmentController::class, 'destroy'])->name('attachments.destroy');

    Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('reports/print', [ReportController::class, 'printOverview'])->name('reports.print');
    Route::get('reports/companies/{company}', [ReportController::class, 'company'])->name('reports.company');
    Route::get('reports/customers/{contact}', [ReportController::class, 'contact'])->name('reports.contact');
    Route::get('reports/companies/{company}/print', [ReportController::class, 'printCompany'])->name('reports.company.print');
    Route::get('reports/customers/{contact}/print', [ReportController::class, 'printContact'])->name('reports.contact.print');

    Route::middleware('can:admin')->prefix('trash')->name('trash.')->group(function () {
        Route::get('deals', [TrashController::class, 'deals'])->name('deals');
        Route::get('contacts', [TrashController::class, 'contacts'])->name('contacts');
        Route::get('companies', [TrashController::class, 'companies'])->name('companies');
        Route::get('tickets', [TrashController::class, 'tickets'])->name('tickets');
        Route::patch('{type}/{hash}/restore', [TrashController::class, 'restore'])
            ->whereIn('type', TrashType::values())
            ->whereAlphaNumeric('hash')
            ->name('restore');
        Route::delete('{type}/{hash}', [TrashController::class, 'destroy'])
            ->whereIn('type', TrashType::values())
            ->whereAlphaNumeric('hash')
            ->name('destroy');
    });
});

require __DIR__.'/settings.php';
