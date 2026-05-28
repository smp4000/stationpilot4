<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contract_templates', function (Blueprint $table) {
            $table->dropUnique(['tenant_id', 'contract_type']);
            $table->boolean('is_default')->default(false)->after('is_active');
        });

        // Mark the first (auto-seeded) template per type as default
        \DB::table('contract_templates')
            ->select(\DB::raw('MIN(id) as id'))
            ->groupBy('tenant_id', 'contract_type')
            ->get()
            ->each(fn ($row) => \DB::table('contract_templates')->where('id', $row->id)->update(['is_default' => true]));
    }

    public function down(): void
    {
        Schema::table('contract_templates', function (Blueprint $table) {
            $table->dropColumn('is_default');
            $table->unique(['tenant_id', 'contract_type']);
        });
    }
};
