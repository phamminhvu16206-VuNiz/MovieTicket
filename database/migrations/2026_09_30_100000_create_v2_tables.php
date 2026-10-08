<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('combos', function (Blueprint $table) {
            $table->id(); $table->string('name'); $table->text('description')->nullable();
            $table->decimal('price',10,2); $table->string('image')->nullable();
            $table->boolean('status')->default(true); $table->timestamps();
        });
        Schema::create('vouchers', function (Blueprint $table) {
            $table->id(); $table->string('code')->unique(); $table->string('name');
            $table->enum('discount_type',['percent','fixed']); $table->decimal('discount_value',10,2);
            $table->decimal('min_order',10,2)->default(0); $table->decimal('max_discount',10,2)->nullable();
            $table->unsignedInteger('usage_limit')->nullable(); $table->unsignedInteger('used_count')->default(0);
            $table->dateTime('starts_at')->nullable(); $table->dateTime('expires_at')->nullable();
            $table->boolean('status')->default(true); $table->timestamps();
        });
        Schema::create('payments', function (Blueprint $table) {
            $table->id(); $table->foreignId('booking_id')->unique()->constrained('bookings')->onDelete('cascade');
            $table->string('payment_code')->unique(); $table->string('method');
            $table->decimal('amount',10,2); $table->enum('status',['pending','success','failed','refunded'])->default('pending');
            $table->dateTime('paid_at')->nullable(); $table->string('transaction_ref')->nullable(); $table->timestamps();
        });
        Schema::create('booking_combos', function (Blueprint $table) {
            $table->id(); $table->foreignId('booking_id')->constrained('bookings')->onDelete('cascade');
            $table->foreignId('combo_id')->constrained('combos')->onDelete('cascade');
            $table->unsignedInteger('quantity')->default(1); $table->decimal('unit_price',10,2); $table->timestamps();
        });
        Schema::create('booking_vouchers', function (Blueprint $table) {
            $table->id(); $table->foreignId('booking_id')->constrained('bookings')->onDelete('cascade');
            $table->foreignId('voucher_id')->constrained('vouchers')->onDelete('cascade');
            $table->decimal('discount_amount',10,2); $table->timestamps();
            $table->unique(['booking_id','voucher_id']);
        });
        Schema::create('reviews', function (Blueprint $table) {
            $table->id(); $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('movie_id')->constrained('movies')->onDelete('cascade');
            $table->foreignId('booking_id')->nullable()->constrained('bookings')->nullOnDelete();
            $table->unsignedTinyInteger('rating'); $table->text('comment')->nullable();
            $table->boolean('status')->default(true); $table->timestamps();
            $table->unique(['user_id','movie_id']);
        });
    }
    public function down(): void {
        foreach (['reviews','booking_vouchers','booking_combos','payments','vouchers','combos'] as $table) Schema::dropIfExists($table);
    }
};
