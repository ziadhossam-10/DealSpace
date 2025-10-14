export interface Email {
  id: number
  subject: string
  body?: string
  body_html?: string
  from_email: string
  from_name?: string
  to_email: string
  to_name?: string
  cc?: string
  bcc?: string
  reply_to?: string
  message_id?: string
  thread_id?: string
  is_incoming: boolean
  is_read?: boolean
  is_starred?: boolean
  is_archived?: boolean
  is_deleted?: boolean
  is_spam?: boolean
  is_processed?: boolean
  headers?: any
  attachments?: any
  sent_at?: string
  received_at?: string
  created_at: string
  updated_at: string
  person_id: number
  user_id?: number
  email_account_id: number
}

export interface EmailAccount {
  id: number
  provider: string
  email: string
  is_active: boolean
  token_expires_at: string
  is_token_expired: boolean
  tenant_id: string
  created_at: string
  updated_at: string
}

export interface SendEmailRequest {
  account_id: number
  person_id: number
  to_email: string
  subject: string
  body: string
  body_html: string
}

export interface EmailsApiResponse {
  status: boolean
  message: string
  data: {
    items: Email[]
    meta: {
      current_page: number
      per_page: number
      total: number
      last_page: number
    }
  }
}

export interface EmailAccountsApiResponse {
  status: boolean
  message: string
  data: EmailAccount[]
}
