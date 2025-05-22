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
        Schema::create('transactionPetClinicCheckUpResults', function (Blueprint $table) {
            $table->id();

            $table->integer('weight');
            $table->integer('weightCategory');

            $table->integer('temperature');
            $table->integer('temperatureBottom');
            $table->integer('temperatureTop');
            $table->integer('temperatureCategory');

            $table->boolean('isLice');
            $table->string('noteLice')->nullable();

            $table->boolean('isFlea');
            $table->string('noteFlea')->nullable();

            $table->boolean('isCaplak');
            $table->string('noteCaplak')->nullable();

            $table->boolean('isTungau');
            $table->string('noteTungau')->nullable();

            $table->integer('ectoParasitCategory');

            $table->boolean('isNematoda');
            $table->string('noteNematoda')->nullable();

            $table->boolean('isTermatoda');
            $table->string('noteTermatoda')->nullable();

            $table->boolean('isCestode');
            $table->string('noteCestode')->nullable();

            $table->boolean('isFungiFound');

            //mukosa
            $table->string('konjung')->nullable();
            $table->string('ginggiva')->nullable();
            $table->string('ear')->nullable();
            $table->string('tongue')->nullable();
            $table->string('nose')->nullable();
            $table->string('CRT')->nullable();
            $table->string('genitals')->nullable();

            $table->string('neurologicalFindings')->nullable();
            $table->string('lokomosiFindings')->nullable();

            $table->boolean('isSnot');
            $table->string('noteSnot')->nullable();

            $table->integer('breathType');
            $table->integer('breathSoundType');
            $table->string('breathSoundNote')->nullable();

            $table->string('othersFoundBreath')->nullable();

            $table->boolean('isPulsus');
            $table->integer('heartSound');
            $table->string('othersFoundHeart')->nullable();

            $table->string('othersFoundSkin')->nullable();
            $table->string('othersFoundHair')->nullable();

            $table->integer('maleTesticles');
            $table->string('othersMaleTesticles')->nullable();
            $table->string('penisCondition')->nullable();
            $table->integer('vaginalDischargeType');
            $table->integer('urinationType');
            $table->string('othersUrination')->nullable();
            $table->string('othersFoundUrogenital')->nullable();

            $table->string('abnormalitasCavumOris')->nullable();
            $table->string('intestinalPeristalsis')->nullable();
            $table->string('perkusiAbdomen')->nullable();
            $table->string('rektumKloaka')->nullable();
            $table->string('othersCharacterRektumKloaka')->nullable();
            $table->string('fecesForm')->nullable();
            $table->string('fecesColor')->nullable();
            $table->string('fecesWithCharacter')->nullable();
            $table->string('othersFoundDigesti')->nullable();

            $table->string('reflectPupil')->nullable();
            $table->string('eyeBallCondition')->nullable();
            $table->string('othersFoundVision')->nullable();

            $table->string('earlobe')->nullable();
            $table->boolean('isEarwax')->nullable();
            $table->string('earwaxCharacter')->nullable();
            $table->string('othersFoundEar')->nullable();

            $table->boolean('isDeleted')->nullable()->default(false);
            $table->integer('userId');
            $table->integer('userUpdateId')->nullable();
            $table->string('deletedBy')->nullable();
            $table->timestamp('deletedAt', 0)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('transactionPetClinicCheckUpResults');
    }
};
