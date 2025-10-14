export interface ApiKey {
  id: number
  name: string
  allowed_domains: string[]
  last_used_at: string | null
  is_active: boolean
  created_at: string
  updated_at: string
  key?: string // Only returned on create/regenerate
}

export interface CreateApiKeyRequest {
  name: string
  allowed_domains: string[]
}

export interface UpdateApiKeyRequest {
  name: string
  allowed_domains: string[]
}

export interface ApiKeysApiResponse {
  status: boolean
  message: string
  data: {
    items: ApiKey[]
    meta: {
      current_page: number
      per_page: number
      total: number
      last_page: number
    }
  }
}

export interface ApiKeyApiResponse {
  status: boolean
  message: string
  data: ApiKey
}
