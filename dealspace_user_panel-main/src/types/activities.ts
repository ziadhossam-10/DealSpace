export interface Activity {
  id: number
  type: string
  title: string
  description: string
  metadata: {
    [key: string]: any
  }
  created_at: string
  user: {
    id: number
    name: string
  }
}

export interface ActivitiesApiResponse {
  status: boolean
  message: string
  data: {
    items: Activity[]
    meta: {
      current_page: number
      per_page: number
      total: number
      last_page: number
    }
  }
}
