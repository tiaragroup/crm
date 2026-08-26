<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Separate account prospecting from event-specific catering inquiries.
     *
     * This migration is intentionally additive and rerunnable. Existing leads
     * remain attached to the event pipeline and no historical product data is
     * removed.
     */
    public function up(): void
    {
        DB::transaction(function () {
            $now = now();

            $eventPipeline = DB::table('lead_pipelines')
                ->whereIn('name', ['Event Sales Pipeline', 'Catering Sales Pipeline'])
                ->orderByRaw("name = 'Event Sales Pipeline' desc")
                ->first()
                ?? DB::table('lead_pipelines')->where('is_default', 1)->first()
                ?? DB::table('lead_pipelines')->first();

            if (! $eventPipeline) {
                $eventPipelineId = DB::table('lead_pipelines')->insertGetId([
                    'name'         => 'Event Sales Pipeline',
                    'is_default'   => 1,
                    'rotten_days'  => 30,
                    'created_at'   => $now,
                    'updated_at'   => $now,
                ]);
            } else {
                $eventPipelineId = $eventPipeline->id;

                if ($eventPipeline->name !== 'Event Sales Pipeline'
                    && ! DB::table('lead_pipelines')->where('name', 'Event Sales Pipeline')->exists()
                ) {
                    DB::table('lead_pipelines')->where('id', $eventPipelineId)->update([
                        'name'       => 'Event Sales Pipeline',
                        'updated_at' => $now,
                    ]);
                }
            }

            DB::table('lead_pipelines')->update(['is_default' => 0]);
            DB::table('lead_pipelines')->where('id', $eventPipelineId)->update([
                'is_default' => 1,
                'updated_at' => $now,
            ]);

            $eventStages = [
                'new'           => ['New Inquiry', 10, 1],
                'follow-up'     => ['Contacted', 20, 2],
                'prospect'      => ['Qualified / Discovery', 35, 3],
                'proposal-prep' => ['Proposal in Preparation', 50, 4],
                'proposal-sent' => ['Proposal Sent', 65, 5],
                'negotiation'   => ['Negotiation / Revision', 80, 6],
                'won'           => ['Confirmed / Won', 100, 7],
                'lost'          => ['Lost', 0, 8],
            ];

            foreach ($eventStages as $code => [$name, $probability, $sortOrder]) {
                $this->upsertStage($eventPipelineId, $code, $name, $probability, $sortOrder);
            }

            $qualifiedStageId = DB::table('lead_pipeline_stages')
                ->where('lead_pipeline_id', $eventPipelineId)
                ->where('code', 'prospect')
                ->value('id');

            $siteVisitStageIds = DB::table('lead_pipeline_stages')
                ->where('lead_pipeline_id', $eventPipelineId)
                ->where('code', 'site-visit')
                ->pluck('id');

            if ($qualifiedStageId && $siteVisitStageIds->isNotEmpty()) {
                DB::table('leads')
                    ->whereIn('lead_pipeline_stage_id', $siteVisitStageIds)
                    ->update(['lead_pipeline_stage_id' => $qualifiedStageId]);

                DB::table('lead_pipeline_stages')->whereIn('id', $siteVisitStageIds)->delete();
            }

            $prospectPipelineId = DB::table('lead_pipelines')
                ->where('name', 'Account Prospecting Pipeline')
                ->value('id');

            if (! $prospectPipelineId) {
                $prospectPipelineId = DB::table('lead_pipelines')->insertGetId([
                    'name'         => 'Account Prospecting Pipeline',
                    'is_default'   => 0,
                    'rotten_days'  => 30,
                    'created_at'   => $now,
                    'updated_at'   => $now,
                ]);
            }

            $prospectStages = [
                'new'       => ['New Prospect', 5, 1],
                'assigned'  => ['Assigned', 10, 2],
                'contacted' => ['Contacted', 20, 3],
                'visit'     => ['Meeting / Visit Scheduled', 35, 4],
                'qualified' => ['Qualified Account', 55, 5],
                'nurture'   => ['Nurture / Follow-up', 30, 6],
                'converted' => ['Event Opportunity Created', 100, 7],
                'lost'      => ['Disqualified / Lost', 0, 8],
            ];

            foreach ($prospectStages as $code => [$name, $probability, $sortOrder]) {
                $this->upsertStage($prospectPipelineId, $code, $name, $probability, $sortOrder);
            }

            $attributes = [
                'opportunity_type'           => ['Opportunity Type', 'select', null, 10, 1],
                'interested_services'        => ['Interested Services', 'multiselect', null, 11, 0],
                'business_category'          => ['Business Category', 'select', null, 20, 0],
                'catering_frequency'         => ['Estimated Catering Frequency', 'select', null, 21, 0],
                'potential_guest_volume'     => ['Potential Guests / Order Size', 'text', 'numeric', 22, 0],
                'next_follow_up_date'        => ['Next Follow-up Date', 'date', null, 23, 0],
                'budget_range'               => ['Estimated Budget Range', 'select', null, 30, 0],
                'date_flexibility'           => ['Event Date Flexibility', 'select', null, 31, 0],
            ];

            foreach ($attributes as $code => [$name, $type, $validation, $sortOrder, $required]) {
                DB::table('attributes')->updateOrInsert(
                    ['code' => $code, 'entity_type' => 'leads'],
                    [
                        'name'            => $name,
                        'type'            => $type,
                        'lookup_type'     => null,
                        'validation'      => $validation,
                        'sort_order'      => $sortOrder,
                        'is_required'     => $required,
                        'is_unique'       => 0,
                        'quick_add'       => 1,
                        'is_user_defined' => 1,
                        'created_at'      => $now,
                        'updated_at'      => $now,
                    ]
                );
            }

            $optionSets = [
                'opportunity_type' => ['Event Inquiry', 'Account Prospect'],
                'interested_services' => [
                    'Full-service Catering',
                    'Drop-off Catering',
                    'Corporate Meals',
                    'Buffet',
                    'Plated Service',
                    'Canapes / Reception',
                    'Beverage Service',
                    'Staffing',
                    'Equipment / Rentals',
                    'Venue Required',
                    'Not Decided',
                ],
                'business_category' => [
                    'Corporate Office',
                    'Government / Institution',
                    'Event Planner / Agency',
                    'Venue / Hotel',
                    'School / University',
                    'Healthcare',
                    'Private Client',
                    'Other',
                ],
                'catering_frequency' => ['One-time', 'Weekly', 'Monthly', 'Quarterly', 'Seasonal / Occasional', 'Unknown'],
                'budget_range' => ['Not Known', 'Under SAR 10,000', 'SAR 10,000 - 25,000', 'SAR 25,000 - 50,000', 'SAR 50,000 - 100,000', 'Above SAR 100,000'],
                'date_flexibility' => ['Fixed Date', 'Flexible by a Few Days', 'Month Only / Not Confirmed', 'Date Unknown'],
            ];

            foreach ($optionSets as $attributeCode => $options) {
                $attributeId = DB::table('attributes')
                    ->where('entity_type', 'leads')
                    ->where('code', $attributeCode)
                    ->value('id');

                foreach ($options as $index => $name) {
                    DB::table('attribute_options')->updateOrInsert(
                        ['attribute_id' => $attributeId, 'name' => $name],
                        ['sort_order' => $index + 1]
                    );
                }
            }

            DB::table('attributes')
                ->where('entity_type', 'leads')
                ->where('code', 'title')
                ->update(['name' => 'Opportunity Name', 'updated_at' => $now]);

            DB::table('attributes')
                ->where('entity_type', 'leads')
                ->where('code', 'description')
                ->update(['name' => 'Requirements / Sales Notes', 'updated_at' => $now]);

            DB::table('attributes')
                ->where('entity_type', 'leads')
                ->where('code', 'lead_value')
                ->update(['name' => 'Estimated Opportunity Value', 'updated_at' => $now]);

            DB::table('attributes')
                ->where('entity_type', 'leads')
                ->where('code', 'lead_type_id')
                ->update(['name' => 'Client Relationship', 'updated_at' => $now]);

            $opportunityAttributeId = DB::table('attributes')
                ->where('entity_type', 'leads')
                ->where('code', 'opportunity_type')
                ->value('id');

            $eventOptionId = DB::table('attribute_options')
                ->where('attribute_id', $opportunityAttributeId)
                ->where('name', 'Event Inquiry')
                ->value('id');

            DB::table('leads')->orderBy('id')->pluck('id')->each(function ($leadId) use ($opportunityAttributeId, $eventOptionId) {
                DB::table('attribute_values')->insertOrIgnore([
                    'entity_type'  => 'leads',
                    'entity_id'    => $leadId,
                    'attribute_id' => $opportunityAttributeId,
                    'integer_value'=> $eventOptionId,
                    'unique_id'    => $leadId.'|'.$opportunityAttributeId,
                ]);
            });
        });
    }

    /**
     * Remove only the additive configuration and return prospect leads to the
     * event pipeline before removing the prospect pipeline.
     */
    public function down(): void
    {
        DB::transaction(function () {
            $eventPipeline = DB::table('lead_pipelines')->where('name', 'Event Sales Pipeline')->first();
            $prospectPipeline = DB::table('lead_pipelines')->where('name', 'Account Prospecting Pipeline')->first();

            if ($eventPipeline && $prospectPipeline) {
                $eventStageId = DB::table('lead_pipeline_stages')
                    ->where('lead_pipeline_id', $eventPipeline->id)
                    ->orderBy('sort_order')
                    ->value('id');

                DB::table('leads')->where('lead_pipeline_id', $prospectPipeline->id)->update([
                    'lead_pipeline_id'       => $eventPipeline->id,
                    'lead_pipeline_stage_id' => $eventStageId,
                ]);

                DB::table('lead_pipelines')->where('id', $prospectPipeline->id)->delete();
            }

            DB::table('attributes')
                ->where('entity_type', 'leads')
                ->whereIn('code', [
                    'opportunity_type',
                    'interested_services',
                    'business_category',
                    'catering_frequency',
                    'potential_guest_volume',
                    'next_follow_up_date',
                    'budget_range',
                    'date_flexibility',
                ])
                ->delete();
        });
    }

    private function upsertStage(int $pipelineId, string $code, string $name, int $probability, int $sortOrder): void
    {
        $query = DB::table('lead_pipeline_stages')->where('lead_pipeline_id', $pipelineId);
        $byCode = (clone $query)->where('code', $code)->first();
        $byName = (clone $query)->where('name', $name)->first();

        if ($byCode && $byName && $byCode->id !== $byName->id) {
            DB::table('leads')->where('lead_pipeline_stage_id', $byName->id)->update([
                'lead_pipeline_stage_id' => $byCode->id,
            ]);

            DB::table('lead_pipeline_stages')->where('id', $byName->id)->delete();
        }

        $stageId = $byCode->id ?? $byName->id ?? null;
        $values = compact('code', 'name', 'probability', 'sortOrder');
        $values['sort_order'] = $values['sortOrder'];
        unset($values['sortOrder']);

        if ($stageId) {
            DB::table('lead_pipeline_stages')->where('id', $stageId)->update($values);

            return;
        }

        DB::table('lead_pipeline_stages')->insert(array_merge($values, [
            'lead_pipeline_id' => $pipelineId,
        ]));
    }
};
