<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;

/**
 * Catálogo de tipos de billetera por país (combo al cargar billetera).
 * Se precargan los tipos base; el Admin los gestiona desde su panel.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallet_types', function (Blueprint $table) {
            $table->id();
            $table->string('country', 2); // AR|ES
            $table->string('code', 40); // transferencia|mercadopago|paypal|bizum
            $table->string('name', 100);
            $table->json('fields')->nullable(); // ej. ["holder","cbu","alias"]
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->unique(['country', 'code']);
        });

        $base = [
            ['country' => 'AR', 'code' => 'transferencia', 'name' => 'Transferencia bancaria', 'fields' => ['holder', 'cbu', 'alias', 'bank']],
            ['country' => 'AR', 'code' => 'mercadopago', 'name' => 'Mercado Pago', 'fields' => ['holder', 'alias']],
            ['country' => 'ES', 'code' => 'transferencia', 'name' => 'Transferencia bancaria', 'fields' => ['holder', 'iban', 'bank']],
            ['country' => 'ES', 'code' => 'paypal', 'name' => 'PayPal', 'fields' => ['holder', 'email']],
            ['country' => 'ES', 'code' => 'bizum', 'name' => 'Bizum', 'fields' => ['holder', 'phone']],
        ];

        foreach ($base as $row) {
            DB::table('wallet_types')->updateOrInsert(
                ['country' => $row['country'], 'code' => $row['code']],
                array_merge($row, [
                    'fields' => json_encode($row['fields']),
                    'active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }

        Schema::table('payout_accounts', function (Blueprint $table) {
            $table->string('country', 2)->default('AR')->after('currency');
            $table->foreignId('wallet_type_id')->nullable()->after('user_id')->constrained()->nullOnDelete();
            $table->string('account_ref', 255)->nullable()->after('alias_cbu');
        });
    }

    public function down(): void
    {
        Schema::table('payout_accounts', function (Blueprint $table) {
            $table->dropForeign(['wallet_type_id']);
            $table->dropColumn(['country', 'wallet_type_id', 'account_ref']);
        });

        Schema::dropIfExists('wallet_types');
    }
};
