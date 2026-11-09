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
