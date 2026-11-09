import { API_BASE_URL, API_TIMEOUT_MS } from '@/config';
import { loadToken } from '@/lib/tokenStore';
import type {
  AccountUserResponse,
  AccountUsersResponse,
  AddressPayload,
  AuthResponse,
  ContactInquiriesParams,
  ContactInquiriesResponse,
  ContactInquiryResponse,
  ContactInquiryStatus,
  CountriesResponse,
  GroupStatusResponse,
  GroupStatusesResponse,
  GroupPayload,
  GroupResponse,
  GroupsResponse,
  MenuResponse,
  MeResponse,
  ModulePayload,
  ModuleResponse,
  ModulesResponse,
  NotificationResponse,
  NotificationsResponse,
  PagePayload,
  PageResponse,
  PagesResponse,
  ProfileAddressResponse,
  ProfileImageResponse,
  ProfilePasswordResponse,
  ProfileProviderResponse,
  ProfileResponse,
  ProfileServiceResponse,
  ProfileServiceTypesResponse,
  ProfileServicesResponse,
  ProfileSubgroupsResponse,
  ProfileUserResponse,
  ProfileVehicleResponse,
  ProviderDetailResponse,
  ProviderOrdersResponse,
  ProviderPayload,
  ProvidersParams,
  ProvidersResponse,
  RegionsResponse,
  RegisterPayload,
  ServiceOrderResponse,
  ServiceOrdersParams,
  ServiceOrdersResponse,
  ServiceSubgroupsResponse,
  SimpleResponse,
  ToggleSubgroupResponse,
  TrustedDriverCandidate,
  TrustedDriversResponse,
  TrustedDriversSettingsResponse,
  TypesResponse,
  UpdateContactInquiryStatusResponse,
  UserStatusResponse,
  UserStatusesResponse,
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

  if (body !== undefined && !(body instanceof FormData)) {
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
      // FormData (fotos) viaja como multipart; fetch pone el boundary solo.
      body: body === undefined ? undefined : body instanceof FormData ? body : JSON.stringify(body),
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

  providers(params: ProvidersParams = {}): Promise<ProvidersResponse> {
    const query = new URLSearchParams();

    if (params.search?.trim()) {
      query.set('search', params.search.trim());
    }

    if (params.type) {
      query.set('type', params.type);
    }

    if (params.favorite) {
      query.set('favorite', '1');
    }

    if (params.per_page) {
      query.set('per_page', String(params.per_page));
    }

    if (params.page) {
      query.set('page', String(params.page));
    }

    const suffix = query.size > 0 ? `?${query.toString()}` : '';

    return request<ProvidersResponse>(`/providers${suffix}`);
  },

  provider(id: number): Promise<ProviderDetailResponse> {
    return request<ProviderDetailResponse>(`/providers/${id}`);
  },

  serviceSubgroups(): Promise<ServiceSubgroupsResponse> {
    return request<ServiceSubgroupsResponse>('/service-subgroups');
  },

  serviceOrders(params: ServiceOrdersParams = {}): Promise<ServiceOrdersResponse> {
    const query = new URLSearchParams();

    if (params.status) {
      query.set('status', params.status);
    }

    if (params.order_type) {
      query.set('order_type', params.order_type);
    }

    if (params.per_page) {
      query.set('per_page', String(params.per_page));
    }

    if (params.page) {
      query.set('page', String(params.page));
    }

    const suffix = query.size > 0 ? `?${query.toString()}` : '';

    return request<ServiceOrdersResponse>(`/service-orders${suffix}`);
  },

  serviceOrder(id: number): Promise<ServiceOrderResponse> {
    return request<ServiceOrderResponse>(`/service-orders/${id}`);
  },

  createEmergency(form: FormData): Promise<ServiceOrderResponse> {
    return request<ServiceOrderResponse>('/service-orders/emergency', {
      method: 'POST',
      body: form,
    });
  },

  createScheduled(payload: Record<string, unknown>): Promise<ServiceOrderResponse> {
    return request<ServiceOrderResponse>('/service-orders/scheduled', {
      method: 'POST',
      body: payload,
    });
  },

  createQuote(form: FormData): Promise<ServiceOrderResponse> {
    return request<ServiceOrderResponse>('/service-orders/quote', {
      method: 'POST',
      body: form,
    });
  },

  cancelServiceOrder(id: number): Promise<ServiceOrderResponse> {
    return request<ServiceOrderResponse>(`/service-orders/${id}/cancel`, {
      method: 'POST',
    });
  },

  acceptQuote(id: number, quoteId?: number): Promise<ServiceOrderResponse> {
    return request<ServiceOrderResponse>(`/service-orders/${id}/accept-quote`, {
      method: 'POST',
      body: quoteId ? { quote: quoteId } : {},
    });
  },

  rejectQuote(id: number, quoteId?: number): Promise<ServiceOrderResponse> {
    return request<ServiceOrderResponse>(`/service-orders/${id}/reject-quote`, {
      method: 'POST',
      body: quoteId ? { quote: quoteId } : {},
    });
  },

  rateServiceOrder(id: number, stars: number, comment?: string): Promise<ServiceOrderResponse> {
    return request<ServiceOrderResponse>(`/service-orders/${id}/rate`, {
      method: 'POST',
      body: { stars, ...(comment?.trim() ? { comment: comment.trim() } : {}) },
    });
  },

  registerPushToken(token: string, platform: 'android' | 'ios'): Promise<SimpleResponse> {
    return request<SimpleResponse>('/push-tokens', {
      method: 'POST',
      body: { token, platform },
    });
  },

  unregisterPushToken(token: string): Promise<SimpleResponse> {
    return request<SimpleResponse>('/push-tokens', {
      method: 'DELETE',
      body: { token },
    });
  },

  menu(): Promise<MenuResponse> {
    return request<MenuResponse>('/menu');
  },

  providerOrders(params: ServiceOrdersParams & { mine?: boolean } = {}): Promise<ProviderOrdersResponse> {
    const query = new URLSearchParams();

    if (params.status) {
      query.set('status', params.status);
    }

    if (params.order_type) {
      query.set('order_type', params.order_type);
    }

    if (params.per_page) {
      query.set('per_page', String(params.per_page));
    }

    if (params.page) {
      query.set('page', String(params.page));
    }

    if (params.mine) {
      query.set('mine', '1');
    }

    const suffix = query.size > 0 ? `?${query.toString()}` : '';

    return request<ProviderOrdersResponse>(`/provider/orders${suffix}`);
  },

  providerOrder(id: number): Promise<ServiceOrderResponse> {
    return request<ServiceOrderResponse>(`/provider/orders/${id}`);
  },

  acceptProviderOrder(id: number): Promise<ServiceOrderResponse> {
    return request<ServiceOrderResponse>(`/provider/orders/${id}/accept`, {
      method: 'POST',
    });
  },

  dispatchProviderOrder(id: number): Promise<ServiceOrderResponse> {
    return request<ServiceOrderResponse>(`/provider/orders/${id}/dispatch`, {
      method: 'POST',
    });
  },

  estimateProviderOrder(id: number, additionalCost: number, details?: string): Promise<ServiceOrderResponse> {
    return request<ServiceOrderResponse>(`/provider/orders/${id}/estimate`, {
      method: 'POST',
      body: {
        additional_cost: additionalCost,
        ...(details?.trim() ? { details: details.trim() } : {}),
      },
    });
  },

  proposeProviderOrder(
    id: number,
    proposedPrice: number,
    estimatedTime: string,
    details?: string,
  ): Promise<ServiceOrderResponse> {
    return request<ServiceOrderResponse>(`/provider/orders/${id}/propose`, {
      method: 'POST',
      body: {
        proposed_price: proposedPrice,
        estimated_time: estimatedTime,
        ...(details?.trim() ? { details: details.trim() } : {}),
      },
    });
  },

  completeProviderOrder(orderId: number, form: FormData): Promise<ServiceOrderResponse> {
    return request<ServiceOrderResponse>(`/provider/orders/${orderId}/complete`, {
      method: 'POST',
      body: form,
    });
  },

  approveEstimate(id: number): Promise<ServiceOrderResponse> {
    return request<ServiceOrderResponse>(`/service-orders/${id}/approve-estimate`, {
      method: 'POST',
    });
  },

  confirmRelease(id: number): Promise<ServiceOrderResponse> {
    return request<ServiceOrderResponse>(`/service-orders/${id}/confirm-release`, {
      method: 'POST',
    });
  },

  notifications(params: { per_page?: number; page?: number; unread_only?: boolean } = {}): Promise<NotificationsResponse> {
    const query = new URLSearchParams();

    if (params.per_page) {
      query.set('per_page', String(params.per_page));
    }

    if (params.page) {
      query.set('page', String(params.page));
    }

    if (params.unread_only) {
      query.set('unread_only', '1');
    }

    const suffix = query.size > 0 ? `?${query.toString()}` : '';

    return request<NotificationsResponse>(`/notifications${suffix}`);
  },

  markNotificationAsRead(id: number): Promise<NotificationResponse> {
    return request<NotificationResponse>(`/notifications/${id}/read`, {
      method: 'POST',
    });
  },

  markAllNotificationsAsRead(): Promise<SimpleResponse> {
    return request<SimpleResponse>('/notifications/read-all', {
      method: 'POST',
    });
  },

  contactInquiries(params: ContactInquiriesParams = {}): Promise<ContactInquiriesResponse> {
    const query = new URLSearchParams();

    if (params.status && params.status !== 'all') {
      query.set('status', params.status);
    }

    if (params.per_page) {
      query.set('per_page', String(params.per_page));
    }

    if (params.page) {
      query.set('page', String(params.page));
    }

    const suffix = query.size > 0 ? `?${query.toString()}` : '';

    return request<ContactInquiriesResponse>(`/contact-inquiries${suffix}`);
  },

  contactInquiry(id: number): Promise<ContactInquiryResponse> {
    return request<ContactInquiryResponse>(`/contact-inquiries/${id}`);
  },

  updateContactInquiryStatus(
    id: number,
    status: ContactInquiryStatus,
  ): Promise<UpdateContactInquiryStatusResponse> {
    return request<UpdateContactInquiryStatusResponse>(`/contact-inquiries/${id}/status`, {
      method: 'POST',
      body: { status },
    });
  },

  groupStatuses(): Promise<GroupStatusesResponse> {
    return request<GroupStatusesResponse>('/group-statuses');
  },

  createGroupStatus(description: string): Promise<GroupStatusResponse> {
    return request<GroupStatusResponse>('/group-statuses', {
      method: 'POST',
      body: { description },
    });
  },

  updateGroupStatus(id: number, description: string): Promise<GroupStatusResponse> {
    return request<GroupStatusResponse>(`/group-statuses/${id}`, {
      method: 'PUT',
      body: { description },
    });
  },

  deleteGroupStatus(id: number): Promise<SimpleResponse> {
    return request<SimpleResponse>(`/group-statuses/${id}`, {
      method: 'DELETE',
    });
  },

  userStatuses(): Promise<UserStatusesResponse> {
    return request<UserStatusesResponse>('/user-statuses');
  },

  createUserStatus(status: string): Promise<UserStatusResponse> {
    return request<UserStatusResponse>('/user-statuses', {
      method: 'POST',
      body: { status },
    });
  },

  updateUserStatus(id: number, status: string): Promise<UserStatusResponse> {
    return request<UserStatusResponse>(`/user-statuses/${id}`, {
      method: 'PUT',
      body: { status },
    });
  },

  deleteUserStatus(id: number): Promise<SimpleResponse> {
    return request<SimpleResponse>(`/user-statuses/${id}`, {
      method: 'DELETE',
    });
  },

  groups(): Promise<GroupsResponse> {
    return request<GroupsResponse>('/groups');
  },

  createGroup(payload: GroupPayload): Promise<GroupResponse> {
    return request<GroupResponse>('/groups', {
      method: 'POST',
      body: payload,
    });
  },

  updateGroup(id: number, payload: GroupPayload): Promise<GroupResponse> {
    return request<GroupResponse>(`/groups/${id}`, {
      method: 'PUT',
      body: payload,
    });
  },

  deleteGroup(id: number): Promise<SimpleResponse> {
    return request<SimpleResponse>(`/groups/${id}`, {
      method: 'DELETE',
    });
  },

  modules(): Promise<ModulesResponse> {
    return request<ModulesResponse>('/modules');
  },

  createModule(payload: ModulePayload): Promise<ModuleResponse> {
    return request<ModuleResponse>('/modules', {
      method: 'POST',
      body: payload,
    });
  },

  updateModule(id: number, payload: ModulePayload): Promise<ModuleResponse> {
    return request<ModuleResponse>(`/modules/${id}`, {
      method: 'PUT',
      body: payload,
    });
  },

  deleteModule(id: number): Promise<SimpleResponse> {
    return request<SimpleResponse>(`/modules/${id}`, {
      method: 'DELETE',
    });
  },

  pages(): Promise<PagesResponse> {
    return request<PagesResponse>('/pages');
  },

  createPage(payload: PagePayload): Promise<PageResponse> {
    return request<PageResponse>('/pages', {
      method: 'POST',
      body: payload,
    });
  },

  updatePage(id: number, payload: PagePayload): Promise<PageResponse> {
    return request<PageResponse>(`/pages/${id}`, {
      method: 'PUT',
      body: payload,
    });
  },

  deletePage(id: number): Promise<SimpleResponse> {
    return request<SimpleResponse>(`/pages/${id}`, {
      method: 'DELETE',
    });
  },

  // Mi Perfil: mismos paneles y reglas que el dashboard web.
  profile(): Promise<ProfileResponse> {
    return request<ProfileResponse>('/profile');
  },

  updateProfile(name: string): Promise<ProfileUserResponse> {
    return request<ProfileUserResponse>('/profile', {
      method: 'PUT',
      body: { name },
    });
  },

  updateProfilePassword(payload: {
    current_password?: string;
    password: string;
    password_confirmation: string;
  }): Promise<ProfilePasswordResponse> {
    return request<ProfilePasswordResponse>('/profile/password', {
      method: 'PUT',
      body: payload,
    });
  },

  updateProvider(payload: ProviderPayload): Promise<ProfileProviderResponse> {
    return request<ProfileProviderResponse>('/profile/provider', {
      method: 'PUT',
      body: payload,
    });
  },

  updateVehicle(vehicle_type: string, vehicle_plate?: string): Promise<ProfileVehicleResponse> {
    return request<ProfileVehicleResponse>('/profile/vehicle', {
      method: 'PATCH',
      body: { vehicle_type, vehicle_plate: vehicle_plate ?? null },
    });
  },

  uploadProfileImage(form: FormData): Promise<ProfileImageResponse> {
    return request<ProfileImageResponse>('/profile/images', {
      method: 'POST',
      body: form,
    });
  },

  deleteProfileImage(id: number): Promise<SimpleResponse> {
    return request<SimpleResponse>(`/profile/images/${id}`, {
      method: 'DELETE',
    });
  },

  profileAddress(): Promise<ProfileAddressResponse> {
    return request<ProfileAddressResponse>('/profile/address');
  },

  saveAddress(payload: AddressPayload): Promise<ProfileAddressResponse> {
    return request<ProfileAddressResponse>('/profile/address', {
      method: 'PUT',
      body: payload,
    });
  },

  profileCountries(): Promise<CountriesResponse> {
    return request<CountriesResponse>('/profile/countries');
  },

  profileRegions(countryId: number): Promise<RegionsResponse> {
    return request<RegionsResponse>(`/profile/regions?country_id=${countryId}`);
  },

  profileServices(): Promise<ProfileServicesResponse> {
    return request<ProfileServicesResponse>('/profile/services');
  },

  createProfileService(payload: { name: string; description?: string }): Promise<ProfileServiceResponse> {
    return request<ProfileServiceResponse>('/profile/services', {
      method: 'POST',
      body: payload,
    });
  },

  updateProfileService(
    id: number,
    payload: { name: string; description?: string },
  ): Promise<ProfileServiceResponse> {
    return request<ProfileServiceResponse>(`/profile/services/${id}`, {
      method: 'PUT',
      body: payload,
    });
  },

  deleteProfileService(id: number): Promise<SimpleResponse> {
    return request<SimpleResponse>(`/profile/services/${id}`, {
      method: 'DELETE',
    });
  },

  reorderProfileServices(order: number[]): Promise<ProfileServicesResponse> {
    return request<ProfileServicesResponse>('/profile/services/reorder', {
      method: 'PUT',
      body: { order },
    });
  },

  profileSubgroups(): Promise<ProfileSubgroupsResponse> {
    return request<ProfileSubgroupsResponse>('/profile/subgroups');
  },

  toggleProfileSubgroup(subgroupId: number): Promise<ToggleSubgroupResponse> {
    return request<ToggleSubgroupResponse>('/profile/subgroups/toggle', {
      method: 'POST',
      body: { subgroup_id: subgroupId },
    });
  },

  profileServiceTypes(): Promise<ProfileServiceTypesResponse> {
    return request<ProfileServiceTypesResponse>('/profile/service-types');
  },

  toggleProfileServiceType(serviceTypeId: number): Promise<ToggleSubgroupResponse> {
    return request<ToggleSubgroupResponse>('/profile/service-types/toggle', {
      method: 'POST',
      body: { service_type_id: serviceTypeId },
    });
  },

  trustedDrivers(): Promise<TrustedDriversResponse> {
    return request<TrustedDriversResponse>('/profile/trusted-drivers');
  },

  updateTrustedDriversSettings(enabled: boolean): Promise<TrustedDriversSettingsResponse> {
    return request<TrustedDriversSettingsResponse>('/profile/trusted-drivers/settings', {
      method: 'PUT',
      body: { enabled },
    });
  },

  addTrustedDriver(userId: number): Promise<TrustedDriversResponse> {
    return request<TrustedDriversResponse>('/profile/trusted-drivers', {
      method: 'POST',
      body: { user_id: userId },
    });
  },

  removeTrustedDriver(id: number, disableMode = false): Promise<TrustedDriversResponse> {
    const suffix = disableMode ? '?disable_mode=1' : '';

    return request<TrustedDriversResponse>(`/profile/trusted-drivers/${id}${suffix}`, {
      method: 'DELETE',
    });
  },

  profileUsers(): Promise<AccountUsersResponse> {
    return request<AccountUsersResponse>('/profile/users');
  },

  createProfileUser(payload: {
    name: string;
    password: string;
    password_confirmation: string;
    user_status_id?: number | null;
  }): Promise<AccountUserResponse> {
    return request<AccountUserResponse>('/profile/users', {
      method: 'POST',
      body: payload,
    });
  },

  updateProfileUser(
    id: number,
    payload: {
      name: string;
      password?: string;
      password_confirmation?: string;
      user_status_id?: number | null;
    },
  ): Promise<AccountUserResponse> {
    return request<AccountUserResponse>(`/profile/users/${id}`, {
      method: 'PUT',
      body: payload,
    });
  },

  deleteProfileUser(id: number): Promise<SimpleResponse> {
    return request<SimpleResponse>(`/profile/users/${id}`, {
      method: 'DELETE',
    });
  },

  attachProfileUserPage(userId: number, pageId: number): Promise<AccountUserResponse> {
    return request<AccountUserResponse>(`/profile/users/${userId}/pages/${pageId}`, {
      method: 'POST',
    });
  },

  detachProfileUserPage(userId: number, pageId: number): Promise<AccountUserResponse> {
    return request<AccountUserResponse>(`/profile/users/${userId}/pages/${pageId}`, {
      method: 'DELETE',
    });
  },
};

export type { TrustedDriverCandidate };
