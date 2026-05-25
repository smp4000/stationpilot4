<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('station_competitors', function (Blueprint $table) {
            $table->string('benzinpreis_hash', 32)->nullable()->after('osm_id')->index();
            $table->string('benzinpreis_slug', 255)->nullable()->after('benzinpreis_hash');
        });

        // Migrate existing bp:hash values from notes column
        \DB::table('station_competitors')
            ->whereNotNull('notes')
            ->where('notes', 'like', 'bp:%')
            ->chunkById(100, function ($rows) {
                foreach ($rows as $row) {
                    if (preg_match('/^bp:([a-f0-9]+)$/i', $row->notes, $m)) {
                        \DB::table('station_competitors')
                            ->where('id', $row->id)
                            ->update(['benzinpreis_hash' => $m[1]]);
                    }
                }
            });
    }

    public function down(): void
    {
        Schema::table('station_competitors', function (Blueprint $table) {
            $table->dropColumn(['benzinpreis_hash', 'benzinpreis_slug']);
        });
    }
};
