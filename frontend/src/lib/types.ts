export type Category = {
  id: number;
  name: string;
  slug: string;
  description: string | null;
  image_url: string | null;
};

export type Item = {
  id: number;
  category: Category | null;
  name: string;
  slug: string;
  description: string | null;
  sku: string;
  unit_label: string;
  rental_price_per_day: string;
  deposit_amount: string | null;
  total_stock: number;
  min_rental_quantity: number;
  image_url: string | null;
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

export type ReservationItemPayload = {
  item_id: number;
  quantity: number;
};

export type CreateReservationPayload = {
  event_start_date: string;
  event_end_date: string;
  delivery_method: "delivery" | "pickup";
  delivery_address?: string;
  delivery_slot_id?: number | null;
  return_slot_id?: number | null;
  notes?: string;
  customer: {
    first_name: string;
    last_name: string;
    email: string;
    phone: string;
  };
  items: ReservationItemPayload[];
};

export type Reservation = {
  reference: string;
  status: string;
  event_start_date: string;
  event_end_date: string;
  total: string;
  currency: string;
  quote_pdf_url: string | null;
  whatsapp_message: string;
};
