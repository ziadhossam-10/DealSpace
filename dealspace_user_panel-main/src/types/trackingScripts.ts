export interface TrackingScript {
  id: number
  name: string
  description: string
  script_key: string
  tracking_code?: string
  is_active: boolean
  domain: string[]
  track_all_forms: boolean
  form_selectors: string[]
  field_mappings: {
    [key: string]: string[]
  }
  auto_lead_capture: boolean
  track_page_views: boolean
  track_utm_parameters: boolean
  custom_events: string[]
  custom_events_count?: number
  form_selectors_count?: number
  created_at: string
  updated_at: string
}

export interface CreateTrackingScriptRequest {
  name: string
  description: string
  domain: string[]
  track_all_forms: boolean
  form_selectors: string[]
  field_mappings: {
    [key: string]: string[]
  }
  auto_lead_capture: boolean
  track_page_views: boolean
  track_utm_parameters: boolean
  custom_events: string[]
}

export interface UpdateTrackingScriptRequest {
  id: number
  name: string
  description: string
  domain: string[]
  track_all_forms: boolean
  form_selectors: string[]
  field_mappings: {
    [key: string]: string[]
  }
  auto_lead_capture: boolean
  track_page_views: boolean
  track_utm_parameters: boolean
  custom_events: string[]
}

export interface TrackingScriptsApiResponse {
  status: boolean
  message: string
  data: {
    items: TrackingScript[]
    meta: {
      current_page: number
      per_page: number
      total: number
      last_page: number
    }
  }
}

export interface TrackingScriptApiResponse {
  status: boolean
  message: string
  data: TrackingScript
}

export interface TrackingCodeResponse {
  status: boolean
  message: string
  data: {
    tracking_code: string
    script_key: string
    instructions: {
      basic_setup: {
        title: string
        description: string
        code_example: string
      }
      custom_events: {
        title: string
        description: string
        code_example: string
      }
      property_tracking: {
        title: string
        description: string
        code_example: string
      }
      form_customization: {
        title: string
        description: string
        code_example: string
      }
    }
  }
}
