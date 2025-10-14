export interface User {
    id: number
    name: string
    email: string
    role: number
    role_name?: string
    avatar?: string
    created_at?: string
    updated_at?: string
  }
  
  export interface CreateUserRequest {
    name: string
    email: string
    password: string
    role: number
    avatar?: File
  }
  
  export interface UpdateUserRequest {
    id: number
    name: string
    email: string
    password?: string
    role: number
    avatar?: File
  }
  