"use client";

import {
  createContext,
  useCallback,
  useContext,
  useMemo,
  useState,
  type ReactNode,
} from "react";
import type { Item } from "./types";

export type CartLine = {
  item: Item;
  quantity: number;
};

type CartContextValue = {
  lines: CartLine[];
  eventStartDate: string;
  eventEndDate: string;
  setEventDates: (start: string, end: string) => void;
  addLine: (item: Item, quantity: number) => void;
  removeLine: (itemId: number) => void;
  updateQuantity: (itemId: number, quantity: number) => void;
  clear: () => void;
};

const CartContext = createContext<CartContextValue | null>(null);

export function CartProvider({ children }: { children: ReactNode }) {
  const [lines, setLines] = useState<CartLine[]>([]);
  const [eventStartDate, setEventStartDate] = useState("");
  const [eventEndDate, setEventEndDate] = useState("");

  const addLine = useCallback((item: Item, quantity: number) => {
    setLines((prev) => {
      const existing = prev.find((l) => l.item.id === item.id);
      if (existing) {
        return prev.map((l) =>
          l.item.id === item.id ? { ...l, quantity } : l,
        );
      }
      return [...prev, { item, quantity }];
    });
  }, []);

  const removeLine = useCallback((itemId: number) => {
    setLines((prev) => prev.filter((l) => l.item.id !== itemId));
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
  }, []);

  const value = useMemo(
    () => ({
      lines,
      eventStartDate,
      eventEndDate,
      setEventDates,
      addLine,
      removeLine,
      updateQuantity,
      clear,
    }),
    [
      lines,
      eventStartDate,
      eventEndDate,
      setEventDates,
      addLine,
      removeLine,
      updateQuantity,
      clear,
    ],
  );

  return <CartContext.Provider value={value}>{children}</CartContext.Provider>;
}

export function useCart() {
  const ctx = useContext(CartContext);
  if (!ctx) throw new Error("useCart must be used within a CartProvider");
  return ctx;
}
