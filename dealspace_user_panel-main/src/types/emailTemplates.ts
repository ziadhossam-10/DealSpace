export interface EmailTemplate {
  id: number
  name: string
  subject: string
  body: string
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

export interface CreateEmailTemplateRequest {
  name: string
  subject: string
  body: string
  is_shared: boolean
}

export interface UpdateEmailTemplateRequest {
  name: string
  subject: string
  body: string
  is_shared: boolean
}

export interface EmailTemplatesApiResponse {
  status: boolean
  message: string
  data: {
    items: EmailTemplate[]
    meta: {
      current_page: number
      per_page: number
      total: number
      last_page: number
    }
  }
}

export interface EmailTemplateApiResponse {
  status: boolean
  message: string
  data: EmailTemplate
}
