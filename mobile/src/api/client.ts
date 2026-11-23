import { API_BASE_URL, API_TIMEOUT_MS } from '@/config';
import { loadToken } from '@/lib/tokenStore';
import type {
  AccountUserResponse,
  AccountUsersResponse,
  AddressPayload,
  AdminUserAddressPayload,
  AdminUserCreatePayload,
  AdminUserPageResponse,
  AdminUserResponse,
  AdminUsersResponse,
  AdminUserUpdatePayload,
  AuthResponse,
  ContactInquiriesParams,
  ContactInquiriesResponse,
  ContactInquiryResponse,
  ContactInquiryStatus,
  CountriesResponse,
  CurrencyPayload,
  CurrencyResponse,
  CurrenciesResponse,
  DriverActionResponse,
  DriverActiveOrdersResponse,
  DriverOffersResponse,
  DriverStatsResponse,
  DriverStatusPayload,
  DriverStatusResponse,
  DriverWithdrawalPayload,
  DriverWithdrawalResponse,
  DriverWithdrawalsResponse,
  GroupStatusResponse,
  GroupStatusesResponse,
  GroupPayload,
  GroupResponse,
  GroupsResponse,
  MenuResponse,
  MeResponse,
  MarketAddressesResponse,
  MarketCatalogResponse,
  MarketDriverLocationResponse,
  MarketMyOrdersParams,
  MarketMyOrdersResponse,
  MarketOrderPayload,
  MarketOrderResponse,
  MarketPlansResponse,
  MarketQuoteResponse,
  MarketRateOrderPayload,
  MarketRateOrderResponse,
  MarketShopsParams,
  MarketShopsResponse,
  MarketSubscriptionPayload,
  MarketSubscriptionResponse,
  MarketSubscriptionsResponse,
  MarketToggleFavoriteResponse,
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
  ProviderCatalogCategoryResponse,
  ProviderCatalogOptionGroupPayload,
  ProviderCatalogOptionGroupResponse,
  ProviderCatalogOptionPayload,
  ProviderCatalogOptionResponse,
  ProviderCatalogPreviewResponse,
  ProviderCatalogProductPayload,
  ProviderCatalogProductResponse,
  ProviderCatalogProductsResponse,
  ProviderCatalogUnitsResponse,
  ProviderCatalogVariantPayload,
  ProviderCatalogVariantResponse,
  ProviderDetailResponse,
  ProviderDispatchActionResponse,
  ProviderDispatchOrdersParams,
  ProviderDispatchOrdersResponse,
  ProviderDispatchStatusPayload,
  ProviderFinancialHealthResponse,
  ProviderInventoryProductPayload,
  ProviderInventoryProductResponse,
  ProviderInventoryResponse,
  ProviderInventorySettingsResponse,
  ProviderMarketingRulePayload,
  ProviderMarketingRuleResponse,
  ProviderMarketingRulesResponse,
  ProviderOrdersResponse,
  ProviderPayload,
  ProviderWithdrawalActionResponse,
  ProviderWithdrawalsResponse,
  ProviderRecipePayload,
  ProviderRecipeResponse,
  ProviderSubscriptionPaymentPayload,
  ProviderSubscriptionPaymentResponse,
  ProviderSubscriptionPlanPayload,
  ProviderSubscriptionPlanResponse,
  ProviderSubscriptionPlansResponse,
  ProviderSubscriptionRevenueResponse,
  ProviderSuppliesResponse,
  ProviderSupplyPayload,
  ProviderSupplyResponse,
  ProvidersParams,
  ProvidersResponse,
  RegionsResponse,
  RegisterPayload,
  RegisterResponse,
  PayoutAccountPayload,
  PayoutAccountResponse,
  PayoutAccountsResponse,
  BillingStatusResponse,
  DineroChargePayload,
  DineroChargeResponse,
  DineroChargesResponse,
  DineroFeeRulePayload,
  DineroFeeRuleResponse,
  DineroFeeRulesResponse,
  DineroInvoiceResponse,
  DineroInvoicesResponse,
  DineroLedgerResponse,
  DineroDriverWithdrawalActionResponse,
  DineroDriverWithdrawalsResponse,
  DineroMatrix,
  DineroMatrixResponse,
  DineroOperatingStatusResponse,
  DineroPlanPayload,
  DineroPlanResponse,
  DineroPlansResponse,
  DineroSettlementPayload,
  DineroSettlementResponse,
  DineroSettlementsResponse,
  DineroSubscribePayload,
  MercadoPagoCheckoutResponse,
  WalletTypesResponse,
  AutoApproveResponse,
  ReportComercioHistoryParams,
  ReportComercioHistoryResponse,
  ReportComercioNutritionParams,
  ReportComercioNutritionResponse,
  ReportComercioSavingsResponse,
  ReportComercioSubscriptionsResponse,
  MarketPaymentStatusResponse,
  AppVersionResponse,
  ProviderMarginsResponse,
  ProviderMrrResponse,
  ProviderCommissionResponse,
  ProviderLtvResponse,
  ProviderCampaignsResponse,
  ProviderHeatmapResponse,
  ProviderForecastResponse,
  ReportHogarParams,
  ReportHogarResponse,
  ServiceOrderResponse,
  ServiceOrdersParams,
  ServiceOrdersResponse,
  ServiceSubgroupsResponse,
  SimpleResponse,
  SubGroupPayload,
  SubGroupResponse,
  SubGroupsResponse,
  ToggleSubgroupResponse,
  ToggleProviderFavoriteResponse,
  TrustedDriverCandidate,
  TrustedDriversResponse,
  TrustedDriversSettingsResponse,
  TypesResponse,
  TypeUserBulkPagesResponse,
  TypeUserPageRefResponse,
  TypeUserPagesDataResponse,
  TypeUserUpdatePagesResponse,
  TypeUserPayload,
  TypeUserResponse,
  TypeUsersResponse,
  UnitOfMeasurePayload,
  UnitOfMeasureResponse,
  UnitsOfMeasureResponse,
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
  /** Tiempo maximo de espera; por defecto API_TIMEOUT_MS. */
  timeoutMs?: number;
};

async function request<T>(path: string, options: RequestOptions = {}): Promise<T> {
  const { method = 'GET', body, auth = true, timeoutMs = API_TIMEOUT_MS } = options;

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
  const timeout = setTimeout(() => controller.abort(), timeoutMs);

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

  register(payload: RegisterPayload): Promise<RegisterResponse> {
    return request<RegisterResponse>('/auth/register', {
      method: 'POST',
      auth: false,
      timeoutMs: 90000,
      body: { ...payload, device_name: payload.device_name ?? 'PideAca Mobile' },
    });
  },

  /**
   * Reenvío del correo de activación (cuenta aún no activada).
   * Responde igual exista o no, igual que la recuperación de contraseña.
   */
  resendActivation(email: string): Promise<SimpleResponse> {
    return request<SimpleResponse>('/auth/activation/resend', {
      method: 'POST',
      auth: false,
      timeoutMs: 90000,
      body: { email },
    });
  },

  /**
   * Billeteras de cobro (Fase 1). Mismas reglas que el panel web:
   * validacion de CBU/alias/IBAN, una predeterminada por moneda y
   * desactivar exige otra activa.
   */
  payoutAccounts(): Promise<PayoutAccountsResponse> {
    return request<PayoutAccountsResponse>('/profile/payout-accounts');
  },

  walletTypes(): Promise<WalletTypesResponse> {
    return request<WalletTypesResponse>('/wallet-types');
  },

  createPayoutAccount(payload: PayoutAccountPayload): Promise<PayoutAccountResponse> {
    return request<PayoutAccountResponse>('/profile/payout-accounts', {
      method: 'POST',
      timeoutMs: 90000,
      body: payload,
    });
  },

  updatePayoutAccount(id: number, payload: Partial<PayoutAccountPayload>): Promise<PayoutAccountResponse> {
    return request<PayoutAccountResponse>(`/profile/payout-accounts/${id}`, {
      method: 'PUT',
      body: payload,
    });
  },

  setDefaultPayoutAccount(id: number): Promise<PayoutAccountResponse> {
    return request<PayoutAccountResponse>(`/profile/payout-accounts/${id}/default`, {
      method: 'POST',
    });
  },

  deactivatePayoutAccount(id: number): Promise<PayoutAccountResponse> {
    return request<PayoutAccountResponse>(`/profile/payout-accounts/${id}/deactivate`, {
      method: 'POST',
    });
  },

  reactivatePayoutAccount(id: number): Promise<PayoutAccountResponse> {
    return request<PayoutAccountResponse>(`/profile/payout-accounts/${id}/reactivate`, {
      method: 'POST',
    });
  },

  billingStatus(): Promise<BillingStatusResponse> {
    return request<BillingStatusResponse>('/profile/billing/status');
  },

  /**
   * Mi Dinero del prestador: solo datos propios (el servidor filtra por el
   * usuario autenticado). Espejo de las pestañas web de liquidaciones,
   * cobros y estado, sin exponer datos de otros usuarios.
   */
  mySettlements(params: { status?: string } = {}): Promise<DineroSettlementsResponse> {
    const suffix = params.status ? `?status=${encodeURIComponent(params.status)}` : '';
    return request<DineroSettlementsResponse>(`/profile/billing/settlements${suffix}`);
  },

  myCharges(params: { status?: string } = {}): Promise<DineroChargesResponse> {
    const suffix = params.status ? `?status=${encodeURIComponent(params.status)}` : '';
    return request<DineroChargesResponse>(`/profile/billing/charges${suffix}`);
  },

  /**
   * Backoffice Dinero (Fase 1-3, solo Admin): mismos endpoints y reglas que
   * la seccion web del panel (`/admin/billing`). El servidor verifica Admin
   * y responde 403 si no lo es.
   */
  dineroFeeRules(): Promise<DineroFeeRulesResponse> {
    return request<DineroFeeRulesResponse>('/admin/billing/fee-rules');
  },

  dineroCreateFeeRule(payload: DineroFeeRulePayload): Promise<DineroFeeRuleResponse> {
    return request<DineroFeeRuleResponse>('/admin/billing/fee-rules', {
      method: 'POST',
      body: payload,
    });
  },

  dineroToggleFeeRule(id: number, active: boolean): Promise<DineroFeeRuleResponse> {
    return request<DineroFeeRuleResponse>(`/admin/billing/fee-rules/${id}`, {
      method: 'PUT',
      body: { active },
    });
  },

  dineroMatrix(): Promise<DineroMatrixResponse> {
    return request<DineroMatrixResponse>('/admin/billing/company');
  },

  dineroSaveMatrix(settings: DineroMatrix): Promise<DineroMatrixResponse> {
    return request<DineroMatrixResponse>('/admin/billing/company', {
      method: 'PUT',
      body: { settings },
    });
  },

  dineroSettlements(params: { status?: string; user_id?: number } = {}): Promise<DineroSettlementsResponse> {
    const query = new URLSearchParams();
    if (params.status) query.set('status', params.status);
    if (params.user_id) query.set('user_id', String(params.user_id));
    const suffix = query.toString() ? `?${query.toString()}` : '';
    return request<DineroSettlementsResponse>(`/admin/billing/settlements${suffix}`);
  },

  dineroCreateSettlement(payload: DineroSettlementPayload): Promise<DineroSettlementResponse> {
    return request<DineroSettlementResponse>('/admin/billing/settlements', {
      method: 'POST',
      body: payload,
    });
  },

  dineroPaySettlement(id: number, reference: string): Promise<DineroSettlementResponse> {
    return request<DineroSettlementResponse>(`/admin/billing/settlements/${id}/pay`, {
      method: 'POST',
      body: { reference },
    });
  },

  dineroCancelSettlement(id: number): Promise<DineroSettlementResponse> {
    return request<DineroSettlementResponse>(`/admin/billing/settlements/${id}/cancel`, {
      method: 'POST',
    });
  },

  dineroCharges(params: { status?: string; module?: string } = {}): Promise<DineroChargesResponse> {
    const query = new URLSearchParams();
    if (params.status) query.set('status', params.status);
    if (params.module) query.set('module', params.module);
    const suffix = query.toString() ? `?${query.toString()}` : '';
    return request<DineroChargesResponse>(`/admin/billing/charges${suffix}`);
  },

  dineroRegisterCharge(payload: DineroChargePayload): Promise<DineroChargeResponse> {
    return request<DineroChargeResponse>('/admin/billing/charges', {
      method: 'POST',
      body: payload,
    });
  },

  dineroVerifyCharge(id: number): Promise<DineroChargeResponse> {
    return request<DineroChargeResponse>(`/admin/billing/charges/${id}/verify`, {
      method: 'POST',
    });
  },

  dineroLedger(params: { type?: string; currency?: string; user_id?: number } = {}): Promise<DineroLedgerResponse> {
    const query = new URLSearchParams();
    if (params.type) query.set('type', params.type);
    if (params.currency) query.set('currency', params.currency);
    if (params.user_id) query.set('user_id', String(params.user_id));
    const suffix = query.toString() ? `?${query.toString()}` : '';
    return request<DineroLedgerResponse>(`/admin/billing/ledger${suffix}`);
  },

  dineroOperatingStatus(user_id: number, currency = 'ARS'): Promise<DineroOperatingStatusResponse> {
    const query = new URLSearchParams({
      user_id: String(user_id),
      currency,
    });
    return request<DineroOperatingStatusResponse>(`/admin/billing/status?${query.toString()}`);
  },

  /**
   * Retiros de repartidores pendientes (admin). Espeja
   * /admin/withdrawals de la web: mismo controlador y mismo gate
   * (hasPageUrl('usuarios')).
   */
  dineroDriverWithdrawals(): Promise<DineroDriverWithdrawalsResponse> {
    return request<DineroDriverWithdrawalsResponse>('/admin/billing/driver-withdrawals');
  },

  dineroApproveDriverWithdrawal(id: number): Promise<DineroDriverWithdrawalActionResponse> {
    return request<DineroDriverWithdrawalActionResponse>(
      `/admin/billing/driver-withdrawals/${id}/approve`,
      { method: 'POST' },
    );
  },

  dineroRejectDriverWithdrawal(id: number, reason?: string | null): Promise<DineroDriverWithdrawalActionResponse> {
    return request<DineroDriverWithdrawalActionResponse>(
      `/admin/billing/driver-withdrawals/${id}/reject`,
      { method: 'POST', body: { reason: reason ?? null } },
    );
  },

  dineroPlans(): Promise<DineroPlansResponse> {
    return request<DineroPlansResponse>('/admin/billing/plans');
  },

  dineroCreatePlan(payload: DineroPlanPayload): Promise<DineroPlanResponse> {
    return request<DineroPlanResponse>('/admin/billing/plans', {
      method: 'POST',
      body: payload,
    });
  },

  dineroUpdatePlan(id: number, payload: Partial<DineroPlanPayload>): Promise<DineroPlanResponse> {
    return request<DineroPlanResponse>(`/admin/billing/plans/${id}`, {
      method: 'PUT',
      body: payload,
    });
  },

  dineroInvoices(params: { status?: string; user_id?: number } = {}): Promise<DineroInvoicesResponse> {
    const query = new URLSearchParams();
    if (params.status) query.set('status', params.status);
    if (params.user_id) query.set('user_id', String(params.user_id));
    const suffix = query.toString() ? `?${query.toString()}` : '';
    return request<DineroInvoicesResponse>(`/admin/billing/invoices${suffix}`);
  },

  dineroPayInvoice(id: number, reference?: string | null): Promise<DineroInvoiceResponse> {
    return request<DineroInvoiceResponse>(`/admin/billing/invoices/${id}/pay`, {
      method: 'POST',
      body: { reference: reference ?? null },
    });
  },

  /**
   * Billeteras por país (Fase 1, solo Admin): catálogo completo de tipos,
   * igual que la página web (incluye inactivos, a diferencia del combo
   * público que solo trae activos).
   */
  dineroWalletTypes(): Promise<WalletTypesResponse> {
    return request<WalletTypesResponse>('/admin/billing/wallet-types');
  },

  dineroCreateWalletType(payload: {
    country: string;
    code: string;
    name: string;
    fields?: string[];
    active?: boolean;
  }): Promise<SimpleResponse> {
    return request<SimpleResponse>('/admin/billing/wallet-types', {
      method: 'POST',
      body: payload,
    });
  },

  dineroUpdateWalletType(
    id: number,
    payload: { name?: string; fields?: string[]; active?: boolean },
  ): Promise<SimpleResponse> {
    return request<SimpleResponse>(`/admin/billing/wallet-types/${id}`, {
      method: 'PUT',
      body: payload,
    });
  },

  dineroSubscribe(payload: DineroSubscribePayload): Promise<SimpleResponse> {
    return request<SimpleResponse>('/admin/billing/subscribe', {
      method: 'POST',
      body: payload,
    });
  },

  startMercadoPagoCheckout(
    orderType: 'order' | 'service_order',
    orderId: number,
  ): Promise<MercadoPagoCheckoutResponse> {
    return request<MercadoPagoCheckoutResponse>('/checkout/mercadopago/start', {
      method: 'POST',
      body: { order_type: orderType, order_id: orderId },
    });
  },

  types(): Promise<TypesResponse> {
    return request<TypesResponse>('/auth/types', { auth: false });
  },

  /**
   * Versionado propio Android (Fase 3, $0 sin EAS Update).
   * Publico: la app lo consulta sin sesion al arrancar.
   */
  appVersion(): Promise<AppVersionResponse> {
    return request<AppVersionResponse>('/app/version', { auth: false });
  },

  /**
   * Recuperacion de contrasena por correo.
   *
   * El servidor responde siempre igual (exista o no la cuenta) para no
   * revelar que correos estan registrados, y envia un enlace valido por
   * 60 minutos. El envio es sincrono, por eso esta llamada espera mas que
   * el resto: el SMTP puede tardar decenas de segundos.
   */
  forgotPassword(email: string): Promise<SimpleResponse> {
    return request<SimpleResponse>('/auth/password/forgot', {
      method: 'POST',
      auth: false,
      timeoutMs: 90000,
      body: { email },
    });
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

  toggleProviderFavorite(id: number): Promise<ToggleProviderFavoriteResponse> {
    return request<ToggleProviderFavoriteResponse>(`/providers/${id}/favorite`, {
      method: 'POST',
    });
  },

  marketShops(params: MarketShopsParams = {}): Promise<MarketShopsResponse> {
    const query = new URLSearchParams();

    if (params.module_id) {
      query.set('module_id', String(params.module_id));
    }

    if (params.search?.trim()) {
      query.set('search', params.search.trim());
    }

    if (params.favorite) {
      query.set('favorite', '1');
    }

    if (params.only_with_plans) {
      query.set('only_with_plans', '1');
    }

    const suffix = query.toString();

    return request<MarketShopsResponse>(`/market/shops${suffix ? `?${suffix}` : ''}`);
  },

  toggleMarketFavorite(id: number): Promise<MarketToggleFavoriteResponse> {
    return request<MarketToggleFavoriteResponse>(`/market/shops/${id}/favorite`, {
      method: 'POST',
    });
  },

  marketCatalog(id: number): Promise<MarketCatalogResponse> {
    return request<MarketCatalogResponse>(`/market/shops/${id}/catalog`);
  },

  marketPlans(id: number): Promise<MarketPlansResponse> {
    return request<MarketPlansResponse>(`/market/shops/${id}/plans`);
  },

  marketAddresses(): Promise<MarketAddressesResponse> {
    return request<MarketAddressesResponse>('/market/addresses');
  },

  marketQuote(
    providerId: number,
    addressId: number | null,
    deliveryType: 'delivery' | 'takeaway' = 'delivery',
  ): Promise<MarketQuoteResponse> {
    const params = new URLSearchParams({
      provider_id: String(providerId),
      delivery_type: deliveryType,
    });

    if (addressId) {
      params.set('address_id', String(addressId));
    }

    return request<MarketQuoteResponse>(`/market/quote?${params.toString()}`);
  },

  createMarketOrder(payload: MarketOrderPayload): Promise<MarketOrderResponse> {
    return request<MarketOrderResponse>('/market/orders', {
      method: 'POST',
      body: payload,
    });
  },

  marketOrders(params: MarketMyOrdersParams = {}): Promise<MarketMyOrdersResponse> {
    const suffix = params.status ? `?status=${encodeURIComponent(params.status)}` : '';

    return request<MarketMyOrdersResponse>(`/market/orders${suffix}`);
  },

  rateMarketOrder(
    id: number,
    payload: MarketRateOrderPayload,
  ): Promise<MarketRateOrderResponse> {
    return request<MarketRateOrderResponse>(`/market/orders/${id}/rate`, {
      method: 'POST',
      body: payload,
    });
  },

  driverLocation(id: number): Promise<MarketDriverLocationResponse> {
    return request<MarketDriverLocationResponse>(`/market/orders/${id}/driver-location`);
  },

  /**
   * Estado del pago de un pedido (flujo "pagar antes de confirmar").
   * Solo el dueno; no muta nada.
   */
  marketPaymentStatus(id: number): Promise<MarketPaymentStatusResponse> {
    return request<MarketPaymentStatusResponse>(`/market/orders/${id}/payment-status`);
  },

  marketSubscriptions(): Promise<MarketSubscriptionsResponse> {
    return request<MarketSubscriptionsResponse>('/market/subscriptions');
  },

  createMarketSubscription(payload: MarketSubscriptionPayload): Promise<MarketSubscriptionResponse> {
    return request<MarketSubscriptionResponse>('/market/subscriptions', {
      method: 'POST',
      body: payload,
    });
  },

  updateMarketSubscriptionStatus(
    id: number,
    status: string,
  ): Promise<MarketSubscriptionResponse> {
    return request<MarketSubscriptionResponse>(`/market/subscriptions/${id}/status`, {
      method: 'PATCH',
      body: { status },
    });
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

  /**
   * Reportes Hogar: mismos filtros y KPIs que la vista web
   * (ReporteHogarController). La respuesta trae el consolidado y todas las
   * órdenes filtradas para armar las tarjetas de detalle.
   */
  reportHogar(params: ReportHogarParams = {}): Promise<ReportHogarResponse> {
    const query = new URLSearchParams();

    if (params.fecha_desde) {
      query.set('fecha_desde', params.fecha_desde);
    }

    if (params.fecha_hasta) {
      query.set('fecha_hasta', params.fecha_hasta);
    }

    if (params.estado) {
      query.set('estado', params.estado);
    }

    if (params.tipo) {
      query.set('tipo', params.tipo);
    }

    const suffix = query.size > 0 ? `?${query.toString()}` : '';

    return request<ReportHogarResponse>(`/reports/hogar${suffix}`);
  },

  /** Reportes Comercio: ahorro del cliente vs. precios Express. */
  reportComercioSavings(): Promise<ReportComercioSavingsResponse> {
    return request<ReportComercioSavingsResponse>('/reports/comercio/savings');
  },

  /** Reportes Comercio: suscripciones activas y pausadas del cliente. */
  reportComercioSubscriptions(): Promise<ReportComercioSubscriptionsResponse> {
    return request<ReportComercioSubscriptionsResponse>('/reports/comercio/subscriptions');
  },

  /** Reportes Comercio: platos y comercios mas frecuentes. */
  reportComercioHistory(
    params: ReportComercioHistoryParams = {},
  ): Promise<ReportComercioHistoryResponse> {
    const suffix = params.limit ? `?limit=${params.limit}` : '';

    return request<ReportComercioHistoryResponse>(`/reports/comercio/order-history${suffix}`);
  },

  /** Reportes Comercio: resumen nutricional por mes (YYYY-MM). */
  reportComercioNutrition(
    params: ReportComercioNutritionParams = {},
  ): Promise<ReportComercioNutritionResponse> {
    const suffix = params.month ? `?month=${encodeURIComponent(params.month)}` : '';

    return request<ReportComercioNutritionResponse>(`/reports/comercio/nutritional${suffix}`);
  },

  /**
   * Reportes del comercio (prestador): espejo movil de
   * /api/v1/provider/reports/* de la web. Mismos JSON que los tabs de
   * Rentabilidad, MRR, Comisiones, Clientes, Campanas, Demanda y
   * Abastecimiento. El servidor exige comercio asociado (403 si no).
   */
  providerReportMargins(productId?: number | null): Promise<ProviderMarginsResponse> {
    const suffix = productId ? `?product_id=${productId}` : '';
    return request<ProviderMarginsResponse>(`/provider/reports/margins${suffix}`);
  },

  providerReportMrr(periodMonths?: number | null): Promise<ProviderMrrResponse> {
    const suffix = periodMonths ? `?period=${periodMonths}` : '';
    return request<ProviderMrrResponse>(`/provider/reports/mrr${suffix}`);
  },

  providerReportCommission(params: { start_date?: string; end_date?: string } = {}): Promise<ProviderCommissionResponse> {
    const query = new URLSearchParams();
    if (params.start_date) query.set('start_date', params.start_date);
    if (params.end_date) query.set('end_date', params.end_date);
    const suffix = query.size > 0 ? `?${query.toString()}` : '';
    return request<ProviderCommissionResponse>(`/provider/reports/commission-savings${suffix}`);
  },

  providerReportLtv(): Promise<ProviderLtvResponse> {
    return request<ProviderLtvResponse>('/provider/reports/ltv-risk');
  },

  providerReportCampaigns(): Promise<ProviderCampaignsResponse> {
    return request<ProviderCampaignsResponse>('/provider/reports/campaigns');
  },

  providerReportHeatmap(days?: number | null): Promise<ProviderHeatmapResponse> {
    const suffix = days ? `?days=${days}` : '';
    return request<ProviderHeatmapResponse>(`/provider/reports/demand-heatmap${suffix}`);
  },

  providerReportForecast(params: { start_date?: string; end_date?: string } = {}): Promise<ProviderForecastResponse> {
    const query = new URLSearchParams();
    if (params.start_date) query.set('start_date', params.start_date);
    if (params.end_date) query.set('end_date', params.end_date);
    const suffix = query.size > 0 ? `?${query.toString()}` : '';
    return request<ProviderForecastResponse>(`/provider/reports/stock-forecast${suffix}`);
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

  menu(managedUserId?: number | null): Promise<MenuResponse> {
    const suffix = managedUserId ? `?managed_user_id=${managedUserId}` : '';
    return request<MenuResponse>(`/menu${suffix}`);
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

  subgroups(): Promise<SubGroupsResponse> {
    return request<SubGroupsResponse>('/subgroups');
  },

  createSubgroup(payload: SubGroupPayload): Promise<SubGroupResponse> {
    return request<SubGroupResponse>('/subgroups', {
      method: 'POST',
      body: payload,
    });
  },

  updateSubgroup(id: number, payload: SubGroupPayload): Promise<SubGroupResponse> {
    return request<SubGroupResponse>(`/subgroups/${id}`, {
      method: 'PUT',
      body: payload,
    });
  },

  deleteSubgroup(id: number): Promise<SimpleResponse> {
    return request<SimpleResponse>(`/subgroups/${id}`, {
      method: 'DELETE',
    });
  },

  currencies(): Promise<CurrenciesResponse> {
    return request<CurrenciesResponse>('/currencies');
  },

  createCurrency(payload: CurrencyPayload): Promise<CurrencyResponse> {
    return request<CurrencyResponse>('/currencies', {
      method: 'POST',
      body: payload,
    });
  },

  updateCurrency(id: number, payload: CurrencyPayload): Promise<CurrencyResponse> {
    return request<CurrencyResponse>(`/currencies/${id}`, {
      method: 'PUT',
      body: payload,
    });
  },

  deleteCurrency(id: number): Promise<SimpleResponse> {
    return request<SimpleResponse>(`/currencies/${id}`, {
      method: 'DELETE',
    });
  },

  typeUsers(): Promise<TypeUsersResponse> {
    return request<TypeUsersResponse>('/type-users');
  },

  createTypeUser(payload: TypeUserPayload): Promise<TypeUserResponse> {
    return request<TypeUserResponse>('/type-users', {
      method: 'POST',
      body: payload,
    });
  },

  updateTypeUser(id: number, payload: TypeUserPayload): Promise<TypeUserResponse> {
    return request<TypeUserResponse>(`/type-users/${id}`, {
      method: 'PUT',
      body: payload,
    });
  },

  deleteTypeUser(id: number): Promise<SimpleResponse> {
    return request<SimpleResponse>(`/type-users/${id}`, {
      method: 'DELETE',
    });
  },

  typeUserPagesData(): Promise<TypeUserPagesDataResponse> {
    return request<TypeUserPagesDataResponse>('/type-users/pages-data');
  },

  updateTypeUserPages(typeId: number, pageIds: number[]): Promise<TypeUserUpdatePagesResponse> {
    return request<TypeUserUpdatePagesResponse>(`/type-users/${typeId}/pages`, {
      method: 'PUT',
      body: { page_ids: pageIds },
    });
  },

  bulkTypeUserPages(
    typeId: number,
    pageIds: number[],
    action: 'attach' | 'detach',
  ): Promise<TypeUserBulkPagesResponse> {
    return request<TypeUserBulkPagesResponse>(`/type-users/${typeId}/pages/bulk`, {
      method: 'POST',
      body: { page_ids: pageIds, action },
    });
  },

  attachTypeUserPage(typeId: number, pageId: number): Promise<TypeUserPageRefResponse> {
    return request<TypeUserPageRefResponse>(`/type-users/${typeId}/pages/${pageId}`, {
      method: 'POST',
    });
  },

  detachTypeUserPage(typeId: number, pageId: number): Promise<TypeUserPageRefResponse> {
    return request<TypeUserPageRefResponse>(`/type-users/${typeId}/pages/${pageId}`, {
      method: 'DELETE',
    });
  },

  unitsOfMeasure(): Promise<UnitsOfMeasureResponse> {
    return request<UnitsOfMeasureResponse>('/units-of-measure');
  },

  createUnitOfMeasure(payload: UnitOfMeasurePayload): Promise<UnitOfMeasureResponse> {
    return request<UnitOfMeasureResponse>('/units-of-measure', {
      method: 'POST',
      body: payload,
    });
  },

  updateUnitOfMeasure(id: number, payload: UnitOfMeasurePayload): Promise<UnitOfMeasureResponse> {
    return request<UnitOfMeasureResponse>(`/units-of-measure/${id}`, {
      method: 'PUT',
      body: payload,
    });
  },

  deleteUnitOfMeasure(id: number): Promise<SimpleResponse> {
    return request<SimpleResponse>(`/units-of-measure/${id}`, {
      method: 'DELETE',
    });
  },

  // Gestion de Usuarios: mismos paneles y reglas que la seccion web.
  adminUsers(managedUserId?: number | null): Promise<AdminUsersResponse> {
    const suffix = managedUserId ? `?managed_user_id=${managedUserId}` : '';
    return request<AdminUsersResponse>(`/admin-users${suffix}`);
  },

  createAdminUser(payload: AdminUserCreatePayload, managedUserId?: number | null): Promise<AdminUserResponse> {
    const suffix = managedUserId ? `?managed_user_id=${managedUserId}` : '';
    return request<AdminUserResponse>(`/admin-users${suffix}`, {
      method: 'POST',
      body: payload,
    });
  },

  updateAdminUser(id: number, payload: AdminUserUpdatePayload): Promise<AdminUserResponse> {
    return request<AdminUserResponse>(`/admin-users/${id}`, {
      method: 'PUT',
      body: payload,
    });
  },

  deleteAdminUser(id: number): Promise<SimpleResponse> {
    return request<SimpleResponse>(`/admin-users/${id}`, {
      method: 'DELETE',
    });
  },

  attachAdminUserPage(userId: number, pageId: number): Promise<AdminUserPageResponse> {
    return request<AdminUserPageResponse>(`/admin-users/${userId}/pages/${pageId}`, {
      method: 'POST',
    });
  },

  detachAdminUserPage(userId: number, pageId: number): Promise<AdminUserPageResponse> {
    return request<AdminUserPageResponse>(`/admin-users/${userId}/pages/${pageId}`, {
      method: 'DELETE',
    });
  },

  // Perfil del usuario gestionado (modo managed de la web): mismo formato
  // que los endpoints de /profile pero operando sobre el gestionado.
  adminUserProfile(userId: number): Promise<ProfileResponse> {
    return request<ProfileResponse>(`/admin-users/${userId}/profile`);
  },

  updateAdminUserProvider(userId: number, payload: ProviderPayload): Promise<ProfileProviderResponse> {
    return request<ProfileProviderResponse>(`/admin-users/${userId}/provider`, {
      method: 'PUT',
      body: payload,
    });
  },

  adminUserServices(userId: number): Promise<ProfileServicesResponse> {
    return request<ProfileServicesResponse>(`/admin-users/${userId}/services`);
  },

  createAdminUserService(
    userId: number,
    payload: { name: string; description?: string },
  ): Promise<ProfileServiceResponse> {
    return request<ProfileServiceResponse>(`/admin-users/${userId}/services`, {
      method: 'POST',
      body: payload,
    });
  },

  updateAdminUserService(
    userId: number,
    serviceId: number,
    payload: { name: string; description?: string },
  ): Promise<ProfileServiceResponse> {
    return request<ProfileServiceResponse>(`/admin-users/${userId}/services/${serviceId}`, {
      method: 'PUT',
      body: payload,
    });
  },

  deleteAdminUserService(userId: number, serviceId: number): Promise<SimpleResponse> {
    return request<SimpleResponse>(`/admin-users/${userId}/services/${serviceId}`, {
      method: 'DELETE',
    });
  },

  uploadAdminUserImage(userId: number, form: FormData): Promise<ProfileImageResponse> {
    return request<ProfileImageResponse>(`/admin-users/${userId}/images`, {
      method: 'POST',
      body: form,
    });
  },

  deleteAdminUserImage(userId: number, imageId: number): Promise<SimpleResponse> {
    return request<SimpleResponse>(`/admin-users/${userId}/images/${imageId}`, {
      method: 'DELETE',
    });
  },

  adminUserSubgroups(userId: number): Promise<ProfileSubgroupsResponse> {
    return request<ProfileSubgroupsResponse>(`/admin-users/${userId}/subgroups`);
  },

  toggleAdminUserSubgroup(userId: number, subgroupId: number): Promise<ToggleSubgroupResponse> {
    return request<ToggleSubgroupResponse>(`/admin-users/${userId}/subgroups/toggle`, {
      method: 'POST',
      body: { subgroup_id: subgroupId },
    });
  },

  adminUserServiceTypes(userId: number): Promise<ProfileServiceTypesResponse> {
    return request<ProfileServiceTypesResponse>(`/admin-users/${userId}/service-types`);
  },

  toggleAdminUserServiceType(userId: number, serviceTypeId: number): Promise<ToggleSubgroupResponse> {
    return request<ToggleSubgroupResponse>(`/admin-users/${userId}/service-types/toggle`, {
      method: 'POST',
      body: { service_type_id: serviceTypeId },
    });
  },

  createAdminUserAddress(userId: number, payload: AdminUserAddressPayload): Promise<AdminUserResponse> {
    return request<AdminUserResponse>(`/admin-users/${userId}/addresses`, {
      method: 'POST',
      body: payload,
    });
  },

  updateAdminUserAddress(
    userId: number,
    addressId: number,
    payload: AdminUserAddressPayload,
  ): Promise<AdminUserResponse> {
    return request<AdminUserResponse>(`/admin-users/${userId}/addresses/${addressId}`, {
      method: 'PUT',
      body: payload,
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

  providerWithdrawals(): Promise<ProviderWithdrawalsResponse> {
    return request<ProviderWithdrawalsResponse>('/profile/provider/withdrawals');
  },

  providerAutoApprove(): Promise<AutoApproveResponse> {
    return request<AutoApproveResponse>('/profile/provider/auto-approve');
  },

  updateProviderAutoApprove(value: number | null): Promise<AutoApproveResponse> {
    return request<AutoApproveResponse>('/profile/provider/auto-approve', {
      method: 'PUT',
      body: { auto_approve_max: value },
    });
  },

  providerApproveWithdrawal(id: number): Promise<ProviderWithdrawalActionResponse> {
    return request<ProviderWithdrawalActionResponse>(
      `/profile/provider/withdrawals/${id}/approve`,
      { method: 'POST' },
    );
  },

  providerRejectWithdrawal(id: number, reason?: string | null): Promise<ProviderWithdrawalActionResponse> {
    return request<ProviderWithdrawalActionResponse>(
      `/profile/provider/withdrawals/${id}/reject`,
      { method: 'POST', body: { reason: reason ?? null } },
    );
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

  // --- Mi Catalogo (gestion del comercio) ---

  providerCatalogPreview(): Promise<ProviderCatalogPreviewResponse> {
    return request<ProviderCatalogPreviewResponse>('/provider/catalog');
  },

  providerCatalogProducts(): Promise<ProviderCatalogProductsResponse> {
    return request<ProviderCatalogProductsResponse>('/provider/catalog/products');
  },

  providerCatalogCreateProduct(
    payload: ProviderCatalogProductPayload,
  ): Promise<ProviderCatalogProductResponse> {
    return request<ProviderCatalogProductResponse>('/provider/catalog/products', {
      method: 'POST',
      body: payload,
    });
  },

  providerCatalogUpdateProduct(
    id: number,
    payload: ProviderCatalogProductPayload,
  ): Promise<ProviderCatalogProductResponse> {
    return request<ProviderCatalogProductResponse>(`/provider/catalog/products/${id}`, {
      method: 'PUT',
      body: payload,
    });
  },

  providerCatalogUnits(): Promise<ProviderCatalogUnitsResponse> {
    return request<ProviderCatalogUnitsResponse>('/provider/catalog/units');
  },

  providerCatalogCreateCategory(name: string): Promise<ProviderCatalogCategoryResponse> {
    return request<ProviderCatalogCategoryResponse>('/provider/catalog/categories', {
      method: 'POST',
      body: { name },
    });
  },

  providerCatalogUpdateCategory(
    id: number,
    payload: { name?: string; sort_order?: number; is_active?: boolean },
  ): Promise<ProviderCatalogCategoryResponse> {
    return request<ProviderCatalogCategoryResponse>(`/provider/catalog/categories/${id}`, {
      method: 'PUT',
      body: payload,
    });
  },

  providerCatalogDeleteCategory(id: number): Promise<SimpleResponse> {
    return request<SimpleResponse>(`/provider/catalog/categories/${id}`, {
      method: 'DELETE',
    });
  },

  providerCatalogCreateVariant(
    productId: number,
    payload: ProviderCatalogVariantPayload,
  ): Promise<ProviderCatalogVariantResponse> {
    return request<ProviderCatalogVariantResponse>(
      `/provider/catalog/products/${productId}/variants`,
      { method: 'POST', body: payload },
    );
  },

  providerCatalogUpdateVariant(
    id: number,
    payload: ProviderCatalogVariantPayload,
  ): Promise<ProviderCatalogVariantResponse> {
    return request<ProviderCatalogVariantResponse>(`/provider/catalog/variants/${id}`, {
      method: 'PUT',
      body: payload,
    });
  },

  providerCatalogDeleteVariant(id: number): Promise<SimpleResponse> {
    return request<SimpleResponse>(`/provider/catalog/variants/${id}`, {
      method: 'DELETE',
    });
  },

  providerCatalogCreateOptionGroup(
    productId: number,
    payload: ProviderCatalogOptionGroupPayload,
  ): Promise<ProviderCatalogOptionGroupResponse> {
    return request<ProviderCatalogOptionGroupResponse>(
      `/provider/catalog/products/${productId}/option-groups`,
      { method: 'POST', body: payload },
    );
  },

  providerCatalogUpdateOptionGroup(
    id: number,
    payload: ProviderCatalogOptionGroupPayload,
  ): Promise<ProviderCatalogOptionGroupResponse> {
    return request<ProviderCatalogOptionGroupResponse>(
      `/provider/catalog/option-groups/${id}`,
      { method: 'PUT', body: payload },
    );
  },

  providerCatalogDeleteOptionGroup(id: number): Promise<SimpleResponse> {
    return request<SimpleResponse>(`/provider/catalog/option-groups/${id}`, {
      method: 'DELETE',
    });
  },

  providerCatalogCreateOption(
    groupId: number,
    payload: ProviderCatalogOptionPayload,
  ): Promise<ProviderCatalogOptionResponse> {
    return request<ProviderCatalogOptionResponse>(
      `/provider/catalog/option-groups/${groupId}/options`,
      { method: 'POST', body: payload },
    );
  },

  providerCatalogUpdateOption(
    id: number,
    payload: ProviderCatalogOptionPayload,
  ): Promise<ProviderCatalogOptionResponse> {
    return request<ProviderCatalogOptionResponse>(`/provider/catalog/options/${id}`, {
      method: 'PUT',
      body: payload,
    });
  },

  providerCatalogDeleteOption(id: number): Promise<SimpleResponse> {
    return request<SimpleResponse>(`/provider/catalog/options/${id}`, {
      method: 'DELETE',
    });
  },

  // --- Inventario (gestion de stock del comercio) ---

  providerInventory(): Promise<ProviderInventoryResponse> {
    return request<ProviderInventoryResponse>('/provider/inventory');
  },

  providerInventoryUpdateSettings(
    hasInventoryControl: boolean,
  ): Promise<ProviderInventorySettingsResponse> {
    return request<ProviderInventorySettingsResponse>('/provider/inventory/settings', {
      method: 'PUT',
      body: { has_inventory_control: hasInventoryControl },
    });
  },

  providerInventoryUpdateProduct(
    id: number,
    payload: ProviderInventoryProductPayload,
  ): Promise<ProviderInventoryProductResponse> {
    return request<ProviderInventoryProductResponse>(`/provider/inventory/products/${id}`, {
      method: 'PUT',
      body: payload,
    });
  },

  // --- Centro de Despacho (pedidos de comida del comercio) ---

  providerDispatchOrders(
    params: ProviderDispatchOrdersParams = {},
  ): Promise<ProviderDispatchOrdersResponse> {
    const query = new URLSearchParams();

    if (params.tab) query.set('tab', params.tab);
    if (params.status) query.set('status', params.status);
    if (params.search) query.set('search', params.search);
    if (params.date_from) query.set('date_from', params.date_from);
    if (params.date_to) query.set('date_to', params.date_to);
    if (params.page) query.set('page', String(params.page));
    if (params.per_page) query.set('per_page', String(params.per_page));

    const suffix = query.toString() ? `?${query.toString()}` : '';

    return request<ProviderDispatchOrdersResponse>(`/provider/dispatch/orders${suffix}`);
  },

  providerDispatchUpdateStatus(
    id: number,
    payload: ProviderDispatchStatusPayload,
  ): Promise<ProviderDispatchActionResponse> {
    return request<ProviderDispatchActionResponse>(
      `/provider/dispatch/orders/${id}/status`,
      { method: 'PATCH', body: payload },
    );
  },

  providerDispatchRateOrder(
    id: number,
    payload: MarketRateOrderPayload,
  ): Promise<MarketRateOrderResponse> {
    return request<MarketRateOrderResponse>(`/provider/dispatch/orders/${id}/rate`, {
      method: 'POST',
      body: payload,
    });
  },

  providerDispatchSendToPool(id: number): Promise<ProviderDispatchActionResponse> {
    return request<ProviderDispatchActionResponse>(
      `/provider/dispatch/orders/${id}/send-to-pool`,
      { method: 'POST' },
    );
  },

  // --- Retención (campañas automáticas de fidelización) ---

  providerMarketingRules(): Promise<ProviderMarketingRulesResponse> {
    return request<ProviderMarketingRulesResponse>('/provider/marketing/rules');
  },

  providerMarketingUpdateRule(
    payload: ProviderMarketingRulePayload,
  ): Promise<ProviderMarketingRuleResponse> {
    return request<ProviderMarketingRuleResponse>('/provider/marketing/rules', {
      method: 'PUT',
      body: payload,
    });
  },

  // --- Salud financiera (costos, margenes, insumos y fichas tecnicas) ---

  providerFinancialHealth(
    page = 1,
    perPage?: number,
  ): Promise<ProviderFinancialHealthResponse> {
    const query = new URLSearchParams({ page: String(page) });

    if (perPage) {
      query.set('per_page', String(perPage));
    }

    return request<ProviderFinancialHealthResponse>(
      `/provider/financial-health?${query.toString()}`,
    );
  },

  providerFinancialSupplies(): Promise<ProviderSuppliesResponse> {
    return request<ProviderSuppliesResponse>('/provider/financial-health/supplies');
  },

  providerFinancialSaveSupply(
    payload: ProviderSupplyPayload,
  ): Promise<ProviderSupplyResponse> {
    return request<ProviderSupplyResponse>('/provider/financial-health/supplies', {
      method: 'POST',
      body: payload,
    });
  },

  providerFinancialDeleteSupply(id: number): Promise<SimpleResponse> {
    return request<SimpleResponse>(`/provider/financial-health/supplies/${id}`, {
      method: 'DELETE',
    });
  },

  providerFinancialRecipe(productId: number): Promise<ProviderRecipeResponse> {
    return request<ProviderRecipeResponse>(
      `/provider/financial-health/products/${productId}/recipe`,
    );
  },

  providerFinancialSaveRecipe(
    productId: number,
    payload: ProviderRecipePayload,
  ): Promise<ProviderRecipeResponse> {
    return request<ProviderRecipeResponse>(
      `/provider/financial-health/products/${productId}/recipe`,
      { method: 'POST', body: payload },
    );
  },

  // --- Suscripciones del comercio (planes, MRR y cobros por confirmar) ---

  providerSubscriptionRevenue(months = 3): Promise<ProviderSubscriptionRevenueResponse> {
    return request<ProviderSubscriptionRevenueResponse>(
      `/provider/subscriptions/revenue?months=${months}`,
    );
  },

  providerSubscriptionPlans(): Promise<ProviderSubscriptionPlansResponse> {
    return request<ProviderSubscriptionPlansResponse>('/provider/subscriptions/plans');
  },

  providerSubscriptionSavePlan(
    payload: ProviderSubscriptionPlanPayload,
  ): Promise<ProviderSubscriptionPlanResponse> {
    return request<ProviderSubscriptionPlanResponse>('/provider/subscriptions/plans', {
      method: 'POST',
      body: payload,
    });
  },

  providerSubscriptionUpdatePlan(
    planId: number,
    payload: ProviderSubscriptionPlanPayload,
  ): Promise<ProviderSubscriptionPlanResponse> {
    return request<ProviderSubscriptionPlanResponse>(
      `/provider/subscriptions/plans/${planId}`,
      { method: 'PUT', body: payload },
    );
  },

  providerSubscriptionUpdatePayment(
    paymentId: number,
    payload: ProviderSubscriptionPaymentPayload,
  ): Promise<ProviderSubscriptionPaymentResponse> {
    return request<ProviderSubscriptionPaymentResponse>(
      `/provider/subscriptions/payments/${paymentId}`,
      { method: 'PUT', body: payload },
    );
  },

  // --- Terminal Central de Repartos (Driver Pro) ---

  driverStats(): Promise<DriverStatsResponse> {
    return request<DriverStatsResponse>('/driver/stats');
  },

  driverStatus(): Promise<DriverStatusResponse> {
    return request<DriverStatusResponse>('/driver/status');
  },

  driverUpdateStatus(payload: DriverStatusPayload): Promise<DriverStatusResponse> {
    return request<DriverStatusResponse>('/driver/status', {
      method: 'PUT',
      body: payload,
    });
  },

  driverAvailableOrders(): Promise<DriverOffersResponse> {
    return request<DriverOffersResponse>('/driver/orders/available');
  },

  driverMyOrders(): Promise<DriverActiveOrdersResponse> {
    return request<DriverActiveOrdersResponse>('/driver/orders/mine');
  },

  driverTakeOrder(id: number): Promise<DriverActionResponse> {
    return request<DriverActionResponse>(`/driver/orders/${id}/take`, {
      method: 'POST',
    });
  },

  driverPickupOrder(id: number): Promise<DriverActionResponse> {
    return request<DriverActionResponse>(`/driver/orders/${id}/pickup`, {
      method: 'POST',
    });
  },

  driverConfirmDelivery(id: number, deliveryPin: string): Promise<DriverActionResponse> {
    return request<DriverActionResponse>(`/driver/orders/${id}/confirm`, {
      method: 'POST',
      body: { delivery_pin: deliveryPin },
    });
  },

  driverWithdrawals(): Promise<DriverWithdrawalsResponse> {
    return request<DriverWithdrawalsResponse>('/driver/wallet/withdrawals');
  },

  driverRequestWithdrawal(payload: DriverWithdrawalPayload): Promise<DriverWithdrawalResponse> {
    return request<DriverWithdrawalResponse>('/driver/wallet/withdraw', {
      method: 'POST',
      body: payload,
    });
  },
};

export type { TrustedDriverCandidate };
