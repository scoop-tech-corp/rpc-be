<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('loanProductLogs', function (Blueprint $table) {
            $table->id();
            $table->integer('loanProductId');
            $table->string('action'); // created|updated|submitted|approved|rejected|loaned|returned|cancelled
            $table->text('description')->nullable();
            $table->integer('userId');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loanProductLogs');
    }
};
