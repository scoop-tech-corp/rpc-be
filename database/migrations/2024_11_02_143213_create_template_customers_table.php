<?php

use App\Models\TemplateCustomer;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('templateCustomers', function (Blueprint $table) {
            $table->id();

            $table->string('fileName');
            $table->string('fileType');
            $table->timestamp('lastChange');

            $table->boolean('isDeleted')->nullable()->default(false);
            $table->integer('userId');
            $table->integer('userUpdateId')->nullable();
            $table->string('deletedBy')->nullable();
            $table->timestamp('deletedAt', 0)->nullable();
            $table->timestamps();
        });

        TemplateCustomer::create([
            'fileName' => 'Template Upload Customer.xlsx',
            'fileType' => 'importCustomer',
            'lastChange' => now(),
            'userId' => 1,
            'created_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('templateCustomers');
    }
};
