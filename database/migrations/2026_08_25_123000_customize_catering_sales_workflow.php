<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Convert the stock CRM sales setup into a catering inquiry workflow.
     */
    public function up(): void
    {
        DB::transaction(function () {
            $now = now();

            $pipeline = DB::table('lead_pipelines')
                ->where('is_default', 1)
                ->first();

            if ($pipeline) {
                $pipelineNameInUse = DB::table('lead_pipelines')
                    ->where('name', 'Catering Sales Pipeline')
                    ->where('id', '!=', $pipeline->id)
                    ->exists();

                if (! $pipelineNameInUse) {
                    DB::table('lead_pipelines')
                        ->where('id', $pipeline->id)
                        ->update([
                            'name'       => 'Catering Sales Pipeline',
                            'updated_at' => $now,
                        ]);
                }

                $stages = [
                    'new'          => ['New Inquiry', 10, 1],
                    'follow-up'    => ['Contacted', 20, 2],
                    'prospect'     => ['Qualified', 35, 3],
                    'site-visit'   => ['Site Visit Scheduled', 50, 4],
                    'proposal-sent'=> ['Proposal Sent', 65, 5],
                    'negotiation'  => ['Negotiation', 80, 6],
                    'won'          => ['Confirmed / Won', 100, 7],
                    'lost'         => ['Lost', 0, 8],
                ];

                foreach ($stages as $code => [$name, $probability, $sortOrder]) {
                    $this->upsertPipelineStage($pipeline->id, $code, $name, $probability, $sortOrder);
                }
            }

            $this->renameDefaults('lead_sources', [
                'Web'      => 'Website',
                'Web Form' => 'Website Form',
                'Phone'    => 'Phone Call',
                'Direct'   => 'Walk-in / Direct',
            ], 'lead_source_id');

            foreach (['WhatsApp', 'Instagram / Social Media', 'Referral', 'Event Planner / Agency'] as $source) {
                if (! DB::table('lead_sources')->where('name', $source)->exists()) {
                    DB::table('lead_sources')->insert([
                        'name'       => $source,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }

            $this->renameDefaults('lead_types', [
                'New Business'      => 'New Catering Client',
                'Existing Business' => 'Returning Catering Client',
            ], 'lead_type_id');

            $this->renameLeadAttributes($now);
            $this->addCateringAttributes($now);
        });
    }

    /**
     * Restore stock names and remove catering-only setup.
     */
    public function down(): void
    {
        DB::transaction(function () {
            $pipeline = DB::table('lead_pipelines')
                ->where('is_default', 1)
                ->first();

            if ($pipeline) {
                $fallbackStageId = DB::table('lead_pipeline_stages')
                    ->where('lead_pipeline_id', $pipeline->id)
                    ->where('code', 'negotiation')
                    ->value('id');

                $customStageIds = DB::table('lead_pipeline_stages')
                    ->where('lead_pipeline_id', $pipeline->id)
                    ->whereIn('code', ['site-visit', 'proposal-sent'])
                    ->pluck('id');

                if ($fallbackStageId && $customStageIds->isNotEmpty()) {
                    DB::table('leads')
                        ->whereIn('lead_pipeline_stage_id', $customStageIds)
                        ->update(['lead_pipeline_stage_id' => $fallbackStageId]);
                }

                DB::table('lead_pipeline_stages')
                    ->whereIn('id', $customStageIds)
                    ->delete();

                $stockStages = [
                    'new'         => ['New', 100, 1],
                    'follow-up'   => ['Follow Up', 100, 2],
                    'prospect'    => ['Prospect', 100, 3],
                    'negotiation' => ['Negotiation', 100, 4],
                    'won'         => ['Won', 100, 5],
                    'lost'        => ['Lost', 0, 6],
                ];

                foreach ($stockStages as $code => [$name, $probability, $sortOrder]) {
                    DB::table('lead_pipeline_stages')
                        ->where('lead_pipeline_id', $pipeline->id)
                        ->where('code', $code)
                        ->update([
                            'name'        => $name,
                            'probability' => $probability,
                            'sort_order'  => $sortOrder,
                        ]);
                }

                DB::table('lead_pipelines')
                    ->where('id', $pipeline->id)
                    ->update([
                        'name'       => 'Default Pipeline',
                        'updated_at' => now(),
                    ]);
            }

            DB::table('lead_sources')
                ->whereIn('name', ['WhatsApp', 'Instagram / Social Media', 'Referral', 'Event Planner / Agency'])
                ->whereNotIn('id', DB::table('leads')->select('lead_source_id')->whereNotNull('lead_source_id'))
                ->delete();

            $this->renameDefaults('lead_sources', [
                'Website'          => 'Web',
                'Website Form'     => 'Web Form',
                'Phone Call'       => 'Phone',
                'Walk-in / Direct' => 'Direct',
            ], 'lead_source_id');

            $this->renameDefaults('lead_types', [
                'New Catering Client'       => 'New Business',
                'Returning Catering Client' => 'Existing Business',
            ], 'lead_type_id');

            DB::table('attributes')
                ->where('entity_type', 'leads')
                ->whereIn('code', [
                    'event_date',
                    'guest_count',
                    'venue_location',
                    'event_type',
                    'service_style',
                    'dietary_requirements',
                ])
                ->delete();

            $this->renameAttribute('title', 'Title');
            $this->renameAttribute('description', 'Description');
            $this->renameAttribute('lead_value', 'Lead Value');
            $this->renameAttribute('user_id', 'Sales Owner');
            $this->renameAttribute('expected_close_date', 'Expected Close Date');
        });
    }

    /**
     * Update an existing stage by code or name, merging duplicates without
     * losing leads when production data uses a different legacy code.
     */
    private function upsertPipelineStage(int $pipelineId, string $code, string $name, int $probability, int $sortOrder): void
    {
        $query = DB::table('lead_pipeline_stages')->where('lead_pipeline_id', $pipelineId);
        $stageByCode = (clone $query)->where('code', $code)->first();
        $stageByName = (clone $query)->where('name', $name)->first();

        if ($stageByCode && $stageByName && $stageByCode->id !== $stageByName->id) {
            DB::table('leads')
                ->where('lead_pipeline_stage_id', $stageByName->id)
                ->update(['lead_pipeline_stage_id' => $stageByCode->id]);

            DB::table('lead_pipeline_stages')->where('id', $stageByName->id)->delete();
        }

        $stageId = $stageByCode->id ?? $stageByName->id ?? null;
        $values = [
            'code'        => $code,
            'name'        => $name,
            'probability' => $probability,
            'sort_order'  => $sortOrder,
        ];

        if ($stageId) {
            DB::table('lead_pipeline_stages')->where('id', $stageId)->update($values);

            return;
        }

        DB::table('lead_pipeline_stages')->insert(array_merge($values, [
            'lead_pipeline_id' => $pipelineId,
        ]));
    }

    /**
     * Rename stock source/type records. If production already contains the
     * target name, reuse it and move lead references before removing the old
     * duplicate.
     */
    private function renameDefaults(string $table, array $names, ?string $leadForeignKey = null): void
    {
        foreach ($names as $from => $to) {
            $fromRecord = DB::table($table)->where('name', $from)->first();

            if (! $fromRecord) {
                continue;
            }

            $toRecord = DB::table($table)->where('name', $to)->first();

            if ($toRecord && $toRecord->id !== $fromRecord->id) {
                if ($leadForeignKey) {
                    DB::table('leads')
                        ->where($leadForeignKey, $fromRecord->id)
                        ->update([$leadForeignKey => $toRecord->id]);
                }

                DB::table($table)->where('id', $fromRecord->id)->delete();

                continue;
            }

            DB::table($table)->where('id', $fromRecord->id)->update([
                'name'       => $to,
                'updated_at' => now(),
            ]);
        }
    }

    private function renameLeadAttributes($now): void
    {
        foreach ([
            'title'               => 'Event / Inquiry Name',
            'description'         => 'Requirements & Notes',
            'lead_value'          => 'Estimated Contract Value',
            'user_id'             => 'Sales Representative',
            'expected_close_date' => 'Decision Deadline',
        ] as $code => $name) {
            DB::table('attributes')
                ->where('entity_type', 'leads')
                ->where('code', $code)
                ->update([
                    'name'       => $name,
                    'updated_at' => $now,
                ]);
        }
    }

    private function addCateringAttributes($now): void
    {
        $attributes = [
            'event_date'           => ['Event Date', 'date', null, 11],
            'guest_count'          => ['Estimated Guest Count', 'text', 'numeric', 12],
            'venue_location'       => ['Venue / Event Location', 'text', null, 13],
            'event_type'           => ['Event Type', 'select', null, 14],
            'service_style'        => ['Service Style', 'select', null, 15],
            'dietary_requirements' => ['Dietary Requirements / Allergies', 'textarea', null, 16],
        ];

        foreach ($attributes as $code => [$name, $type, $validation, $sortOrder]) {
            DB::table('attributes')->updateOrInsert(
                ['code' => $code, 'entity_type' => 'leads'],
                [
                    'name'            => $name,
                    'type'            => $type,
                    'lookup_type'     => null,
                    'validation'      => $validation,
                    'sort_order'      => $sortOrder,
                    'is_required'     => 0,
                    'is_unique'       => 0,
                    'quick_add'       => 1,
                    'is_user_defined' => 1,
                    'created_at'      => $now,
                    'updated_at'      => $now,
                ]
            );
        }

        $options = [
            'event_type'    => ['Corporate Event', 'Wedding', 'Private Party', 'Government / Institutional', 'Conference / Exhibition', 'Other'],
            'service_style' => ['Buffet', 'Plated Service', 'Canapes / Reception', 'Food Stations', 'Drop-off Catering', 'Staffed Catering'],
        ];

        foreach ($options as $code => $names) {
            $attributeId = DB::table('attributes')
                ->where('entity_type', 'leads')
                ->where('code', $code)
                ->value('id');

            foreach ($names as $sortOrder => $name) {
                DB::table('attribute_options')->updateOrInsert(
                    ['attribute_id' => $attributeId, 'name' => $name],
                    ['sort_order' => $sortOrder + 1]
                );
            }
        }
    }

    private function renameAttribute(string $code, string $name): void
    {
        DB::table('attributes')
            ->where('entity_type', 'leads')
            ->where('code', $code)
            ->update([
                'name'       => $name,
                'updated_at' => now(),
            ]);
    }
};
