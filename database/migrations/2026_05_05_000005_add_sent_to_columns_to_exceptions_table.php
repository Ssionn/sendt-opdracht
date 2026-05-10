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
            $table->boolean('sent_to_mail')->default(false)->after('resolved_at');
            $table->boolean('sent_to_slack')->default(false)->after('sent_to_mail');
            $table->boolean('sent_to_sentry')->default(false)->after('sent_to_slack');
        });
    }

    public function down(): void
    {
        Schema::table('exceptions', function (Blueprint $table): void {
            $table->dropColumn(['sent_to_mail', 'sent_to_slack', 'sent_to_sentry']);
        });
    }
};
