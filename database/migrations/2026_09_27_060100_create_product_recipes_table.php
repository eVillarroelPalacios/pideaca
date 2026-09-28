<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_recipes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('supply_id')->constrained()->cascadeOnDelete();
            // Cantidad del insumo que lleva una unidad del producto (0.150 = 150 g).
            $table->decimal('quantity_required', 10, 3);
            $table->timestamps();

            // Un insumo no puede repetirse dentro de la misma ficha tecnica.
            $table->unique(['product_id', 'supply_id']);
            $table->index('supply_id');
        });

        DB::statement('ALTER TABLE product_recipes ADD CONSTRAINT product_recipes_quantity_required_check CHECK (quantity_required > 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('product_recipes');
    }
};
