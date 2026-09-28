<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->id('ticket_id');
            $table->string('subject');
            $table->text('description');
            $table->foreignId('contact_id')->constrained('contacts', 'contact_id')->cascadeOnDelete();
            $table->foreignId('company_id')->nullable()->constrained('companies', 'company_id')->nullOnDelete();
            $table->foreignId('deal_id')->nullable()->constrained('deals', 'deal_id')->nullOnDelete();
            $table->string('type')->default('question');
            $table->string('priority')->default('medium');
            $table->string('status')->default('new');
            $table->string('channel')->default('email');
            $table->foreignId('assignee_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            // SLA targets and the moments they were met.
            $table->timestamp('first_response_due_at')->nullable();
            $table->timestamp('resolution_due_at')->nullable();
            $table->timestamp('first_responded_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('closed_at')->nullable();

            $table->text('resolution')->nullable();
            $table->unsignedTinyInteger('satisfaction')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'priority']);
            $table->index('resolution_due_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
