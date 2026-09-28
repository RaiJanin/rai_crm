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
        // Distinguishes replies logged to the customer from internal notes.
        Schema::table('comments', function (Blueprint $table) {
            $table->boolean('is_public')->default(false)->after('body');
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->foreignId('ticket_id')->nullable()->after('contact_id')
                ->constrained('tickets', 'ticket_id')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropConstrainedForeignId('ticket_id');
        });

        Schema::table('comments', function (Blueprint $table) {
            $table->dropColumn('is_public');
        });
    }
};
