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
