"use client";

import {
  createContext,
  useCallback,
  useContext,
  useEffect,
  useMemo,
  useState,
  type ReactNode,
} from "react";
import { useLocale } from "next-intl";
import { useAuth } from "./auth-context";
import * as api from "./api";
import type { Item } from "./types";

type FavoritesContextValue = {
  favoriteIds: number[];
  isFavorited: (itemId: number) => boolean;
  toggleFavorite: (item: Item) => Promise<void>;
};

const FavoritesContext = createContext<FavoritesContextValue | null>(null);

export function FavoritesProvider({ children }: { children: ReactNode }) {
  const locale = useLocale();
  const { user, token } = useAuth();
  const [favoriteIds, setFavoriteIds] = useState<number[]>([]);

  // Resetting/loading favorites in reaction to the auth state settling is the
  // whole point of this effect — there's no non-effect alternative here.
  /* eslint-disable react-hooks/set-state-in-effect */
  useEffect(() => {
    if (!user || !token) {
      setFavoriteIds([]);
      return;
    }
    api
      .getMyFavorites(locale, token)
      .then((items) => setFavoriteIds(items.map((i) => i.id)))
      .catch(() => undefined);
    // Only re-fetch when the signed-in user changes, not on every locale switch.
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [user?.id, token]);
  /* eslint-enable react-hooks/set-state-in-effect */

  const isFavorited = useCallback(
    (itemId: number) => favoriteIds.includes(itemId),
    [favoriteIds],
  );

  const toggleFavorite = useCallback(
    async (item: Item) => {
      if (!token) return;
      const wasFavorited = favoriteIds.includes(item.id);
      setFavoriteIds((prev) =>
        wasFavorited ? prev.filter((id) => id !== item.id) : [...prev, item.id],
      );
      try {
        await api.toggleFavorite(locale, item.id, token);
      } catch {
        // Revert on failure.
        setFavoriteIds((prev) =>
          wasFavorited ? [...prev, item.id] : prev.filter((id) => id !== item.id),
        );
      }
    },
    [favoriteIds, locale, token],
  );

  const value = useMemo(
    () => ({ favoriteIds, isFavorited, toggleFavorite }),
    [favoriteIds, isFavorited, toggleFavorite],
  );

  return <FavoritesContext.Provider value={value}>{children}</FavoritesContext.Provider>;
}

export function useFavorites() {
  const ctx = useContext(FavoritesContext);
  if (!ctx) throw new Error("useFavorites must be used within a FavoritesProvider");
  return ctx;
}
