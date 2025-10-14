<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActionPlan;
use App\Services\ActionPlans\ActionPlanService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;


class ActionPlanController extends Controller
{
    protected ActionPlanService $actionPlanService;

    public function __construct(ActionPlanService $actionPlanService)
    {
        $this->actionPlanService = $actionPlanService;
    }

    /**
     * Get tenant ID from authenticated user
     */
    protected function getTenantId()
    {
        // Use tenant() helper if available, otherwise get from auth user
        if (function_exists('tenant') && tenant()) {
            return tenant('id');
        }
        $user = Auth::user();

        return $user->tenant_id ?? null;
    }

    /**
     * Get all action plans
     */
    public function index(Request $request)
    {
        $tenantId = $this->getTenantId();
        
        $query = ActionPlan::with('steps')
            ->where('tenant_id', $tenantId);

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $actionPlans = $query->orderBy('name')->get();

        return response()->json([
            'data' => $actionPlans
        ]);
    }

    /**
     * Create new action plan
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'steps' => 'nullable|array',
            'steps.*.name' => 'required|string',
            'steps.*.description' => 'nullable|string',
            'steps.*.type' => 'required|in:task,email,sms,call,note',
            'steps.*.delay_days' => 'nullable|integer|min:0',
            'steps.*.delay_hours' => 'nullable|integer|min:0',
            'steps.*.metadata' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        $tenantId = $this->getTenantId();

        $actionPlan = ActionPlan::create([
            'tenant_id' => $tenantId,
            'name' => $request->name,
            'description' => $request->description,
            'is_active' => $request->is_active ?? true,
        ]);

        // Create steps
        if ($request->has('steps')) {
            foreach ($request->steps as $index => $stepData) {
                $actionPlan->steps()->create([
                    'name' => $stepData['name'],
                    'description' => $stepData['description'] ?? null,
                    'type' => $stepData['type'],
                    'delay_days' => $stepData['delay_days'] ?? 0,
                    'delay_hours' => $stepData['delay_hours'] ?? 0,
                    'order' => $index,
                    'metadata' => $stepData['metadata'] ?? null,
                ]);
            }
        }

        return response()->json([
            'data' => $actionPlan->load('steps'),
            'message' => 'Action plan created successfully'
        ], 201);
    }

    /**
     * Get single action plan
     */
    public function show(ActionPlan $actionPlan)
    {
        return response()->json([
            'data' => $actionPlan->load('steps')
        ]);
    }

    /**
     * Update action plan
     */
    public function update(Request $request, ActionPlan $actionPlan)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'steps' => 'nullable|array',
            'steps.*.name' => 'required|string',
            'steps.*.description' => 'nullable|string',
            'steps.*.type' => 'required|in:task,email,sms,call,note',
            'steps.*.delay_days' => 'nullable|integer|min:0',
            'steps.*.delay_hours' => 'nullable|integer|min:0',
            'steps.*.metadata' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        $actionPlan->update($request->except('steps'));

        // Update steps
        if ($request->has('steps')) {
            $actionPlan->steps()->delete();

            foreach ($request->steps as $index => $stepData) {
                $actionPlan->steps()->create([
                    'name' => $stepData['name'],
                    'description' => $stepData['description'] ?? null,
                    'type' => $stepData['type'],
                    'delay_days' => $stepData['delay_days'] ?? 0,
                    'delay_hours' => $stepData['delay_hours'] ?? 0,
                    'order' => $index,
                    'metadata' => $stepData['metadata'] ?? null,
                ]);
            }
        }

        return response()->json([
            'data' => $actionPlan->fresh()->load('steps'),
            'message' => 'Action plan updated successfully'
        ]);
    }

    /**
     * Delete action plan
     */
    public function destroy(ActionPlan $actionPlan)
    {
        $actionPlan->delete();

        return response()->json([
            'message' => 'Action plan deleted successfully'
        ]);
    }

    /**
     * Get action plan statistics
     */
    public function statistics(ActionPlan $actionPlan)
    {
        $stats = $this->actionPlanService->getStatistics($actionPlan);

        return response()->json([
            'data' => $stats
        ]);
    }

    /**
     * Assign action plan to a person
     */
    public function assignToPerson(Request $request, ActionPlan $actionPlan)
    {
        $validator = Validator::make($request->all(), [
            'person_id' => 'required|exists:people,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        $executions = $this->actionPlanService->assignToPerson(
            $actionPlan,
            $request->person_id
        );

        return response()->json([
            'data' => $executions,
            'message' => 'Action plan assigned successfully'
        ]);
    }
}