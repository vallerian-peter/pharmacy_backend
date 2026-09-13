<?php

use App\Enum\StatusEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ─── branches ─────────────────────────────────────────────────────────
        // Branches are created ONLINE by the Owner, then synced down to PCs.
        // uuid is used as FK in all operational tables (sales, purchases, batches…)
        // because those records are created offline using branch uuid as reference.
        Schema::create('branches', function (Blueprint $table) {
            $table->id();                                      // integer PK — internal joins only
            $table->string('uuid')->unique();                  // PUBLIC id — used as FK in offline tables

            $table->string('name');
            $table->string('address');
            $table->string('phone');

            // Short code for offline invoice numbering: e.g. "KRK" → KRK-000001
            // Ensures invoice numbers never collide between branches when created offline
            $table->string('invoicePrefix', 10)->unique();

            $table->boolean('isMainBranch')->default(false);
            $table->string('status')->default(StatusEnum::ACTIVE->value);

            $table->softDeletes('deletedAt');                  // camelCase
            $table->timestamp('createdAt')->nullable();        // camelCase
            $table->timestamp('updatedAt')->nullable();        // camelCase
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branches');
    }
};
