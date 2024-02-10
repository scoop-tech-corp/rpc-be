<?php

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
        Schema::create('presentStatuses', function (Blueprint $table) {
            $table->id();

            $table->string('statusName');

            $table->boolean('isDeleted')->nullable()->default(false);
            $table->integer('userId');
            $table->integer('userUpdateId')->nullable();
            $table->string('deletedBy')->nullable();
            $table->timestamp('deletedAt', 0)->nullable();
            $table->timestamps();
        });

        $data = [
            [
                'statusName' => 'Masuk',
                'userId' => 1
            ],
            [
                'statusName' => 'Sakit',
                'userId' => 1
            ],
            [
                'statusName' => 'Cuti',
                'userId' => 1
            ],
            [
                'statusName' => 'Pulang',
                'userId' => 1
            ]
        ];

        DB::table('presentStatuses')->insert($data);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('presentStatuses');
    }
};
