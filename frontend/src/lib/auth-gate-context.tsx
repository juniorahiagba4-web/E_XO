"use client";

import { createContext, useCallback, useContext, useMemo, useState, type ReactNode } from "react";
import AuthGatePrompt from "@/components/AuthGatePrompt";
import { useAuth } from "./auth-context";

const GUEST_ACK_KEY = "eventloc.guestAcknowledged";

type AuthGateContextValue = {
  /**
   * Runs `action` immediately if the visitor is authenticated or has already
   * chosen to continue as a guest this session; otherwise shows the sign-in
   * prompt and holds `action` until they either sign in/register (they leave
   * to do so, then must repeat the action) or pick "Continuer sans compte".
   */
  guard: (action: () => void) => void;
};

const AuthGateContext = createContext<AuthGateContextValue | null>(null);

export function AuthGateProvider({ children }: { children: ReactNode }) {
  const { user } = useAuth();
  const [pendingAction, setPendingAction] = useState<(() => void) | null>(null);

  const hasAcknowledgedGuest = useCallback(() => {
    try {
      return window.localStorage.getItem(GUEST_ACK_KEY) === "1";
    } catch {
      return false;
    }
  }, []);

  const guard = useCallback(
    (action: () => void) => {
      if (user || hasAcknowledgedGuest()) {
        action();
        return;
      }
      setPendingAction(() => action);
    },
    [user, hasAcknowledgedGuest],
  );

  const continueAsGuest = useCallback(() => {
    try {
      window.localStorage.setItem(GUEST_ACK_KEY, "1");
    } catch {
      // Ignore write failures (e.g. private browsing) — the prompt will
      // simply reappear next time, which is a harmless fallback.
    }
    const action = pendingAction;
    setPendingAction(null);
    action?.();
  }, [pendingAction]);

  const dismiss = useCallback(() => setPendingAction(null), []);

  const value = useMemo(() => ({ guard }), [guard]);

  return (
    <AuthGateContext.Provider value={value}>
      {children}
      <AuthGatePrompt
        open={pendingAction !== null}
        onContinueAsGuest={continueAsGuest}
        onDismiss={dismiss}
      />
    </AuthGateContext.Provider>
  );
}

export function useAuthGate() {
  const ctx = useContext(AuthGateContext);
  if (!ctx) throw new Error("useAuthGate must be used within an AuthGateProvider");
  return ctx;
}
