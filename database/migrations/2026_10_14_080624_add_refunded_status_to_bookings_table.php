<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Thay đổi kiểu dữ liệu cột status thành string thông thường để bỏ qua CHECK constraint của SQLite
        Schema::table('bookings', function (Blueprint $table) {
            $table->string('status')->default('pending')->change();
        });

        if (Schema::hasTable('payments')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->string('status')->default('pending')->change();
            });
        }
    }

    public function down(): void
    {
        //
    }
};
