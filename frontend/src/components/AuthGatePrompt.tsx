"use client";

import { useTranslations } from "next-intl";
import { usePathname } from "@/i18n/navigation";
import Modal from "./Modal";
import { Link } from "@/i18n/navigation";

export default function AuthGatePrompt({
  open,
  onContinueAsGuest,
  onDismiss,
}: {
  open: boolean;
  onContinueAsGuest: () => void;
  onDismiss: () => void;
}) {
  const t = useTranslations("auth");
  const pathname = usePathname();
  const redirect = encodeURIComponent(pathname);

  return (
    <Modal open={open} onClose={onDismiss}>
      <div className="flex flex-col items-center gap-4 p-8 text-center">
        <h2 className="text-xl font-semibold text-brand-navy">{t("gateTitle")}</h2>
        <p className="text-sm text-slate-500">{t("gateMessage")}</p>
        <div className="mt-2 flex w-full flex-col gap-3">
          <Link
            href={`/connexion?redirect=${redirect}`}
            onClick={onDismiss}
            className="rounded-full bg-brand-navy px-6 py-3 text-center font-medium text-white transition hover:bg-brand-navy-light"
          >
            {t("login")}
          </Link>
          <Link
            href={`/inscription?redirect=${redirect}`}
            onClick={onDismiss}
            className="rounded-full border border-brand-navy px-6 py-3 text-center font-medium text-brand-navy transition hover:bg-brand-navy hover:text-white"
          >
            {t("register")}
          </Link>
        </div>
        <button
          type="button"
          onClick={onContinueAsGuest}
          className="mt-2 text-xs text-slate-400 underline-offset-2 hover:text-slate-600 hover:underline"
        >
          {t("continueAsGuest")}
        </button>
      </div>
    </Modal>
  );
}
