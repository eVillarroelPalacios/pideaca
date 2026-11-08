// Tipos que espejan las respuestas del API /api/mobile/v1.
// Si el backend cambia un campo, se actualiza acá y TypeScript avisa.

export type User = {
  id: number;
  name: string;
  email: string;
  phone: string | null;
  role: string | null;
  is_admin: boolean;
  is_driver: boolean;
  status: string | null;
  vehicle_type: string | null;
  vehicle_plate: string | null;
};

export type AuthResponse = {
  success: boolean;
  message: string;
  token: string;
  token_type: 'Bearer';
  user: User;
};

export type MeResponse = {
  success: boolean;
  user: User;
};

export type TypeUser = {
  id: number;
  description: string;
};

export type TypesResponse = {
  success: boolean;
  data: TypeUser[];
};

export type SimpleResponse = {
  success: boolean;
  message: string;
};

export type RegisterPayload = {
  name: string;
  email: string;
  type_user_id: number;
  password: string;
  password_confirmation: string;
  device_name?: string;
};
