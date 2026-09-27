import { API_URL } from "./config";
import type {
  AuthUser,
  AvailabilityResponse,
  Category,
  ContactMessagePayload,
  CreateReservationPayload,
  DeliverySlot,
  Item,
  LoginPayload,
  RegisterPayload,
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
  init?: RequestInit & { token?: string | null },
): Promise<T> {
  const { token, ...requestInit } = init ?? {};

  const res = await fetch(`${API_URL}${path}`, {
    ...requestInit,
    headers: {
      Accept: "application/json",
      "Accept-Language": locale,
      ...(requestInit.body ? { "Content-Type": "application/json" } : {}),
      ...(token ? { Authorization: `Bearer ${token}` } : {}),
      ...requestInit.headers,
    },
    cache: "no-store",
  });

  if (!res.ok) {
    const body = await res.json().catch(() => undefined);

    // A token that was valid earlier in the session can still expire mid-way
    // (Sanctum tokens now have a lifetime). Drop the stale copy so the next
    // page load reflects the logged-out state instead of retrying forever
    // with a dead token.
    if (res.status === 401 && token && typeof window !== "undefined") {
      window.localStorage.removeItem("eventloc.token");
    }

    throw new ApiError(
      body?.message ?? `Request failed with status ${res.status}`,
      res.status,
      body,
    );
  }

  if (res.status === 204) {
    return undefined as T;
  }

  return res.json() as Promise<T>;
}

export function getCategories(locale: string) {
  return apiFetch<{ data: Category[] }>("/categories", locale).then(
    (r) => r.data,
  );
}

export function getSiteSettings(locale: string) {
  return apiFetch<{ data: { homepage_hero_image_url: string | null } }>(
    "/site-settings",
    locale,
  ).then((r) => r.data);
}

export function getItems(
  locale: string,
  params: {
    category?: string;
    search?: string;
    sort?: "price_asc" | "price_desc" | "popular" | "newest";
    min_price?: string;
    max_price?: string;
  } = {},
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
  return apiFetch<{ data: Item; related: Item[] }>(`/items/${slug}`, locale).then(
    (r) => ({ item: r.data, related: r.related }),
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
  token?: string | null,
) {
  return apiFetch<{ data: Reservation }>("/reservations", locale, {
    method: "POST",
    body: JSON.stringify(payload),
    token,
  }).then((r) => r.data);
}

export function getReservation(locale: string, reference: string, email: string) {
  return apiFetch<{ data: Reservation }>(
    `/reservations/${reference}?email=${encodeURIComponent(email)}`,
    locale,
  ).then((r) => r.data);
}

export function getMyReservations(locale: string, token: string) {
  return apiFetch<{ data: Reservation[] }>("/me/reservations", locale, {
    token,
  }).then((r) => r.data);
}

export function sendContactMessage(locale: string, payload: ContactMessagePayload) {
  return apiFetch<{ data: { id: number; received_at: string } }>(
    "/contact-messages",
    locale,
    {
      method: "POST",
      body: JSON.stringify(payload),
    },
  ).then((r) => r.data);
}


export function register(locale: string, payload: RegisterPayload) {
  return apiFetch<{ token: string; user: AuthUser }>("/auth/register", locale, {
    method: "POST",
    body: JSON.stringify(payload),
  });
}

export function login(locale: string, payload: LoginPayload) {
  return apiFetch<{ token: string; user: AuthUser }>("/auth/login", locale, {
    method: "POST",
    body: JSON.stringify(payload),
  });
}

export function logout(locale: string, token: string) {
  return apiFetch<void>("/auth/logout", locale, {
    method: "POST",
    token,
  });
}

export function getMe(locale: string, token: string) {
  return apiFetch<{ user: AuthUser }>("/auth/me", locale, { token }).then(
    (r) => r.user,
  );
}

export function getMyFavorites(locale: string, token: string) {
  return apiFetch<{ data: Item[] }>("/me/favorites", locale, { token }).then(
    (r) => r.data,
  );
}

export function toggleFavorite(locale: string, itemId: number, token: string) {
  return apiFetch<{ favorited: boolean }>(`/items/${itemId}/favorite`, locale, {
    method: "POST",
    token,
  });
}

export { ApiError };
