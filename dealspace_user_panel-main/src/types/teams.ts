export interface User {
  id: number
  name: string
  email: string
  avatar?: string | null
  role: number
  role_name: string
  created_at: string
  updated_at: string
}

export interface Team {
  id: number
  name: string
  users: User[]
  leaders: User[]
  created_at: string
  updated_at: string
}

export interface CreateTeamRequest {
  name: string
  userIds: number[]
  leaderIds: number[]
}

export interface UpdateTeamRequest {
  name?: string
  userIds?: number[]
  leaderIds?: number[]
  userIdsToDelete?: number[]
  leaderIdsToDelete?: number[]
}

export interface TeamsApiResponse {
  status: boolean
  message: string
  data: {
    items: Team[]
    meta: {
      current_page: number
      per_page: number
      total: number
      last_page: number
    }
  }
}

export interface TeamApiResponse {
  status: boolean
  message: string
  data: Team
}
