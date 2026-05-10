<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('biomarkers', function (Blueprint $table): void {
            $table->text('ai_recommendations')->nullable()->after('heart_rate');
        });
    }

    public function down(): void
    {
        Schema::table('biomarkers', function (Blueprint $table): void {
            $table->dropColumn('ai_recommendations');
        });
    }
};
