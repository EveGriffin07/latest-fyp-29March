<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('announcements', function (Blueprint $table) {
            // 1. Primary Key: announcement_id (Integer)
            $table->id('announcement_id'); 

            // 2. Title (Varchar)
            $table->string('title');

            // 3. Content (Varchar) - Maps to form input "message"
            $table->text('content'); 

            // 4. Audience Type (Varchar) - Maps to form input "audience"
            $table->string('audience_type'); // e.g., 'all', 'admin', 'department'

            // 5. Publish Date (DateTime)
            $table->dateTime('publish_at');

            // --- Fields required by your UI (even if not in snippet) ---
            $table->string('priority')->default('Normal'); // For the badges (Critical/Important)
            $table->string('department')->nullable();      // For specific dept filtering
            $table->date('expires_at')->nullable();        // For the "Expiry" input
            $table->text('remarks')->nullable();           // For "Additional Notes"

            // Metadata
            // explicitly point to 'user_id' on the 'users' table
$table->foreignId('posted_by')->nullable()->constrained('users', 'user_id')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('announcements');
    }
};