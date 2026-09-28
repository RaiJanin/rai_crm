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
        Schema::create('tasks', function (Blueprint $table) {
            $table->id('task_id');
            $table->foreignId('deal_id')->nullable()->constrained('deals', 'deal_id')->cascadeOnDelete();
            $table->foreignId('contact_id')->nullable()->constrained('contacts', 'contact_id')->cascadeOnDelete();
            $table->text('description');
            $table->foreignId('responsible_person_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('pending');
            $table->date('due_date')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'due_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
