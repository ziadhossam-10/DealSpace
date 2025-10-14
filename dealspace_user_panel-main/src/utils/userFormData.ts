import { CreateUserRequest, UpdateUserRequest } from "../types/users";

export const createUserFormData = (data: CreateUserRequest): FormData => {
    const formData = new FormData();
    formData.append('name', data.name);
    formData.append('email', data.email);
    formData.append('password', data.password);
    formData.append('role', data.role.toString());
    
    if (data.avatar) {
      formData.append('avatar', data.avatar);
    }
    
    return formData;
  };
  
  export const updateUserFormData = (data: UpdateUserRequest): FormData => {
    const formData = new FormData();
    formData.append('name', data.name);
    formData.append('email', data.email);
    formData.append('role', data.role.toString());
    formData.append('_method', 'PUT');
    if (data.password) {
      formData.append('password', data.password);
    }
    
    if (data.avatar) {
      formData.append('avatar', data.avatar);
    }
    
    return formData;
  };