<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cinema_id')->constrained('cinemas')->onDelete('cascade');
            $table->string('name');
            $table->integer('rows')->default(5);
            $table->integer('seats_per_row')->default(8);
            $table->enum('status', ['active', 'maintenance'])->default('active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};
