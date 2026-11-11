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
  has_provider: boolean;
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

export type ProviderSubgroup = {
  id: number;
  description: string;
};

export type ProviderServiceType = {
  code: 'EMERGENCY' | 'SCHEDULED' | 'QUOTE' | string;
  name: string;
};

export type ProviderCard = {
  id: number;
  business_name: string;
  description: string | null;
  zone: string | null;
  rating: number | null;
  ratings_count: number;
  promo: string | null;
  phone: string | null;
  whatsapp: string | null;
  banner_image: string | null;
  publicidad_image: string | null;
  is_verified: boolean;
  base_visit_fee: number | null;
  is_available_now: boolean;
  availability_label: string;
  subgroups: ProviderSubgroup[];
  service_types: ProviderServiceType[];
  is_favorite: boolean;
};

export type ProviderServiceItem = {
  id: number;
  name: string;
  description: string | null;
};

export type ProviderImageItem = {
  id: number;
  image_type: string | null;
  is_primary: boolean;
  url: string | null;
};

export type ProviderDetail = ProviderCard & {
  hours: Record<string, unknown> | null;
  eta_minutes: number;
  emergency_count: number;
  services: ProviderServiceItem[];
  images: ProviderImageItem[];
};

export type PaginationMeta = {
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
  /** Total con los filtros aplicados, antes del tab de favoritos. */
  total_count?: number;
  /** Favoritos del usuario con los filtros aplicados. */
  favorites_count?: number;
};

export type ProvidersResponse = {
  success: boolean;
  data: ProviderCard[];
  meta: PaginationMeta;
};

export type ProviderDetailResponse = {
  success: boolean;
  data: ProviderDetail;
};

export type ToggleProviderFavoriteResponse = {
  success: boolean;
  is_favorite: boolean;
  favorites_count: number;
};

export type ProvidersParams = {
  search?: string;
  type?: 'EMERGENCY' | 'SCHEDULED' | 'QUOTE';
  favorite?: boolean;
  per_page?: number;
  page?: number;
};

export type ServiceSubgroup = {
  id: number;
  description: string;
  group_id: number;
  providers_count: number;
};

export type ServiceSubgroupsResponse = {
  success: boolean;
  data: ServiceSubgroup[];
  service_types: { code: string; name: string }[];
};

export type ServiceOrderType = 'EMERGENCY' | 'SCHEDULED' | 'QUOTE';

export type OrderMerchant = {
  id: number;
  business_name: string;
  phone: string | null;
  whatsapp: string | null;
  zone: string | null;
  photo_url: string | null;
  contact_name: string | null;
  rating: number | null;
  is_verified: boolean;
} | null;

export type OrderQuoteProposal = {
  id: number;
  proposed_price: number | string;
  estimated_time: string | null;
  details: string | null;
  provider: { id: number; business_name: string } | null;
  created_at: string | null;
};

export type ServiceOrderItem = {
  id: number;
  order_type: ServiceOrderType | string;
  status: string;
  display_status: string;
  status_label: string;
  escrow_status: string | null;
  customer_id: number;
  merchant_id: number | null;
  cancellable: boolean;
  customer_rated: boolean;
  customer_rating: { stars: number; comment: string | null; created_at: string | null } | null;
  provider_rated: boolean;
  provider_rating: { stars: number; comment: string | null; created_at: string | null } | null;
  merchant: OrderMerchant;
  latitude: number | null;
  longitude: number | null;
  scheduled_at: string | null;
  fulfillment: string | null;
  payment_method: string | null;
  payment_mode: string | null;
  deposit_amount: number | string | null;
  total_amount: number | string | null;
  additional_cost: number | string | null;
  description: string | null;
  address: string | null;
  address_street: string | null;
  address_unit: string | null;
  address_neighborhood: string | null;
  estimate_details: string | null;
  photo_url: string | null;
  audio_url: string | null;
  completion_photo_url: string | null;
  attachments: { type: string; url: string | null }[];
  proposals: OrderQuoteProposal[];
  sub_group: { id: number; description: string; group_id: number };
  created_at: string | null;
};

export type ServiceOrdersResponse = {
  success: boolean;
  data: ServiceOrderItem[];
  meta: PaginationMeta;
  filters: {
    statuses: string[];
    order_types: string[];
  };
};

export type ProviderOrdersResponse = {
  success: boolean;
  data: {
    provider: {
      id: number;
      business_name: string;
      balance: number | null;
    };
    orders: ServiceOrderItem[];
  };
  meta: PaginationMeta;
  filters: {
    statuses: string[];
    order_types: string[];
  };
};

export type ServiceOrderResponse = {
  success: boolean;
  data: {
    order: ServiceOrderItem;
    statuses: string[];
  };
};

export type ServiceOrdersParams = {
  status?: string;
  order_type?: ServiceOrderType;
  per_page?: number;
  page?: number;
};

export type ReportHogarKpis = {
  /** Total gastado (cliente) o facturado (prestador) de los completados. */
  total_economico: number;
  /** Suma de señas retenidas en custodia. */
  senas_custodia: number;
  completados: number;
  cancelados: number;
  promedio_reputacion: number;
  total_resenas: number;
  /** Solo prestador: trabajos pendientes de aceptar. */
  pendientes?: number;
  /** Solo prestador: trabajos aceptados/en proceso. */
  en_proceso?: number;
  /** Solo prestador: porcentaje de cotizaciones completadas. */
  efectividad_cotizaciones?: number;
};

export type ReportHogarOrder = ServiceOrderItem & {
  quote?: {
    id: number;
    status: string | null;
    proposed_price: number | string | null;
    estimated_time: string | null;
    details: string | null;
  } | null;
  visit_fee?: number | string | null;
  completed_at?: string | null;
  customer?: {
    id: number;
    name: string;
    phone: string | null;
    email: string | null;
  } | null;
};

export type ReportHogarParams = {
  fecha_desde?: string;
  fecha_hasta?: string;
  estado?: string;
  tipo?: ServiceOrderType;
};

export type ReportHogarResponse = {
  success: boolean;
  is_prestador: boolean;
  kpis: ReportHogarKpis;
  data: ReportHogarOrder[];
  filters: {
    statuses: string[];
    order_types: string[];
    estado: string | null;
    tipo: string | null;
    fecha_desde: string | null;
    fecha_hasta: string | null;
  };
  meta: {
    total: number;
    generated_at: string;
  };
};

export type AppNotification = {
  id: number;
  type: string;
  title: string;
  body: string | null;
  data: { order_id?: number; url?: string; [key: string]: unknown } | null;
  read_at: string | null;
  created_at: string | null;
};

export type NotificationsResponse = {
  success: boolean;
  data: AppNotification[];
  meta: PaginationMeta & { unread_count: number };
};

export type MenuPage = {
  id: number;
  description: string;
  url: string | null;
};

export type MenuModule = {
  id: number;
  description: string;
  pages: MenuPage[];
};

export type MenuResponse = {
  success: boolean;
  managed_user: {
    id: number;
    name: string;
    email: string;
  } | null;
  data: {
    modules: MenuModule[];
    first_page_url: string | null;
  };
};

export type NotificationResponse = {
  success: boolean;
  data: AppNotification;
};

export type ContactInquiryStatus = 'new' | 'in_progress' | 'resolved';

export type ContactInquiryType = 'consulta' | 'sugerencia' | 'reclamo';

export type ContactInquiry = {
  id: number;
  full_name: string;
  email: string;
  inquiry_type: ContactInquiryType | string;
  message: string;
  status: ContactInquiryStatus | string;
  created_at: string | null;
};

export type ContactInquiryCounts = {
  all: number;
  new: number;
  in_progress: number;
  resolved: number;
};

export type ContactInquiriesParams = {
  status?: ContactInquiryStatus | 'all';
  per_page?: number;
  page?: number;
};

export type ContactInquiriesResponse = {
  success: boolean;
  data: ContactInquiry[];
  meta: PaginationMeta;
  counts: ContactInquiryCounts;
  filters: {
    statuses: ContactInquiryStatus[];
  };
};

export type ContactInquiryResponse = {
  success: boolean;
  data: {
    contact_inquiry: ContactInquiry;
    statuses: ContactInquiryStatus[];
  };
};

export type UpdateContactInquiryStatusResponse = {
  success: boolean;
  message: string;
  contact_inquiry: ContactInquiry;
};

export type GroupStatusItem = {
  id: number;
  description: string;
  groups_count: number;
  can_delete: boolean;
};

export type GroupStatusesResponse = {
  success: boolean;
  data: GroupStatusItem[];
  summary: {
    total: number;
    in_use: number;
  };
};

export type GroupStatusResponse = {
  success: boolean;
  message: string;
  group_status: GroupStatusItem;
};

export type UserStatusItem = {
  id: number;
  status: string;
  users_count: number;
  can_delete: boolean;
};

export type UserStatusesResponse = {
  success: boolean;
  data: UserStatusItem[];
  summary: {
    total: number;
    in_use: number;
  };
};

export type UserStatusResponse = {
  success: boolean;
  message: string;
  user_status: UserStatusItem;
};

export type GroupStatusOption = {
  id: number;
  description: string;
};

export type GroupItem = {
  id: number;
  description: string;
  icon: string | null;
  group_status_id: number | null;
  group_status: GroupStatusOption | null;
  subgroups_count: number;
  can_delete: boolean;
};

export type GroupsResponse = {
  success: boolean;
  data: GroupItem[];
  filters: {
    group_statuses: GroupStatusOption[];
    default_group_status_id: number | null;
  };
};

export type GroupPayload = {
  description: string;
  icon?: string | null;
  group_status_id?: number | null;
};

export type GroupResponse = {
  success: boolean;
  message: string;
  group: GroupItem;
};

export type ModuleItem = {
  id: number;
  description: string;
  icon: string | null;
  pages_count: number;
  can_delete: boolean;
};

export type ModulesResponse = {
  success: boolean;
  data: ModuleItem[];
  summary: {
    total: number;
    in_use: number;
  };
};

export type ModulePayload = {
  description: string;
  icon?: string | null;
};

export type ModuleResponse = {
  success: boolean;
  message: string;
  module: ModuleItem;
};

export type PageModuleOption = {
  id: number;
  description: string;
};

export type PageItem = {
  id: number;
  description: string;
  url: string | null;
  module_id: number;
  module: PageModuleOption | null;
  users_count: number;
  can_delete: boolean;
};

export type PagesResponse = {
  success: boolean;
  data: PageItem[];
  filters: {
    modules: PageModuleOption[];
  };
  summary: {
    total: number;
    in_use: number;
  };
};

export type PagePayload = {
  description: string;
  url?: string | null;
  module_id: number;
};

export type PageResponse = {
  success: boolean;
  message: string;
  page: PageItem;
};

export type SubGroupOption = {
  id: number;
  description: string;
};

export type SubGroupItem = {
  id: number;
  description: string;
  group_id: number;
  group: SubGroupOption | null;
  providers_count: number;
  can_delete: boolean;
};

export type SubGroupsResponse = {
  success: boolean;
  data: SubGroupItem[];
  filters: {
    groups: SubGroupOption[];
  };
  summary: {
    total: number;
    in_use: number;
  };
};

export type SubGroupPayload = {
  description: string;
  group_id: number;
};

export type SubGroupResponse = {
  success: boolean;
  message: string;
  subgroup: SubGroupItem;
};

export type CurrencyCountryOption = {
  id: number;
  iso_code: string;
  name: string;
};

export type CurrencyItem = {
  id: number;
  country_id: number;
  code: string;
  name: string;
  symbol: string;
  symbol_position: 'before' | 'after';
  symbol_space: boolean;
  decimals: number;
  decimal_separator: string;
  thousand_separator: string;
  locale: string;
  is_active: boolean;
  country: CurrencyCountryOption | null;
  is_default: boolean;
  can_delete: boolean;
};

export type CurrenciesResponse = {
  success: boolean;
  data: CurrencyItem[];
  filters: {
    countries: CurrencyCountryOption[];
  };
  summary: {
    total: number;
    active: number;
    default_code: string;
  };
};

export type CurrencyPayload = {
  country_id: number;
  code: string;
  name: string;
  symbol: string;
  symbol_position: 'before' | 'after';
  symbol_space?: boolean;
  decimals: number;
  decimal_separator: string;
  thousand_separator: string;
  locale: string;
  is_active?: boolean;
};

export type CurrencyResponse = {
  success: boolean;
  message: string;
  currency: CurrencyItem;
};

export type TypeUserItem = {
  id: number;
  description: string;
  users_count: number;
  can_delete: boolean;
};

export type TypeUsersResponse = {
  success: boolean;
  data: TypeUserItem[];
  summary: {
    total: number;
    in_use: number;
  };
};

export type TypeUserPayload = {
  description: string;
};

export type TypeUserResponse = {
  success: boolean;
  message: string;
  type_user: TypeUserItem;
};

export type UnitOfMeasureItem = {
  id: number;
  name: string;
  symbol: string;
  base_conversion_factor: number;
  is_integer_only: boolean;
  products_count: number;
  supplies_count: number;
  inventory_movements_count: number;
  can_delete: boolean;
};

export type UnitsOfMeasureResponse = {
  success: boolean;
  data: UnitOfMeasureItem[];
  summary: {
    total: number;
    in_use: number;
  };
};

export type UnitOfMeasurePayload = {
  name: string;
  symbol: string;
  base_conversion_factor: number;
  is_integer_only?: boolean;
};

export type UnitOfMeasureResponse = {
  success: boolean;
  message: string;
  unit: UnitOfMeasureItem;
};

export type AdminUserAddress = {
  id: number;
  country_id: number | null;
  province_id: number | null;
  street: string | null;
  number: string | null;
};

export type AdminUserPageItem = {
  id: number;
  description: string;
  url: string | null;
  module?: string | null;
};

export type AdminUserModule = {
  id: number;
  description: string;
  pages: {
    id: number;
    description: string;
    url: string | null;
    module_id: number;
  }[];
};

export type AdminUserItem = {
  id: number;
  name: string;
  email: string;
  role: string | null;
  status: string | null;
  status_id: number | null;
  type_user_id: number | null;
  principal_role: string | null;
  is_principal: boolean;
  is_self: boolean;
  is_inactive: boolean;
  can_edit: boolean;
  page_ids: number[];
  pages: AdminUserPageItem[];
  primary_address: AdminUserAddress | null;
};

export type AdminUsersResponse = {
  success: boolean;
  account_email: string;
  managed_user: {
    id: number;
    name: string;
    email: string;
  } | null;
  actor: {
    id: number;
    is_admin: boolean;
  };
  data: AdminUserItem[];
  catalog: {
    modules: AdminUserModule[];
  };
  filters: {
    user_statuses: {
      id: number;
      status: string;
    }[];
    types: {
      id: number;
      description: string;
    }[];
  };
  limits: {
    max_secondary: number;
    secondary_count: number;
    can_add: boolean;
  };
};

export type AdminUserCreatePayload = {
  name: string;
  password: string;
  password_confirmation: string;
  user_status_id?: number | null;
};

export type AdminUserUpdatePayload = {
  name: string;
  email: string;
  user_status_id?: number | null;
  type_user_id?: number | null;
  password?: string;
  password_confirmation?: string;
};

export type AdminUserResponse = {
  success: boolean;
  message: string;
  user: AdminUserItem;
};

export type AdminUserPageResponse = {
  success: boolean;
  message: string;
  page_id: number;
  cascaded: boolean;
  user: AdminUserItem;
};

export type AdminUserAddressPayload = {
  country_id: number;
  province_id?: number | null;
  street?: string | null;
  number?: string | null;
};

export type TypeUserPagesType = {
  id: number;
  description: string;
  users_count: number;
  assigned_page_ids: number[];
};

export type TypeUserPagesPage = {
  id: number;
  description: string;
  url: string | null;
  module_id: number;
  module: string | null;
};

export type TypeUserPagesDataResponse = {
  success: boolean;
  types: TypeUserPagesType[];
  pages: TypeUserPagesPage[];
};

export type TypeUserUpdatePagesResponse = {
  success: boolean;
  message: string;
  users_count: number;
};

export type TypeUserBulkPagesResponse = {
  success: boolean;
  message: string;
  action: string;
  count?: number;
};

export type TypeUserPageRefResponse = {
  success: boolean;
  message: string;
  page_id: number;
};

export type ProfileUserPayload = {
  id: number;
  name: string;
  email: string;
  phone: string | null;
  role: string | null;
  status: string | null;
  is_admin: boolean;
  is_principal: boolean;
  has_provider: boolean;
  vehicle_type: string | null;
  vehicle_plate: string | null;
};

export type ProfileFlags = {
  role: string | null;
  is_admin: boolean;
  is_principal: boolean;
  is_prestador: boolean;
  is_cliente: boolean;
  is_repartidor: boolean;
  can_configure_business: boolean;
  can_see_address: boolean;
  can_manage_users: boolean;
  can_see_trusted_drivers: boolean;
  can_see_service_types: boolean;
  initial_panel: string;
};

export type ProfileServiceRow = {
  id: number;
  name: string;
  description: string | null;
  sort_order: number | null;
};

export type ProfileImageRow = {
  id: number;
  image_path: string;
  image_type: string | null;
  is_primary: boolean;
  sort_order: number | null;
  url: string | null;
};

export type ProfileProvider = {
  id: number;
  business_name: string;
  description: string | null;
  phone: string | null;
  whatsapp: string | null;
  zone: string | null;
  promo: string | null;
  hours: Record<string, { open?: boolean; shifts?: { from: string; to: string }[] }> | null;
  is_active: boolean;
  trusted_drivers_enabled: boolean;
  category_id: number | null;
  category: { id: number; description: string } | null;
  services: ProfileServiceRow[];
  images: ProfileImageRow[];
  subgroup_ids: number[];
  service_type_ids: number[];
};

export type ProfileAddress = {
  id: number;
  country_id: number | null;
  province_id: number | null;
  street: string | null;
  number: string | null;
  floor_apartment: string | null;
  postal_code: string | null;
  notes: string | null;
  latitude: number | null;
  longitude: number | null;
  is_primary: boolean;
  country: { id: number; name: string } | null;
  province: { id: number; name: string } | null;
};

export type VehicleOption = {
  value: string;
  label: string;
};

export type ProfileResponse = {
  success: boolean;
  user: ProfileUserPayload;
  flags: ProfileFlags;
  provider: ProfileProvider | null;
  address: ProfileAddress | null;
  address_shared: boolean;
  vehicle_types: VehicleOption[];
  default_vehicle_type: string;
};

export type ProfileUserResponse = {
  success: boolean;
  message: string;
  user: ProfileUserPayload;
};

export type ProfilePasswordResponse = {
  success: boolean;
  message: string;
};

export type ProfileProviderResponse = {
  success: boolean;
  message: string;
  provider: ProfileProvider;
};

export type ProfileVehicleResponse = {
  success: boolean;
  message: string;
  vehicle_type: string | null;
  vehicle_plate: string | null;
  label: string;
};

export type ProfileImageResponse = {
  success: boolean;
  message: string;
  image: ProfileImageRow;
};

export type ProfileAddressResponse = {
  success: boolean;
  message?: string;
  address: ProfileAddress | null;
  shared: boolean;
};

export type CountryOption = {
  id: number;
  iso_code: string;
  name: string;
};

export type CountriesResponse = {
  success: boolean;
  data: CountryOption[];
};

export type RegionOption = {
  id: number;
  name: string;
  type: string | null;
};

export type RegionsResponse = {
  success: boolean;
  data: RegionOption[];
};

export type ProfileServicesResponse = {
  success: boolean;
  services: ProfileServiceRow[];
};

export type ProfileServiceResponse = {
  success: boolean;
  message: string;
  service: ProfileServiceRow;
};

export type ProfileSubgroupsResponse = {
  success: boolean;
  groups: {
    id: number;
    description: string;
    subgroups: { id: number; description: string; group_id: number }[];
  }[];
  selected: number[];
};

export type ToggleSubgroupResponse = {
  success: boolean;
  message: string;
  selected: number[];
};

export type ServiceTypeOption = {
  id: number;
  code: string;
  name: string;
  description: string | null;
};

export type ProfileServiceTypesResponse = {
  success: boolean;
  service_types: ServiceTypeOption[];
  selected: number[];
};

export type TrustedDriverRow = {
  id: number;
  user_id: number;
  name: string | null;
  email: string | null;
  phone: string | null;
  vehicle: string | null;
  vehicle_label: string | null;
  vehicle_plate: string | null;
  status: string;
};

export type TrustedDriverCandidate = {
  id: number;
  name: string;
  email: string;
  phone: string | null;
  vehicle: string | null;
  vehicle_label: string | null;
  vehicle_plate: string | null;
};

export type TrustedDriversResponse = {
  success: boolean;
  provider: { id: number; business_name: string; trusted_drivers_enabled: boolean };
  drivers: TrustedDriverRow[];
  candidates: TrustedDriverCandidate[];
};

export type TrustedDriversSettingsResponse = {
  success: boolean;
  message: string;
  provider: { id: number; business_name?: string; trusted_drivers_enabled: boolean };
};

export type AccountPageRef = {
  id: number;
  description: string;
  url: string | null;
  module?: string | null;
  module_id?: number;
};

export type AccountUserRow = {
  id: number;
  name: string;
  email: string;
  role: string | null;
  status: string | null;
  status_id: number | null;
  is_principal: boolean;
  is_self: boolean;
  can_edit: boolean;
  page_ids: number[];
  pages: AccountPageRef[];
};

export type AccountCatalogModule = {
  id: number;
  description: string;
  pages: AccountPageRef[];
};

export type AccountUsersResponse = {
  success: boolean;
  data: AccountUserRow[];
  catalog: { modules: AccountCatalogModule[] };
  filters: { user_statuses: { id: number; status: string }[] };
  limits: { max_secondary: number; secondary_count: number; can_add: boolean };
};

export type AccountUserResponse = {
  success: boolean;
  message: string;
  user: AccountUserRow;
  page_id?: number;
  cascaded?: boolean;
};

export type ProviderPayload = {
  business_name: string;
  description?: string | null;
  phone?: string | null;
  whatsapp?: string | null;
  zone?: string | null;
  promo?: string | null;
  hours?: Record<string, unknown> | null;
};

export type AddressPayload = {
  country_id: number;
  province_id?: number | null;
  street?: string | null;
  number?: string | null;
  floor_apartment?: string | null;
  postal_code?: string | null;
  notes?: string | null;
  latitude?: number | null;
  longitude?: number | null;
};

// ============ MERCADO (COMERCIOS) ============

export type MarketSettings = {
  delivery_fee: number;
  max_items_per_order: number;
  max_quantity_per_item: number;
};

export type MarketShop = {
  id: number;
  business_name: string;
  description: string | null;
  rubro: string | null;
  zone: string | null;
  rating: number | null;
  promo: string | null;
  phone: string | null;
  whatsapp: string | null;
  hours: Record<string, unknown> | null;
  banner_image: string | null;
  categories_count: number;
  products_count: number;
  active_subscription_plans_count: number;
  publicidad_image: string | null;
  is_favorite: boolean;
};

export type MarketShopsParams = {
  module_id?: number;
  search?: string;
  favorite?: boolean;
  only_with_plans?: boolean;
};

export type MarketShopsResponse = {
  success: boolean;
  settings: MarketSettings;
  providers: MarketShop[];
  favorites_count: number;
  total_count: number;
};

export type MarketToggleFavoriteResponse = {
  success: boolean;
  is_favorite: boolean;
  favorites_count: number;
};

export type MarketProductVariant = {
  id: number;
  name: string;
  price: number;
};

export type MarketProductOption = {
  id: number;
  name: string;
  extra_price: number;
};

export type MarketProductOptionGroup = {
  id: number;
  name: string;
  min_choices: number;
  max_choices: number;
  is_required: boolean;
  options: MarketProductOption[];
};

export type MarketProduct = {
  id: number;
  name: string;
  description: string | null;
  price: number;
  image_url: string | null;
  is_out_of_stock: boolean;
  variants: MarketProductVariant[];
  option_groups: MarketProductOptionGroup[];
};

export type MarketCatalogCategory = {
  id: number;
  name: string;
  products: MarketProduct[];
};

export type MarketCatalogProvider = {
  id: number;
  business_name: string;
  description: string | null;
  zone: string | null;
  rating: number | null;
  promo: string | null;
  phone: string | null;
  whatsapp: string | null;
  hours: Record<string, unknown> | null;
  address: string | null;
  delivery_fee: number;
  category: { id: number; name: string } | null;
};

export type MarketCatalogResponse = {
  success: boolean;
  settings: MarketSettings;
  provider: MarketCatalogProvider;
  categories: MarketCatalogCategory[];
};

export type MarketAddress = {
  id: number;
  street: string | null;
  number: string | null;
  floor_apartment: string | null;
  postal_code: string | null;
  notes: string | null;
  is_primary: boolean;
  formatted: string;
  latitude: number | null;
  longitude: number | null;
};

export type MarketAddressesResponse = {
  success: boolean;
  addresses: MarketAddress[];
};

export type MarketOrderItemPayload = {
  product_id: number;
  variant_id?: number | null;
  quantity: number;
  options?: number[];
};

export type MarketOrderPayload = {
  provider_id: number;
  delivery_type: 'delivery' | 'takeaway';
  address_id?: number | null;
  payment_method: string;
  notes?: string | null;
  items: MarketOrderItemPayload[];
};

export type MarketOrderItem = {
  product_name: string;
  variant_name: string | null;
  quantity: number;
  unit_price: number;
  total_price: number;
  options: { group_name: string; option_name: string; extra_price: number }[];
};

export type MarketOrder = {
  id: number;
  order_number: string;
  status: string;
  delivery_type: 'delivery' | 'takeaway';
  subtotal: number;
  delivery_fee: number;
  total: number;
  payment_method: string;
  notes: string | null;
  provider: { id: number; business_name: string } | null;
  items: MarketOrderItem[];
};

export type MarketOrderResponse = {
  success: boolean;
  message: string;
  order: MarketOrder;
};

export type MarketPlanItem = {
  product_id: number;
  name: string | null;
  image_url: string | null;
  quantity: number;
  unit_price: number | null;
  line_total: number | null;
};

export type MarketPlan = {
  id: number;
  title: string;
  description: string | null;
  frequency: string;
  frequency_label: string;
  price: number;
  discount_percentage: number;
  discount_amount: number;
  charge_amount: number;
  capacity: number | null;
  available_slots: number | null;
  is_active: boolean;
  items: MarketPlanItem[];
};

export type MarketPlansResponse = {
  success: boolean;
  provider: { id: number; business_name: string };
  plans: MarketPlan[];
};

export type MarketSubscriptionItem = {
  product_id: number;
  name: string | null;
  image_url: string | null;
  quantity: number;
};

export type MarketSubscription = {
  id: number;
  status: string;
  next_delivery_date: string | null;
  preferred_delivery_day: number | null;
  preferred_delivery_time: string | null;
  payment_method: string | null;
  delivery_address_id: number | null;
  provider: { id: number; business_name: string } | null;
  plan: MarketPlan | null;
  items: MarketSubscriptionItem[];
};

export type MarketSubscriptionsResponse = {
  success: boolean;
  subscriptions: MarketSubscription[];
};

export type MarketSubscriptionPayload = {
  subscription_plan_id: number;
  delivery_address_id: number;
  payment_method: string;
  next_delivery_date: string;
  preferred_delivery_day?: number | null;
  preferred_delivery_time?: string | null;
  items?: { product_id: number; quantity: number }[];
};

export type MarketSubscriptionResponse = {
  success: boolean;
  message: string;
  subscription: MarketSubscription;
};

export type MarketMyOrderOption = {
  group_name: string;
  option_name: string;
  extra_price: number;
};

export type MarketMyOrderItem = {
  id: number;
  product_id: number | null;
  variant_id: number | null;
  product_name: string;
  variant_name: string | null;
  quantity: number;
  unit_price: number;
  total_price: number;
  options: MarketMyOrderOption[];
};

export type MarketMyOrderPayment = {
  id: number;
  provider: string | null;
  status: string;
  amount: number;
  created_at: string | null;
};

export type MarketMyOrderAddress = {
  id: number;
  street: string | null;
  number: string | null;
  floor_apartment: string | null;
  postal_code: string | null;
  notes: string | null;
};

export type MarketMyOrderProvider = {
  id: number;
  business_name: string;
  is_active: boolean;
  user_id: number;
  user: { id: number; name: string; phone: string | null } | null;
};

export type MarketMyOrderDriver = {
  id: number;
  name: string;
  phone: string | null;
};

export type MarketMyOrder = {
  id: number;
  order_number: string;
  status: string;
  delivery_type: 'delivery' | 'takeaway';
  subtotal: number;
  delivery_fee: number;
  discount: number;
  total: number;
  payment_method: string | null;
  notes: string | null;
  created_at: string;
  provider: MarketMyOrderProvider | null;
  driver: MarketMyOrderDriver | null;
  address: MarketMyOrderAddress | null;
  items: MarketMyOrderItem[];
  payments: MarketMyOrderPayment[];
  pickup_address: string | null;
  customer_rated: boolean;
  customer_rating: { stars: number; comment: string | null } | null;
  provider_rated: boolean;
  provider_rating: { stars: number; comment: string | null } | null;
};

export type MarketMyOrdersParams = {
  status?: string;
};

export type MarketMyOrdersResponse = {
  success: boolean;
  statuses: string[];
  orders: MarketMyOrder[];
};

export type MarketRateOrderPayload = {
  stars: number;
  comment?: string | null;
};

export type MarketRateOrderResponse = {
  success: boolean;
  message: string;
  rating: {
    id: number;
    order_id: number;
    reviewer_id: number;
    ratee_id: number;
    stars: number;
    comment: string | null;
    created_at: string | null;
  };
  customer_rated: boolean;
  customer_rating: { stars: number; comment: string | null } | null;
  provider_rated: boolean;
  provider_rating: { stars: number; comment: string | null } | null;
  provider_global_rating: number | null;
};

// ---------------------------------------------------------------------------
// Centro de Despacho (pedidos del comercio)
// Espeja OrderController@index / ProviderDispatchController de la web.
// ---------------------------------------------------------------------------

export type ProviderDispatchCustomer = {
  id: number;
  name: string;
  email: string | null;
  phone: string | null;
};

export type ProviderDispatchDriver = {
  id: number;
  name: string;
  email: string | null;
  phone: string | null;
};

export type ProviderDispatchItemOption = {
  group_name: string;
  option_name: string;
  extra_price: number;
};

export type ProviderDispatchItem = {
  id: number;
  product_id: number | null;
  variant_id: number | null;
  product_name: string;
  variant_name: string | null;
  quantity: number;
  unit_price: number;
  total_price: number;
  options: ProviderDispatchItemOption[];
};

export type ProviderDispatchAddress = {
  id: number;
  street: string | null;
  number: string | null;
  floor_apartment: string | null;
  postal_code: string | null;
  notes: string | null;
};

export type ProviderDispatchPayment = {
  id: number;
  provider: string | null;
  status: string;
  amount: number;
  created_at: string | null;
};

export type ProviderDispatchOrder = {
  id: number;
  order_number: string;
  status: string;
  delivery_type: string;
  delivery_status: string | null;
  driver_id: number | null;
  released_to_pool_at: string | null;
  subtotal: number;
  delivery_fee: number;
  discount: number;
  total: number;
  payment_method: string | null;
  notes: string | null;
  created_at: string;
  user: ProviderDispatchCustomer | null;
  driver: ProviderDispatchDriver | null;
  address: ProviderDispatchAddress | null;
  items: ProviderDispatchItem[];
  payments: ProviderDispatchPayment[];
  customer_rated: boolean;
  customer_rating: { stars: number; comment: string | null } | null;
  provider_rated: boolean;
  provider_rating: { stars: number; comment: string | null } | null;
};

export type ProviderDispatchOrdersParams = {
  tab?: 'today' | 'history';
  status?: string;
  search?: string;
  date_from?: string;
  date_to?: string;
  page?: number;
  per_page?: number;
};

export type ProviderDispatchCounts = {
  today: number;
  history: number;
  pending: number;
  in_preparation: number;
};

export type ProviderDispatchPagination = {
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
};

export type ProviderDispatchOrdersResponse = {
  success: boolean;
  provider: {
    id: number;
    business_name: string;
    is_active: boolean;
    trusted_drivers_enabled: boolean;
  };
  statuses: string[];
  orders: ProviderDispatchOrder[];
  pagination: ProviderDispatchPagination;
  counts: ProviderDispatchCounts;
};

export type ProviderDispatchStatusPayload = {
  status: string;
  delivery_type?: string;
  driver_id?: number | null;
};

export type ProviderDispatchActionResponse = {
  success: boolean;
  message: string;
  status?: string;
  order: ProviderDispatchOrder;
};

// ---------------------------------------------------------------------------
// Retención (campañas de fidelización del comercio)
// Espeja MarketingController@index/update del panel web.
// ---------------------------------------------------------------------------

export type ProviderMarketingRuleType =
  | 'INACTIVE_CUSTOMER'
  | 'RECURRING_DAY_REMINDER'
  | 'WELCOME_BACK';

export type ProviderMarketingRule = {
  id: number;
  rule_type: ProviderMarketingRuleType;
  days_inactive: number | null;
  day_of_week: number | null;
  message_template: string;
  discount_code: string | null;
  is_enabled: boolean;
  default_template: string | null;
  created_at: string | null;
  updated_at: string | null;
};

export type ProviderMarketingRulesResponse = {
  success: boolean;
  rules: ProviderMarketingRule[];
  summary: {
    total: number;
    enabled: number;
  };
  types: ProviderMarketingRuleType[];
  default_templates: Partial<Record<ProviderMarketingRuleType, string>>;
};

export type ProviderMarketingRulePayload = {
  rule_type: ProviderMarketingRuleType;
  days_inactive?: number | null;
  day_of_week?: number | null;
  message_template: string;
  discount_code?: string | null;
  is_enabled: boolean;
};

export type ProviderMarketingRuleResponse = {
  success: boolean;
  message: string;
  rule: ProviderMarketingRule;
};

// ---------------------------------------------------------------------------
// Reportes Comercio (cliente de Mercado)
// Espejan los endpoints /api/v1/customer/reports/* de la web.
// ---------------------------------------------------------------------------

export type ReportComercioSavingsResponse = {
  success: boolean;
  summary: {
    total_saved: number;
    saved_with_subscriptions: number;
    saved_with_promotions: number;
    paid_subscription_cycles: number;
    orders_with_discount: number;
  };
  by_subscription: {
    subscription_id: number;
    plan_title: string | null;
    provider: string | null;
  }[];
};

export type ReportComercioSubscription = {
  subscription_id: number;
  plan_title: string | null;
  provider: string | null;
  status: string;
  status_label: string;
  frequency: string | null;
  charge_amount: number;
  next_delivery_date: string | null;
  preferred_delivery_day: number | null;
  preferred_delivery_time: string | null;
  payment_method: string | null;
  next_charge: {
    payment_id: number;
    total: number;
    period_start: string | null;
  } | null;
  products: {
    product_id: number | null;
    name: string | null;
    quantity: number;
  }[];
};

export type ReportComercioSubscriptionsResponse = {
  success: boolean;
  summary: {
    active_count: number;
    paused_count: number;
    monthly_commitment: number;
  };
  subscriptions: ReportComercioSubscription[];
};

export type ReportComercioHistoryResponse = {
  success: boolean;
  summary: {
    total_orders: number;
    total_spent: number;
    favorite_dish: string | null;
    favorite_provider: string | null;
  };
  top_products: {
    product_id: number;
    provider_id: number | null;
    name: string | null;
    times_ordered: number;
    total_quantity: number;
    total_spent: number;
    last_ordered_at: string | null;
    can_reorder_1_click: boolean;
  }[];
  top_providers: {
    provider_id: number;
    business_name: string | null;
    orders_count: number;
    total_spent: number;
    last_order_at: string | null;
    can_reorder_1_click: boolean;
  }[];
};

export type ReportComercioNutritionResponse = {
  success: boolean;
  period: {
    month: string;
    start_date: string;
    end_date: string;
  };
  summary: {
    total_delivered_orders: number;
    total_meals: number;
    total_spent: number;
    main_category: string | null;
  };
  categories: {
    category_id: number | null;
    category_name: string;
    meals_count: number;
    total_quantity: number;
    total_spent: number;
    top_products: { name: string; quantity: number }[];
  }[];
};

export type ReportComercioHistoryParams = {
  limit?: number;
};

export type ReportComercioNutritionParams = {
  /** Formato YYYY-MM. */
  month?: string;
};

// ---------------------------------------------------------------------------
// Mi Catalogo (gestion del comercio)
// Espeja /api/v1/provider/* del panel web bajo /provider/catalog del API movil.
// ---------------------------------------------------------------------------

export type ProviderCatalogProvider = {
  id: number;
  business_name: string;
  zone: string | null;
  is_active: boolean;
};

export type ProviderCatalogVariant = {
  id: number;
  name: string;
  price: number;
  is_available: boolean;
};

export type ProviderCatalogOption = {
  id: number;
  name: string;
  extra_price: number;
  is_available: boolean;
};

export type ProviderCatalogOptionGroup = {
  id: number;
  name: string;
  min_choices: number;
  max_choices: number;
  is_required: boolean;
  options: ProviderCatalogOption[];
};

export type ProviderCatalogUnitRef = {
  id: number;
  name: string;
  symbol: string;
  is_integer_only: boolean;
};

export type ProviderCatalogProduct = {
  id: number;
  name: string;
  description: string | null;
  price: number;
  is_available: boolean;
  category_id: number;
  category_name: string | null;
  track_stock: boolean;
  min_stock_alert: number | null;
  unit_of_measure_id: number | null;
  unit_of_measure: ProviderCatalogUnitRef | null;
  current_stock: number;
  allow_negative_stock: boolean;
  variants: ProviderCatalogVariant[];
  option_groups: ProviderCatalogOptionGroup[];
};

export type ProviderCatalogCategory = {
  id: number;
  name: string;
  sort_order: number;
  is_active: boolean;
  products_count: number;
};

export type ProviderCatalogUnit = {
  id: number;
  name: string;
  symbol: string;
  base_conversion_factor: number;
  is_integer_only: boolean;
  products_count: number;
};

export type ProviderCatalogPreviewCategory = {
  id: number;
  name: string;
  products: ProviderCatalogProduct[];
};

export type ProviderCatalogPreviewResponse = {
  success: boolean;
  provider: ProviderCatalogProvider;
  categories: ProviderCatalogPreviewCategory[];
};

export type ProviderCatalogProductsResponse = {
  success: boolean;
  provider: ProviderCatalogProvider;
  categories: ProviderCatalogCategory[];
  unit_of_measures: Omit<ProviderCatalogUnit, 'products_count'>[];
  products: ProviderCatalogProduct[];
};

export type ProviderCatalogUnitsResponse = {
  success: boolean;
  provider: { id: number; business_name: string };
  units: ProviderCatalogUnit[];
  products_with_unit: number;
};

export type ProviderCatalogProductPayload = {
  name: string;
  category_id: number;
  price: number;
  is_available?: boolean;
  track_stock?: boolean;
  min_stock_alert?: number | null;
  unit_of_measure_id?: number | null;
  initial_stock?: number;
  allow_negative_stock?: boolean;
};

export type ProviderCatalogProductResponse = {
  success: boolean;
  message: string;
  product: ProviderCatalogProduct;
};

export type ProviderCatalogCategoryResponse = {
  success: boolean;
  message?: string;
  category: ProviderCatalogCategory;
};

export type ProviderCatalogVariantPayload = {
  name: string;
  price: number;
  is_available?: boolean;
};

export type ProviderCatalogVariantResponse = {
  success: boolean;
  message: string;
  variant: ProviderCatalogVariant & { product_id: number };
};

export type ProviderCatalogOptionGroupPayload = {
  name: string;
  min_choices: number;
  max_choices: number;
  is_required: boolean;
};

export type ProviderCatalogOptionGroupResponse = {
  success: boolean;
  message: string;
  group: ProviderCatalogOptionGroup & { product_id: number };
};

export type ProviderCatalogOptionPayload = {
  name: string;
  extra_price: number;
  is_available?: boolean;
};

export type ProviderCatalogOptionResponse = {
  success: boolean;
  message: string;
  option: ProviderCatalogOption & { option_group_id: number };
};

// ---------------------------------------------------------------------------
// Inventario (gestion de stock del comercio)
// Espeja /api/v1/provider/inventory del panel web bajo /provider/inventory.
// ---------------------------------------------------------------------------

export type ProviderInventoryProvider = {
  id: number;
  business_name: string;
  has_inventory_control: boolean;
};

export type ProviderInventoryUnit = {
  id: number;
  name: string;
  symbol: string;
  base_conversion_factor: number;
  is_integer_only: boolean;
};

export type ProviderInventoryItem = {
  product_id: number;
  name: string;
  category_id: number;
  category_name: string | null;
  is_available: boolean;
  track_stock: boolean;
  min_stock_alert: number | null;
  unit_of_measure: ProviderInventoryUnit | null;
  current_stock: number;
  reserved_stock: number;
  available_stock: number;
  allow_negative_stock: boolean;
  low_stock: boolean;
};

export type ProviderInventoryCategory = {
  id: number;
  name: string;
  sort_order: number;
  is_active: boolean;
  products_count: number;
};

export type ProviderInventoryResponse = {
  success: boolean;
  provider: ProviderInventoryProvider;
  categories: ProviderInventoryCategory[];
  low_stock_count: number;
  unit_of_measures: ProviderInventoryUnit[];
  products: ProviderInventoryItem[];
};

export type ProviderInventorySettingsResponse = {
  success: boolean;
  message: string;
  provider: ProviderInventoryProvider;
};

export type ProviderInventoryProductPayload = {
  track_stock: boolean;
  current_stock?: number;
  min_stock_alert?: number | null;
  unit_of_measure_id?: number | null;
  allow_negative_stock?: boolean;
  notes?: string;
};

export type ProviderInventoryProductResponse = {
  success: boolean;
  message: string;
  product: ProviderInventoryItem;
  current_stock: number;
  low_stock: boolean;
};


// ---------------------------------------------------------------------------
// Salud financiera (costos, margenes, insumos y fichas tecnicas)
// Espeja RecipeController del panel web bajo /provider/financial-health.
// ---------------------------------------------------------------------------

export type ProviderFinancialAlert = {
  code: string;
  severity: 'warning' | 'info' | string;
  message: string;
};

export type ProviderFinancialProduct = {
  product_id: number;
  name: string;
  sale_price: number;
  production_cost: number;
  profit_margin: number | null;
  has_recipe: boolean;
  alerts: ProviderFinancialAlert[];
};

export type ProviderFinancialSummary = {
  total_products: number;
  products_with_alert: number;
  min_margin_percent: number;
};

export type ProviderFinancialPagination = {
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
};

export type ProviderFinancialHealthResponse = {
  success: boolean;
  summary: ProviderFinancialSummary;
  pagination: ProviderFinancialPagination;
  products: ProviderFinancialProduct[];
};

export type ProviderSupplyUnit = {
  id: number;
  name: string;
  symbol: string;
  is_integer_only: boolean;
};

export type ProviderSupplyUnitRef = {
  id: number;
  name: string;
  symbol: string;
};

export type ProviderSupply = {
  id: number;
  name: string;
  unit_of_measure_id: number;
  unit_of_measure: ProviderSupplyUnitRef | null;
  cost_per_unit: number;
  recipes_count: number;
};

export type ProviderSuppliesResponse = {
  success: boolean;
  supplies: ProviderSupply[];
  units_of_measure: ProviderSupplyUnit[];
};

export type ProviderSupplyPayload = {
  id?: number;
  name: string;
  unit_of_measure_id: number;
  cost_per_unit: number;
};

export type ProviderSupplyResponse = {
  success: boolean;
  message: string;
  supply: ProviderSupply;
};

export type ProviderRecipeItem = {
  id: number;
  supply_id: number;
  supply_name: string;
  unit_symbol: string | null;
  quantity_required: number;
  cost_per_unit: number;
  line_cost: number;
};

export type ProviderRecipe = {
  product_id: number;
  product_name: string;
  items: ProviderRecipeItem[];
};

export type ProviderRecipeResponse = {
  success: boolean;
  recipe: ProviderRecipe;
  production_cost: number;
  profit_margin: number | null;
};

export type ProviderRecipePayload = {
  items: {
    supply_id: number;
    quantity_required: number;
  }[];
};

// ---------------------------------------------------------------------------
// Suscripciones del comercio (planes, MRR y cobros por confirmar)
// Espeja SubscriptionController del panel web bajo /provider/subscriptions.
// ---------------------------------------------------------------------------

export type ProviderSubscriptionFrequency = 'DAILY' | 'WEEKLY' | 'BIWEEKLY' | 'MONTHLY';

export type ProviderSubscriptionPlanItem = {
  product_id: number;
  name: string | null;
  image_path: string | null;
  quantity: number;
  unit_price: number | null;
  line_total: number | null;
};

export type ProviderSubscriptionPlan = {
  id: number;
  title: string;
  description: string | null;
  frequency: ProviderSubscriptionFrequency | string;
  price: number;
  discount_percentage: number;
  discount_amount: number;
  charge_amount: number;
  capacity: number | null;
  available_slots: number | null;
  is_active: boolean;
  items: ProviderSubscriptionPlanItem[];
};

export type ProviderSubscriptionPlansResponse = {
  success: boolean;
  plans: ProviderSubscriptionPlan[];
};

export type ProviderSubscriptionPlanPayload = {
  id?: number;
  title: string;
  description?: string | null;
  frequency: ProviderSubscriptionFrequency;
  price: number;
  discount_percentage?: number | null;
  capacity?: number | null;
  is_active: boolean;
  items: {
    product_id: number;
    quantity: number;
  }[];
};

export type ProviderSubscriptionPlanResponse = {
  success: boolean;
  message: string;
  plan: ProviderSubscriptionPlan;
};

export type ProviderSubscriptionPlanRevenue = {
  plan_id: number;
  title: string | null;
  frequency: string | null;
  price: number;
  charge_amount: number;
  subscribers: number;
  capacity: number | null;
  available_slots: number | null;
  mrr: number;
};

export type ProviderSubscriptionRecurring = {
  mrr: number;
  mrr_basis: string;
  active_subscriptions: number;
  paused_subscriptions: number;
  payment_failed: number;
  cancelled: number;
  by_plan: ProviderSubscriptionPlanRevenue[];
  by_frequency: Record<string, number>;
};

export type ProviderSubscriptionCash = {
  collected_last_30_days: number;
  collected_previous_30_days: number;
  from_plans_last_30_days: number;
  delivery_fees_last_30_days: number;
  pending_amount: number;
  failed_last_30_days: number;
};

export type ProviderSubscriptionHistoryRow = {
  period: string;
  collected: number;
  pending: number;
  failed: number;
  refunded: number;
  charges: number;
};

export type ProviderSubscriptionPaymentSubscription = {
  id: number;
  status: string;
  plan: string | null;
};

export type ProviderSubscriptionPaymentCustomer = {
  id: number;
  name: string;
  email: string;
};

export type ProviderSubscriptionPayment = {
  id: number;
  status: string;
  gateway: string | null;
  attempt: number;
  subtotal: number;
  discount: number;
  delivery_fee: number;
  total: number;
  currency: string;
  period_start: string | null;
  period_end: string | null;
  paid_at: string | null;
  failure_reason: string | null;
  order_id: number | null;
  subscription: ProviderSubscriptionPaymentSubscription | null;
  customer: ProviderSubscriptionPaymentCustomer | null;
};

export type ProviderSubscriptionRevenueResponse = {
  success: boolean;
  currency: string;
  recurring: ProviderSubscriptionRecurring;
  cash: ProviderSubscriptionCash;
  history: ProviderSubscriptionHistoryRow[];
  pending_charges: ProviderSubscriptionPayment[];
};

export type ProviderSubscriptionPaymentPayload = {
  status: 'PAID' | 'FAILED';
  failure_reason?: string;
};

export type ProviderSubscriptionPaymentResponse = {
  success: boolean;
  message: string;
  payment: ProviderSubscriptionPayment;
};

// ---------------------------------------------------------------------------
// Terminal Central de Repartos (Driver Pro)
// Espeja la sección web `repartidor.blade.php` y DriverController (móvil).
// ---------------------------------------------------------------------------

export type DriverStats = {
  earnings_today: number;
  base_today: number;
  tips_today: number;
  completed: number;
  delivered_today: number;
  acceptance: number | null;
  rating: number | null;
  rating_count: number;
  wallet_balance: number;
};

export type DriverProfile = {
  id: number;
  name: string;
  initials: string;
  code: string;
  vehicle_type: string | null;
  vehicle_label: string;
  vehicle_plate: string | null;
  has_vehicle: boolean;
};

export type DriverStatsResponse = {
  success: boolean;
  stats: DriverStats;
  driver: DriverProfile;
  vehicle_types: VehicleOption[];
};

export type DriverStatus = {
  online: boolean;
  zone: string | null;
  latitude: number | null;
  longitude: number | null;
  accuracy: number | null;
  updated_at: string | null;
};

export type DriverStatusResponse = {
  success: boolean;
  message?: string;
  status: DriverStatus;
};

export type DriverStatusPayload = {
  online: boolean;
  zone?: string | null;
  latitude?: number | null;
  longitude?: number | null;
  accuracy?: number | null;
};

export type DriverOrderAddress = {
  id: number;
  street: string | null;
  number: string | null;
  floor_apartment: string | null;
  postal_code: string | null;
  notes: string | null;
  locality: string | null;
  latitude: number | null;
  longitude: number | null;
  full_line: string;
};

export type DriverOfferProvider = {
  id: number | null;
  business_name: string | null;
  phone: string | null;
  zone: string | null;
};

export type DriverOffer = {
  id: number;
  order_number: string;
  status: string;
  delivery_status: string | null;
  delivery_type: string | null;
  delivery_fee: number;
  total: number;
  payment_method: string | null;
  notes: string | null;
  created_at: string | null;
  provider: DriverOfferProvider;
  address: DriverOrderAddress | null;
};

export type DriverOffersResponse = {
  success: boolean;
  orders: DriverOffer[];
};

export type DriverActiveProvider = DriverOfferProvider & {
  pickup_address: DriverOrderAddress | null;
};

export type DriverActiveCustomer = {
  id: number | null;
  name: string | null;
  phone: string | null;
};

export type DriverActiveOrder = {
  id: number;
  order_number: string;
  status: string;
  delivery_status: string | null;
  delivery_type: string | null;
  delivery_fee: number;
  total: number;
  payment_method: string | null;
  notes: string | null;
  created_at: string | null;
  has_delivery_pin: boolean;
  provider: DriverActiveProvider;
  customer: DriverActiveCustomer;
  address: DriverOrderAddress | null;
};

export type DriverActiveOrdersResponse = {
  success: boolean;
  orders: DriverActiveOrder[];
};

export type DriverActionResponse = {
  success: boolean;
  message: string;
  order?: DriverActiveOrder;
  wallet?: { balance: number };
};