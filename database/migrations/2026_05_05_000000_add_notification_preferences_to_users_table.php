<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->boolean('notify_email')->default(true)->after('avatar');
            $table->boolean('notify_slack')->default(false)->after('notify_email');
            $table->string('slack_webhook_url')->nullable()->after('notify_slack');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['notify_email', 'notify_slack', 'slack_webhook_url']);
        });
    }
};
