// src/utils/roles.ts
export const RoleEnum = {
  OWNER: 0,
  ADMIN: 1,
  AGENT: 2,
  LENDER: 3,
  ISAs: 4,
} as const;

export const isAdminOrOwnerRole = (role?: number | null) => {
  const num = Number(role);
  return num === RoleEnum.OWNER || num === RoleEnum.ADMIN;
};

export const isOwnerRole = (role?: number | null) => {
  return Number(role) === RoleEnum.OWNER;
};

export const isAgentRole = (role?: number | null) => {
  return Number(role) === RoleEnum.AGENT;
};

export const isLenderRole = (role?: number | null) => {
  return Number(role) === RoleEnum.LENDER;
};

export const isISAsRole = (role?: number | null) => {
  return Number(role) === RoleEnum.ISAs;
};

export const hasRole = (userRole?: number | null, allowedRoles?: number[]) => {
  if (userRole === null || userRole === undefined) return false;
  return allowedRoles?.includes(userRole) ?? false;
};
