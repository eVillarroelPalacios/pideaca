import { API_BASE_URL, API_TIMEOUT_MS } from '@/config';
import { loadToken } from '@/lib/tokenStore';
import type {
  AuthResponse,
  MeResponse,
  RegisterPayload,
  SimpleResponse,
  TypesResponse,
} from '@/types/api';

/**
 * Error uniforme para toda la app.
 * status = 0 significa que ni siquiera llegamos al servidor (red/timeout).
 */
export class ApiError extends Error {
  readonly status: number;
  readonly code: string | null;
  readonly errors: Record<string, string[]> | null;

  constructor(
    message: string,
    status = 0,
    code: string | null = null,
    errors: Record<string, string[]> | null = null,
  ) {
    super(message);
    this.name = 'ApiError';
    this.status = status;
    this.code = code;
    this.errors = errors;
  }

  get isNetworkError(): boolean {
    return this.status === 0;
  }
}

type RequestOptions = {
  method?: 'GET' | 'POST' | 'PUT' | 'PATCH' | 'DELETE';
  body?: unknown;
  auth?: boolean;
};

async function request<T>(path: string, options: RequestOptions = {}): Promise<T> {
  const { method = 'GET', body, auth = true } = options;

  const headers: Record<string, string> = {
    Accept: 'application/json',
  };

  if (body !== undefined) {
    headers['Content-Type'] = 'application/json';
  }

  if (auth) {
    const token = await loadToken();
    if (token) {
      headers.Authorization = `Bearer ${token}`;
    }
  }

  // Evita que la app quede colgada si el servidor no responde.
  const controller = new AbortController();
  const timeout = setTimeout(() => controller.abort(), API_TIMEOUT_MS);

  let response: Response;

  try {
    response = await fetch(`${API_BASE_URL}${path}`, {
      method,
      headers,
      body: body === undefined ? undefined : JSON.stringify(body),
      signal: controller.signal,
    });
  } catch (error) {
    if (error instanceof Error && error.name === 'AbortError') {
      throw new ApiError('El servidor tardó demasiado en responder. Intentá de nuevo.', 0, 'TIMEOUT');
    }

    throw new ApiError(
      `No pudimos conectar con el servidor (${API_BASE_URL}). Revisá tu conexión y que el API esté encendido.`,
      0,
      'NETWORK_ERROR',
    );
  } finally {
    clearTimeout(timeout);
  }

  const raw = await response.text();
  let data: unknown = null;

  if (raw.length > 0) {
    try {
      data = JSON.parse(raw);
    } catch {
      data = null;
    }
  }

  if (!response.ok) {
    const payload = (data ?? {}) as {
      message?: unknown;
      code?: unknown;
      errors?: unknown;
    };

    throw new ApiError(
      typeof payload.message === 'string'
        ? payload.message
        : 'Ocurrió un error inesperado. Intentá de nuevo.',
      response.status,
      typeof payload.code === 'string' ? payload.code : null,
      (payload.errors as Record<string, string[]> | undefined) ?? null,
    );
  }

  return data as T;
}

export const api = {
  login(email: string, password: string): Promise<AuthResponse> {
    return request<AuthResponse>('/auth/login', {
      method: 'POST',
      auth: false,
      body: {
        email,
        password,
        device_name: 'PideAca Mobile',
      },
    });
  },

  register(payload: RegisterPayload): Promise<AuthResponse> {
    return request<AuthResponse>('/auth/register', {
      method: 'POST',
      auth: false,
      body: { ...payload, device_name: payload.device_name ?? 'PideAca Mobile' },
    });
  },

  types(): Promise<TypesResponse> {
    return request<TypesResponse>('/auth/types', { auth: false });
  },

  me(): Promise<MeResponse> {
    return request<MeResponse>('/auth/me');
  },

  logout(): Promise<SimpleResponse> {
    return request<SimpleResponse>('/auth/logout', { method: 'POST' });
  },

  deleteAccount(): Promise<SimpleResponse> {
    return request<SimpleResponse>('/auth/account', { method: 'DELETE' });
  },
};
