export type Category = {
  id: number;
  name: string;
  slug: string;
  description: string | null;
  image_url: string | null;
};

export type ItemReview = {
  id: number;
  author_name: string;
  rating: string;
  comment: string | null;
  created_at: string | null;
};

export type ItemSpecification = {
  label: string;
  value: string;
};

export type Item = {
  id: number;
  category: Category | null;
  name: string;
  slug: string;
  description: string | null;
  specifications: ItemSpecification[];
  sku: string;
  unit_label: string;
  rental_price_per_day: string;
  sale_price: string | null;
  sale_price_on_request: boolean;
  rating: string | null;
  rating_count: number;
  reviews: ItemReview[];
  total_stock: number;
  purchasable_quantity?: number;
  min_rental_quantity: number;
  image_url: string | null;
  gallery: string[];
  is_favorited: boolean;
};

export type AvailabilityResponse = {
  item_id: number;
  start_date: string;
  end_date: string;
  total_stock: number;
  reserved_quantity: number;
  available_quantity: number;
};

export type DeliverySlot = {
  id: number;
  label: string;
  date: string;
  start_time: string;
  end_time: string;
  type: "delivery" | "pickup" | "both";
  remaining_capacity: number;
};

export type OrderType = "rental" | "purchase";

export type ReservationItemPayload = {
  item_id: number;
  quantity: number;
};

export type GuestCustomerPayload = {
  first_name: string;
  last_name: string;
  email: string;
  phone: string;
};

export type CreateReservationPayload = {
  type: OrderType;
  event_start_date?: string;
  event_end_date?: string;
  delivery_method: "delivery" | "pickup";
  delivery_address?: string;
  delivery_slot_id?: number | null;
  return_slot_id?: number | null;
  notes?: string;
  customer?: GuestCustomerPayload;
  items: ReservationItemPayload[];
};

export type Reservation = {
  reference: string;
  status: string;
  type: OrderType;
  event_start_date: string | null;
  event_end_date: string | null;
  subtotal?: string;
  discount?: string;
  total: string;
  currency: string;
  created_at?: string;
  quote_pdf_url: string | null;
  whatsapp_message: string;
  items?: {
    item_name: string | null;
    quantity: number;
    unit_price_per_day: string | null;
    unit_sale_price: string | null;
    subtotal: string;
    price_on_request: boolean;
  }[];
};

export type ContactMessagePayload = {
  name: string;
  email: string;
  phone?: string;
  subject?: string;
  message: string;
};

export type AuthUser = {
  id: number;
  name: string;
  email: string;
  first_name: string | null;
  last_name: string | null;
  phone: string | null;
};

export type RegisterPayload = {
  first_name: string;
  last_name: string;
  email: string;
  phone: string;
  password: string;
  password_confirmation: string;
};

export type LoginPayload = {
  email: string;
  password: string;
};

