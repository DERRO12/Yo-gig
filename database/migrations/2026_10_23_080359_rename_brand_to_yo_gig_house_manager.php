<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Rebrand content already saved by the admin or the seeders from "YO'gig Maid" to "Yo-gig House Manager".
 */
return new class extends Migration
{
    /**
     * @var array<string, list<string>>
     */
    protected array $columns = [
        'settings' => ['value'],
        'page_sections' => ['badge', 'title', 'body', 'button_text', 'secondary_button_text'],
        'services' => ['name', 'short_description', 'description'],
        'testimonials' => ['role', 'quote'],
    ];

    public function up(): void
    {
        $this->replace("YO'gig Maid", 'Yo-gig House Manager');
    }

    public function down(): void
    {
        $this->replace('Yo-gig House Manager', "YO'gig Maid");
    }

    protected function replace(string $from, string $to): void
    {
        foreach ($this->columns as $table => $columns) {
            foreach ($columns as $column) {
                DB::table($table)
                    ->where($column, 'like', '%'.$from.'%')
                    ->update([$column => DB::raw('REPLACE('.$column.', '.DB::getPdo()->quote($from).', '.DB::getPdo()->quote($to).')')]);
            }
        }

        Cache::forget('site_settings');
    }
};
