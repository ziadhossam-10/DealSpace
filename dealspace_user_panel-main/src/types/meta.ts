// API Response Types
export interface ApiResponse<T> {
  status: boolean
  message: string
  data: T
}

// For paginated responses
type Meta = {
  current_page: number
  per_page: number
  total: number
  last_page: number
}
export interface PaginatedResponse<T> {
  status: boolean
  message: string
  data: {
    items: T[]
    meta: Meta
  }
}

export interface ImportResponseData {
  total: number
  created: number
  failed: number
  errors: {
    row: number
    error: string
  }[]
}
