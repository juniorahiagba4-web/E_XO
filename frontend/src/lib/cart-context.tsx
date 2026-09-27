"use client";

import {
  createContext,
  useCallback,
  useContext,
  useEffect,
  useMemo,
  useRef,
  useState,
  type ReactNode,
} from "react";
import type { Item, OrderType } from "./types";

export type CartLine = {
  item: Item;
  quantity: number;
};

const STORAGE_KEY = "eventloc.cart";

type StoredCart = {
  lines: CartLine[];
  orderType: OrderType | null;
  eventStartDate: string;
  eventEndDate: string;
};

type CartContextValue = {
  lines: CartLine[];
  orderType: OrderType | null;
  eventStartDate: string;
  eventEndDate: string;
  setEventDates: (start: string, end: string) => void;
  /**
   * Adding an item in a different mode than what's already in the cart
   * replaces the cart's contents — a single order is either a rental or a
   * purchase, never both, since that's how the backend models an order.
   */
  addLine: (item: Item, quantity: number, mode: OrderType) => void;
  removeLine: (itemId: number) => void;
  updateQuantity: (itemId: number, quantity: number) => void;
  clear: () => void;
  /** Whether the cart drawer (CartPopup) is open — lets any component (e.g. an item card's cart shortcut) open it, not just the header's own cart button. */
  isOpen: boolean;
  openCart: () => void;
  closeCart: () => void;
};

const CartContext = createContext<CartContextValue | null>(null);

export function CartProvider({ children }: { children: ReactNode }) {
  const [lines, setLines] = useState<CartLine[]>([]);
  const [orderType, setOrderType] = useState<OrderType | null>(null);
  const [eventStartDate, setEventStartDate] = useState("");
  const [eventEndDate, setEventEndDate] = useState("");
  const [isOpen, setIsOpen] = useState(false);
  // A state (not a ref) so the write-back effect only fires after a real
  // re-render with the loaded values — a ref flipped inside the same effect
  // would still read as "hydrated" during React StrictMode's dev-only
  // double-invoke of this effect, causing the second pass to persist the
  // still-stale (empty) closure and wipe out what was just loaded.
  const [hydrated, setHydrated] = useState(false);

  // Reading localStorage can only happen client-side, so this mount-time
  // hydration effect has no non-effect alternative that avoids a
  // server/client markup mismatch.
  /* eslint-disable react-hooks/set-state-in-effect */
  useEffect(() => {
    try {
      const raw = window.localStorage.getItem(STORAGE_KEY);
      if (raw) {
        const stored = JSON.parse(raw) as StoredCart;
        setLines(stored.lines ?? []);
        setOrderType(stored.orderType ?? null);
        setEventStartDate(stored.eventStartDate ?? "");
        setEventEndDate(stored.eventEndDate ?? "");
      }
    } catch {
      // Ignore corrupted or inaccessible storage.
    } finally {
      setHydrated(true);
    }
  }, []);
  /* eslint-enable react-hooks/set-state-in-effect */

  useEffect(() => {
    if (!hydrated) return;
    try {
      const payload: StoredCart = { lines, orderType, eventStartDate, eventEndDate };
      window.localStorage.setItem(STORAGE_KEY, JSON.stringify(payload));
    } catch {
      // Ignore write failures (e.g. private browsing).
    }
  }, [hydrated, lines, orderType, eventStartDate, eventEndDate]);

  const orderTypeRef = useRef<OrderType | null>(orderType);
  useEffect(() => {
    orderTypeRef.current = orderType;
  }, [orderType]);

  const addLine = useCallback((item: Item, quantity: number, mode: OrderType) => {
    const isSwitchingMode = orderTypeRef.current !== null && orderTypeRef.current !== mode;

    setOrderType(mode);
    setLines((prev) => {
      // Switching between renting and buying starts a fresh cart — an order
      // is either a rental or a purchase, never both.
      const base = isSwitchingMode ? [] : prev;
      const existing = base.find((l) => l.item.id === item.id);
      if (existing) {
        return base.map((l) =>
          l.item.id === item.id ? { ...l, quantity } : l,
        );
      }
      return [...base, { item, quantity }];
    });
  }, []);

  const removeLine = useCallback((itemId: number) => {
    setLines((prev) => {
      const next = prev.filter((l) => l.item.id !== itemId);
      if (next.length === 0) setOrderType(null);
      return next;
    });
  }, []);

  const updateQuantity = useCallback((itemId: number, quantity: number) => {
    setLines((prev) =>
      prev.map((l) => (l.item.id === itemId ? { ...l, quantity } : l)),
    );
  }, []);

  const setEventDates = useCallback((start: string, end: string) => {
    setEventStartDate(start);
    setEventEndDate(end);
  }, []);

  const clear = useCallback(() => {
    setLines([]);
    setOrderType(null);
  }, []);

  const openCart = useCallback(() => setIsOpen(true), []);
  const closeCart = useCallback(() => setIsOpen(false), []);

  const value = useMemo(
    () => ({
      lines,
      orderType,
      eventStartDate,
      eventEndDate,
      setEventDates,
      addLine,
      removeLine,
      updateQuantity,
      clear,
      isOpen,
      openCart,
      closeCart,
    }),
    [
      lines,
      orderType,
      eventStartDate,
      eventEndDate,
      setEventDates,
      addLine,
      removeLine,
      updateQuantity,
      clear,
      isOpen,
      openCart,
      closeCart,
    ],
  );

  return <CartContext.Provider value={value}>{children}</CartContext.Provider>;
}

export function useCart() {
  const ctx = useContext(CartContext);
  if (!ctx) throw new Error("useCart must be used within a CartProvider");
  return ctx;
}
