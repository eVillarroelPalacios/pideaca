<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketing_campaign_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provider_id')->constrained()->cascadeOnDelete();

            // Motor de retencion: quantos dias sin pedir, recordatorio de un dia
            // de la semana o reactivacion de un cliente que abandono.
            $table->enum('rule_type', [
                'INACTIVE_CUSTOMER',
                'RECURRING_DAY_REMINDER',
                'WELCOME_BACK',
            ]);

            $table->integer('days_inactive')->nullable()->default(15);
            $table->integer('day_of_week')->nullable();

            $table->text('message_template');
            $table->string('discount_code', 50)->nullable();
            $table->boolean('is_enabled')->default(false);
            $table->timestamps();

            $table->index('provider_id');
            $table->index('is_enabled');
            $table->index(['provider_id', 'is_enabled']);
            $table->index(['rule_type', 'is_enabled']);
        });

        // Laravel 12 no expone Blueprint::check(), asi que los rangos validos
        // se agregan como CHECK de Postgres.
        DB::statement('ALTER TABLE marketing_campaign_rules ADD CONSTRAINT marketing_campaign_rules_day_of_week_check CHECK (day_of_week IS NULL OR (day_of_week BETWEEN 1 AND 7))');
        DB::statement('ALTER TABLE marketing_campaign_rules ADD CONSTRAINT marketing_campaign_rules_days_inactive_check CHECK (days_inactive IS NULL OR days_inactive >= 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('marketing_campaign_rules');
    }
};
