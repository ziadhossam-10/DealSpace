import { Pond } from "./ponds"
import { User } from "./users"

// Update the Group interface to include claim window and default assignment fields
export interface Group {
  id: number
  name: string
  distribution: number
  distribution_name: string
  type: number
  type_name: string
  users_count?: number
  created_at?: string
  updated_at?: string
  users?: User[]
  claim_window?: number
  default_user_id?: number
  default_pond_id?: number
  default_group_id?: number
  defaultGroup?: Group
  defaultPond?: Pond
  defaultUser?: User
}

// Update the CreateGroupRequest interface to include the new optional fields
export interface CreateGroupRequest {
  name: string
  type: number
  distribution: number
  user_ids: number[]
  claim_window?: number
  default_user_id?: number
  default_pond_id?: number
  default_group_id?: number
}

// Update the UpdateGroupRequest interface to include the new optional fields
export interface UpdateGroupRequest {
  name?: string
  type?: number
  distribution?: number
  user_ids?: number[]
  user_ids_to_delete?: number[]
  claim_window?: number
  default_user_id?: number
  default_pond_id?: number
  default_group_id?: number
}
export interface GroupsApiResponse {
  status: boolean
  message: string
  data: {
    items: Group[]
    meta: {
      current_page: number
      per_page: number
      total: number
      last_page: number
    }
  }
}

export interface GroupApiResponse {
  status: boolean
  message: string
  data: Group
}
