import type { User } from "./users"
import type { ApiResponse, PaginatedResponse } from "./meta"

export interface Pond {
  id: number
  name: string
  user_id: number
  created_at: string
  updated_at: string
  user?: User
  users?: User[]
}

export interface CreatePondRequest {
  name: string
  user_id: number
  user_ids: number[]
}

export interface UpdatePondRequest {
  name?: string
  user_id?: number
  user_ids?: number[]
  user_ids_to_delete?: number[]
}

export type PondsApiResponse = PaginatedResponse<Pond>
export type PondApiResponse = ApiResponse<Pond>
