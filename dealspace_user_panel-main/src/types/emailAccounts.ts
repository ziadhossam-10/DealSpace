export interface EmailAccount {
  id: number
  provider: string
  email: string
  access_token: string
  refresh_token: string
  token_expires_at: string
  is_active: boolean
  created_at?: string
}
