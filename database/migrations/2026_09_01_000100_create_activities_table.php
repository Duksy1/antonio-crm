<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('contact_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('deal_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('quote_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 32);
            $table->string('subject');
            $table->text('notes')->nullable();
            $table->timestamp('due_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['owner_id', 'completed_at']);
            $table->index(['owner_id', 'due_at']);
            $table->index(['owner_id', 'type']);
            $table->index('company_id');
            $table->index('contact_id');
            $table->index('deal_id');
            $table->index('quote_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activities');
    }
};
