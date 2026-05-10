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
            $table->text('sentry_auth_token')->nullable()->after('notify_sentry');
            $table->text('sentry_org_slug')->nullable()->after('sentry_auth_token');
            $table->text('sentry_project_slug')->nullable()->after('sentry_org_slug');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['sentry_auth_token', 'sentry_org_slug', 'sentry_project_slug']);
        });
    }
};
