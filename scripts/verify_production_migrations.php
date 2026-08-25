<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

require dirname(__DIR__).'/vendor/autoload.php';

$app = require dirname(__DIR__).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$migration = require dirname(__DIR__).'/database/migrations/2026_08_25_123000_customize_catering_sales_workflow.php';

DB::beginTransaction();

try {
    $pipeline = DB::table('lead_pipelines')->where('is_default', 1)->first();
    $contacted = DB::table('lead_pipeline_stages')
        ->where('lead_pipeline_id', $pipeline->id)
        ->where('name', 'Contacted')
        ->first();

    DB::table('lead_pipeline_stages')->where('id', $contacted->id)->update(['code' => 'legacy-contacted']);

    $duplicateId = DB::table('lead_pipeline_stages')->insertGetId([
        'lead_pipeline_id' => $pipeline->id,
        'code'             => 'follow-up',
        'name'             => 'Legacy Follow Up',
        'probability'      => 10,
        'sort_order'       => 20,
    ]);

    $webSourceId = DB::table('lead_sources')->where('name', 'Web')->value('id');
    $websiteSourceId = DB::table('lead_sources')->where('name', 'Website')->value('id');

    if (! $webSourceId && $websiteSourceId) {
        DB::table('lead_sources')->insert([
            'name'       => 'Web',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    $migration->up();

    $normalizedStages = DB::table('lead_pipeline_stages')
        ->where('lead_pipeline_id', $pipeline->id)
        ->where(fn ($query) => $query->where('code', 'follow-up')->orWhere('name', 'Contacted'))
        ->get();

    assert($normalizedStages->count() === 1);
    assert($normalizedStages->first()->id === $duplicateId);
    assert($normalizedStages->first()->code === 'follow-up');
    assert($normalizedStages->first()->name === 'Contacted');
    assert(DB::table('lead_sources')->where('name', 'Web')->doesntExist());
    assert(DB::table('lead_sources')->where('name', 'Website')->exists());

    // A second run must also be safe.
    $migration->up();

    echo json_encode([
        'stage_collision_merged' => true,
        'source_collision_merged'=> true,
        'second_run_safe'        => true,
        'production_rows_deleted'=> false,
    ], JSON_PRETTY_PRINT).PHP_EOL;
} finally {
    DB::rollBack();
}
