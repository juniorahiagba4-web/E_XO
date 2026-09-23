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
import * as api from "./api";
import type { AuthUser, LoginPayload, RegisterPayload } from "./types";

const STORAGE_KEY = "eventloc.token";

type AuthContextValue = {
  user: AuthUser | null;
  token: string | null;
  loading: boolean;
  login: (payload: LoginPayload) => Promise<void>;
  register: (payload: RegisterPayload) => Promise<void>;
  logout: () => Promise<void>;
};

const AuthContext = createContext<AuthContextValue | null>(null);

export function AuthProvider({ children }: { children: ReactNode }) {
  const locale = useLocale();
  const [token, setToken] = useState<string | null>(null);
  const [user, setUser] = useState<AuthUser | null>(null);
  const [loading, setLoading] = useState(true);
  // See cart-context.tsx: this must be a state (not a ref) so the write-back
  // effect only fires after a real re-render with the loaded token — a ref
  // would still read as "hydrated" during React StrictMode's dev-only
  // double-invoke of the load effect below.
  const [hydrated, setHydrated] = useState(false);

  // Reading localStorage can only happen client-side, so this mount-time
  // hydration effect has no non-effect alternative that avoids a
  // server/client markup mismatch (see the comment on `hydrated` above).
  /* eslint-disable react-hooks/set-state-in-effect */
  useEffect(() => {
    const stored = window.localStorage.getItem(STORAGE_KEY);
    if (!stored) {
      setLoading(false);
      setHydrated(true);
      return;
    }

    api
      .getMe(locale, stored)
      .then((me) => {
        setToken(stored);
        setUser(me);
      })
      .catch(() => {
        window.localStorage.removeItem(STORAGE_KEY);
      })
      .finally(() => {
        setLoading(false);
        setHydrated(true);
      });
    // Only ever run once on mount: re-running when `locale` changes would
    // re-validate the token on every language switch for no reason.
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);
  /* eslint-enable react-hooks/set-state-in-effect */

  useEffect(() => {
    if (!hydrated) return;
    if (token) {
      window.localStorage.setItem(STORAGE_KEY, token);
    } else {
      window.localStorage.removeItem(STORAGE_KEY);
    }
  }, [hydrated, token]);

  const login = useCallback(
    async (payload: LoginPayload) => {
      const { token: newToken, user: newUser } = await api.login(locale, payload);
      setToken(newToken);
      setUser(newUser);
    },
    [locale],
  );

  const register = useCallback(
    async (payload: RegisterPayload) => {
      const { token: newToken, user: newUser } = await api.register(locale, payload);
      setToken(newToken);
      setUser(newUser);
    },
    [locale],
  );

  const logout = useCallback(async () => {
    if (token) {
      await api.logout(locale, token).catch(() => undefined);
    }
    setToken(null);
    setUser(null);
  }, [locale, token]);

  const value = useMemo(
    () => ({ user, token, loading, login, register, logout }),
    [user, token, loading, login, register, logout],
  );

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}

export function useAuth() {
  const ctx = useContext(AuthContext);
  if (!ctx) throw new Error("useAuth must be used within an AuthProvider");
  return ctx;
}
