<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exceptions', function (Blueprint $table): void {
            $table->string('sentry_event_id')->nullable()->after('sent_to_sentry');
        });
    }

    public function down(): void
    {
        Schema::table('exceptions', function (Blueprint $table): void {
            $table->dropColumn('sentry_event_id');
        });
    }
};
