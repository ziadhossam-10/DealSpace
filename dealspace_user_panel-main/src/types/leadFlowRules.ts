export interface LeadFlowRuleCondition {
  id?: number;
  field: string;
  operator: string;
  value: string | number | string[];
  order: number;
}

export interface LeadFlowRule {
  id: number;
  tenant_id: number;
  name: string;
  source_type?: string;
  source_name?: string;
  priority: number;
  is_active: boolean;
  is_default: boolean;
  match_type: 'all' | 'any';
  assigned_agent_id?: number;
  assigned_lender_id?: number;
  action_plan_id?: number;
  group_id?: number;
  pond_id?: number;
  leads_count: number;
  last_lead_at?: string;
  created_at: string;
  updated_at: string;
  assigned_agent?: {
    id: number;
    name: string;
    email: string;
  };
  assigned_lender?: {
    id: number;
    name: string;
    email: string;
  };
  action_plan?: {
    id: number;
    name: string;
  };
  group?: {
    id: number;
    name: string;
  };
  pond?: {
    id: number;
    name: string;
  };
  rule_conditions?: LeadFlowRuleCondition[];
}

export interface LeadFlowRuleFormData {
  name: string;
  source_type?: string;
  source_name?: string;
  priority?: number;
  is_active?: boolean;
  is_default?: boolean;
  match_type?: 'all' | 'any';
  assigned_agent_id?: number;
  assigned_lender_id?: number;
  action_plan_id?: number;
  group_id?: number;
  pond_id?: number;
  conditions?: LeadFlowRuleCondition[];
}

export const FIELD_OPTIONS = [
  { value: 'first_name', label: 'First Name' },
  { value: 'last_name', label: 'Last Name' },
  { value: 'emails.0.email', label: 'Email' },
  { value: 'phones.0.phone', label: 'Phone' },
  { value: 'price', label: 'Price' },
  { value: 'location', label: 'Location' },
  { value: 'city', label: 'City' },
  { value: 'state', label: 'State' },
  { value: 'zip_code', label: 'Zip Code' },
  { value: 'status', label: 'Status' },
];

export const OPERATOR_OPTIONS = [
  { value: 'equals', label: 'Equals' },
  { value: 'not_equals', label: 'Not Equals' },
  { value: 'greater_than', label: 'Greater Than' },
  { value: 'greater_than_or_equal', label: 'Greater Than or Equal' },
  { value: 'less_than', label: 'Less Than' },
  { value: 'less_than_or_equal', label: 'Less Than or Equal' },
  { value: 'contains', label: 'Contains' },
  { value: 'not_contains', label: 'Does Not Contain' },
  { value: 'starts_with', label: 'Starts With' },
  { value: 'ends_with', label: 'Ends With' },
  { value: 'is_empty', label: 'Is Empty' },
  { value: 'is_not_empty', label: 'Is Not Empty' },
];