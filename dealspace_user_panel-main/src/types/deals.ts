export interface DealType {
  id: number
  name: string
  sort?: number
}

export interface DealStage {
  id: number
  name: string
  color: string
  sort: number
  type_id: number
}

export interface Person {
  id: number
  name: string
  first_name: string
  last_name: string
  emails: Array<{
    id: number
    value: string
    type: string
    is_primary: boolean
  }>
  phones: Array<{
    id: number
    value: string
    type: string
    is_primary: boolean
  }>
  addresses: Array<{
    id: number
    street_address: string
    city: string
    state: string
    postal_code: string
    country: string
    type: string
    is_primary: boolean
  }>
}

export interface User {
  id: number
  name: string
  email: string
  avatar: string | null
  role: number
  role_name: string
}
export interface DealAttachment {
  id: number
  deal_id: number
  name: string
  path: string
  size: number
}

export interface Deal {
  id: number
  name: string
  description: string
  price: number
  projected_close_date: string
  order_weight: number
  commission_value: number
  agent_commission: number
  team_commission: number
  stage_id: number
  type_id: number
  created_at: string
  updated_at: string
  stage: DealStage
  type: DealType
  people: Person[]
  users: User[]
  attachments: DealAttachment[]
}

export interface CreateDealRequest {
  name: string
  stage_id: number
  type_id: number
  description: string
  people_ids: number[]
  users_ids: number[]
  price: number
  projected_close_date: string
  order_weight: number
  commission_value: number
  agent_commission: number
  team_commission: number
  attachments?: File[]
  attachments_to_delete?: number[]
}

export interface UpdateDealRequest extends Partial<CreateDealRequest> {}

export interface ApiResponse<T> {
  status: boolean
  message: string
  data: T
}

export interface DealsApiResponse {
  status: boolean
  message: string
  data: {
    items: Deal[]
    totals: {
      total_deals_count: number
      total_deals_price: number
    }
    meta: {
      current_page: number
      per_page: number
      total: number
      last_page: number
    }
  }
}

export interface DealTypesApiResponse {
  status: boolean
  message: string
  data: DealType[]
}

export interface DealStagesApiResponse {
  status: boolean
  message: string
  data: DealStage[]
}
