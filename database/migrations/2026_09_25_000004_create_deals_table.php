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
        Schema::create('deals', function (Blueprint $table) {
            $table->id('deal_id');
            $table->string('title');
            $table->foreignId('contact_id')->constrained('contacts', 'contact_id')->cascadeOnDelete();
            $table->foreignId('company_id')->nullable()->constrained('companies', 'company_id')->nullOnDelete();
            $table->decimal('value', 15, 2)->default(0);
            $table->string('stage')->default('lead');
            $table->date('expected_close_date')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('stage');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deals');
    }
};
