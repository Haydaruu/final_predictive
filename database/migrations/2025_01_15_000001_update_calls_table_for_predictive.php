<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('calls', function (Blueprint $table) {
            // Update status enum untuk predictive dialing
            $table->dropColumn('status');
        });
        
        Schema::table('calls', function (Blueprint $table) {
            $table->enum('status', [
                'pending', 
                'dialing', 
                'ringing', 
                'answered', 
                'connected',
                'busy',
                'no_answer',
                'failed',
                'cancelled',
                'no_agent_available'
            ])->default('pending');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('calls', function (Blueprint $table) {
            $table->dropColumn('status');
        });
        
        Schema::table('calls', function (Blueprint $table) {
            $table->enum('status', ['pending', 'ringing', 'answered', 'failed'])->default('pending');
        });
    }
};