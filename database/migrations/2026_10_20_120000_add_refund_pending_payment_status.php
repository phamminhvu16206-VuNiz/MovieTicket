<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->string('status')
                ->default('pending')
                ->change();
        });
    }

    public function down(): void
    {
        // Keep the extensible status column; narrowing it could invalidate existing payment states.
    }
};
