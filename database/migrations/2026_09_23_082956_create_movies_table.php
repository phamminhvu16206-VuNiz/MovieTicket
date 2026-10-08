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
        // Gộp lại thành 1 lệnh Schema::create duy nhất
        Schema::create('movies', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('poster')->nullable();
            $table->text('description')->nullable();
            $table->string('genre'); // Thể loại
            $table->string('director')->nullable(); // Đạo diễn
            $table->string('actors')->nullable(); // Diễn viên
            $table->integer('duration'); // Thời lượng (phút)
            $table->date('release_date'); // Ngày khởi chiếu
            $table->string('trailer')->nullable();
            $table->enum('status', ['showing', 'upcoming', 'stopped'])->default('showing');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('movies');
    }
};
