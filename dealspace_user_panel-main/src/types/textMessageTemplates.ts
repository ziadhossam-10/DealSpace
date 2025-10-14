export interface TextMessageTemplate {
  id: number
  name: string
  message: string
  is_shared: boolean
  user_id: number
  created_at: string
  updated_at: string
  user: {
    id: number
    name: string
    email: string
    avatar: string | null
    role: number
    role_name: string
    created_at: string
    updated_at: string
  }
}

export interface CreateTextMessageTemplateRequest {
  name: string
  message: string
  is_shared: boolean
}

export interface UpdateTextMessageTemplateRequest {
  name: string
  message: string
  is_shared: boolean
}

export interface TextMessageTemplatesApiResponse {
  status: boolean
  message: string
  data: {
    items: TextMessageTemplate[]
    meta: {
      current_page: number
      per_page: number
      total: number
      last_page: number
    }
  }
}

export interface TextMessageTemplateApiResponse {
  status: boolean
  message: string
  data: TextMessageTemplate
}
