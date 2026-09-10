import { API_URL } from "./config";
import type {
  AvailabilityResponse,
  Category,
  CreateReservationPayload,
  DeliverySlot,
  Item,
  Reservation,
} from "./types";

class ApiError extends Error {
  constructor(
    message: string,
    public status: number,
    public details?: unknown,
  ) {
    super(message);
  }
}

async function apiFetch<T>(
  path: string,
  locale: string,
  init?: RequestInit,
): Promise<T> {
  const res = await fetch(`${API_URL}${path}`, {
    ...init,
    headers: {
      Accept: "application/json",
      "Accept-Language": locale,
      ...(init?.body ? { "Content-Type": "application/json" } : {}),
      ...init?.headers,
    },
    cache: "no-store",
  });

  if (!res.ok) {
    const body = await res.json().catch(() => undefined);
    throw new ApiError(
      body?.message ?? `Request failed with status ${res.status}`,
      res.status,
      body,
    );
  }

  return res.json() as Promise<T>;
}

export function getCategories(locale: string) {
  return apiFetch<{ data: Category[] }>("/categories", locale).then(
    (r) => r.data,
  );
}

export function getItems(
  locale: string,
  params: { category?: string; search?: string } = {},
) {
  const query = new URLSearchParams(
    Object.entries(params).filter(([, v]) => Boolean(v)) as [
      string,
      string,
    ][],
  ).toString();
  return apiFetch<{ data: Item[] }>(
    `/items${query ? `?${query}` : ""}`,
    locale,
  ).then((r) => r.data);
}

export function getItem(locale: string, slug: string) {
  return apiFetch<{ data: Item }>(`/items/${slug}`, locale).then(
    (r) => r.data,
  );
}

export function getItemAvailability(
  locale: string,
  itemId: number,
  startDate: string,
  endDate: string,
) {
  return apiFetch<AvailabilityResponse>(
    `/items/${itemId}/availability?start_date=${startDate}&end_date=${endDate}`,
    locale,
  );
}

export function getDeliverySlots(
  locale: string,
  date: string,
  type: "delivery" | "pickup",
) {
  return apiFetch<{ data: DeliverySlot[] }>(
    `/delivery-slots?date=${date}&type=${type}`,
    locale,
  ).then((r) => r.data);
}

export function createReservation(
  locale: string,
  payload: CreateReservationPayload,
) {
  return apiFetch<{ data: Reservation }>("/reservations", locale, {
    method: "POST",
    body: JSON.stringify(payload),
  }).then((r) => r.data);
}

export function getReservation(locale: string, reference: string) {
  return apiFetch<{ data: Reservation }>(
    `/reservations/${reference}`,
    locale,
  ).then((r) => r.data);
}

export { ApiError };
