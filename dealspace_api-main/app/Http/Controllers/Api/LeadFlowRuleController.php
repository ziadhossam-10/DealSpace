<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LeadFlowRule;
use App\Models\Person;
use App\Services\LeadFlowRules\LeadFlowRuleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class LeadFlowRuleController extends Controller
{
    protected LeadFlowRuleService $ruleService;

    public function __construct(LeadFlowRuleService $ruleService)
    {
        $this->ruleService = $ruleService;
    }

    /**
     * Get all lead flow rules
     */
    public function index(Request $request)
    {
        $query = LeadFlowRule::with([
            'assignedAgent:id,name,email',
            'assignedLender:id,name,email',
            // 'actionPlan:id,name', // Removed - not implemented yet
            'group:id,name',
            'pond:id,name',
            'ruleConditions'
        ])->where('tenant_id', tenant('id'));

        if ($request->has('source_type') && $request->has('source_name')) {
            $query->forSource($request->source_type, $request->source_name);
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $rules = $query->ordered()->get();

        return response()->json([
            'data' => $rules
        ]);
    }

    /**
     * Create new lead flow rule
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'source_type' => 'nullable|string|max:100',
            'source_name' => 'nullable|string|max:100',
            'priority' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
            'match_type' => 'in:all,any',
            'assigned_agent_id' => 'nullable|exists:users,id',
            'assigned_lender_id' => 'nullable|exists:users,id',
            // 'action_plan_id' => 'nullable|exists:action_plans,id', // Removed
            'group_id' => 'nullable|exists:groups,id',
            'pond_id' => 'nullable|exists:ponds,id',
            'conditions' => 'nullable|array',
            'conditions.*.field' => 'required|string',
            'conditions.*.operator' => 'required|string',
            'conditions.*.value' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        $rule = LeadFlowRule::create([
            'tenant_id' => tenant('id'),
            'name' => $request->name,
            'source_type' => $request->source_type,
            'source_name' => $request->source_name,
            'priority' => $request->priority ?? 0,
            'is_active' => $request->is_active ?? true,
            'is_default' => $request->is_default ?? false,
            'match_type' => $request->match_type ?? 'all',
            'assigned_agent_id' => $request->assigned_agent_id,
            'assigned_lender_id' => $request->assigned_lender_id,
            // 'action_plan_id' => $request->action_plan_id, // Removed
            'group_id' => $request->group_id,
            'pond_id' => $request->pond_id,
        ]);

        // Create conditions
        if ($request->has('conditions')) {
            foreach ($request->conditions as $index => $condition) {
                $rule->ruleConditions()->create([
                    'field' => $condition['field'],
                    'operator' => $condition['operator'],
                    'value' => $condition['value'],
                    'order' => $index,
                ]);
            }
        }

        return response()->json([
            'data' => $rule->load('ruleConditions', 'assignedAgent', 'assignedLender', 'group', 'pond'),
            'message' => 'Lead flow rule created successfully'
        ], 201);
    }

    /**
     * Update lead flow rule
     */
    public function update(Request $request, LeadFlowRule $leadFlowRule)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'string|max:255',
            'source_type' => 'nullable|string|max:100',
            'source_name' => 'nullable|string|max:100',
            'priority' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
            'match_type' => 'in:all,any',
            'assigned_agent_id' => 'nullable|exists:users,id',
            'assigned_lender_id' => 'nullable|exists:users,id',
            // 'action_plan_id' => 'nullable|exists:action_plans,id', // Removed
            'group_id' => 'nullable|exists:groups,id',
            'pond_id' => 'nullable|exists:ponds,id',
            'conditions' => 'nullable|array',
            'conditions.*.field' => 'required|string',
            'conditions.*.operator' => 'required|string',
            'conditions.*.value' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        $leadFlowRule->update($request->except('conditions'));

        // Update conditions
        if ($request->has('conditions')) {
            $leadFlowRule->ruleConditions()->delete();
            
            foreach ($request->conditions as $index => $condition) {
                $leadFlowRule->ruleConditions()->create([
                    'field' => $condition['field'],
                    'operator' => $condition['operator'],
                    'value' => $condition['value'],
                    'order' => $index,
                ]);
            }
        }

        return response()->json([
            'data' => $leadFlowRule->fresh()->load('ruleConditions', 'assignedAgent', 'assignedLender', 'group', 'pond'),
            'message' => 'Lead flow rule updated successfully'
        ]);
    }

    /**
     * Delete lead flow rule
     */
    public function destroy(LeadFlowRule $leadFlowRule)
    {
        $leadFlowRule->delete();

        return response()->json([
            'message' => 'Lead flow rule deleted successfully'
        ]);
    }

    /**
     * Reorder rules
     */
    public function reorder(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'rules' => 'required|array',
            'rules.*.id' => 'required|exists:lead_flow_rules,id',
            'rules.*.priority' => 'required|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        foreach ($request->rules as $ruleData) {
            LeadFlowRule::where('id', $ruleData['id'])
                ->where('tenant_id', tenant('id'))
                ->update(['priority' => $ruleData['priority']]);
        }

        return response()->json([
            'message' => 'Rules reordered successfully'
        ]);
    }

    /**
     * Copy rules from another source
     */
    public function copyFromSource(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'from_source_type' => 'required|string',
            'from_source_name' => 'required|string',
            'to_source_type' => 'required|string',
            'to_source_name' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        $count = $this->ruleService->copyRulesFromSource(
            $request->from_source_type,
            $request->from_source_name,
            $request->to_source_type,
            $request->to_source_name
        );

        return response()->json([
            'message' => "{$count} rules copied successfully"
        ]);
    }

    /**
     * Test rule against a lead
     */
    public function testRule(Request $request, LeadFlowRule $leadFlowRule)
    {
        $validator = Validator::make($request->all(), [
            'person_id' => 'required|exists:people,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        $person = Person::findOrFail($request->person_id);
        
        // Test without applying - Use the service method
        $matches = $this->ruleService->evaluateRule($person, $leadFlowRule);

        return response()->json([
            'matches' => $matches,
            'rule' => $leadFlowRule->load('ruleConditions'),
            'person' => $person
        ]);
    }

    /**
     * Get rule statistics
     */
    public function statistics(Request $request)
    {
        $query = LeadFlowRule::where('tenant_id', tenant('id'));

        if ($request->has('source_type') && $request->has('source_name')) {
            $query->forSource($request->source_type, $request->source_name);
        }

        $rules = $query->get();

        $stats = [
            'total_rules' => $rules->count(),
            'active_rules' => $rules->where('is_active', true)->count(),
            'total_leads_processed' => $rules->sum('leads_count'),
            'rules_by_type' => $rules->groupBy('source_type')->map->count(),
        ];

        return response()->json([
            'data' => $stats
        ]);
    }
}