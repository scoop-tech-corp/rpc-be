<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('loanProducts', function (Blueprint $table) {
            $table->id();
            $table->string('loanNumber')->unique();
            $table->integer('staffId');
            $table->integer('locationId');
            $table->string('eventName');
            $table->date('eventDate');
            $table->string('eventAddress')->nullable();
            $table->date('loanDate')->nullable();
            $table->date('returnDeadline')->nullable();
            $table->date('returnDate')->nullable();
            $table->string('status')->default('draft'); // draft|pending|approved|active|returned|cancelled
            $table->integer('approvedBy')->nullable();
            $table->timestamp('approvedAt')->nullable();
            $table->string('rejectedReason')->nullable();
            $table->integer('totalItems')->default(0);
            $table->integer('totalLoanedQty')->default(0);
            $table->integer('totalSoldQty')->default(0);
            $table->integer('totalReturnedQty')->default(0);
            $table->decimal('totalRevenue', 18, 2)->default(0);
            $table->text('note')->nullable();
            $table->text('returnNote')->nullable();
            $table->boolean('isDeleted')->default(false);
            $table->integer('userId');
            $table->integer('userUpdateId')->nullable();
            $table->string('deletedBy')->nullable();
            $table->timestamp('deletedAt')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loanProducts');
    }
};
