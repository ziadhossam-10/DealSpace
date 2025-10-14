
export interface Stage {
  id: number
  name: string
  description: string
  created_at: string
  updated_at: string
}

// Define request types
export interface CreateStageRequest {
  name: string
  description: string
}

export interface UpdateStageRequest {
  id: number
  name: string
  description: string
}
