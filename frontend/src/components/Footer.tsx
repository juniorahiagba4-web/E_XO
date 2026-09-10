import { useTranslations } from "next-intl";
import { WHATSAPP_NUMBER } from "@/lib/config";

export default function Footer() {
  const t = useTranslations("footer");
  const year = new Date().getFullYear();

  return (
    <footer className="border-t border-slate-200 bg-slate-50">
      <div className="mx-auto flex max-w-6xl flex-col gap-2 px-4 py-8 text-sm text-slate-500 sm:flex-row sm:items-center sm:justify-between">
        <p>© {year} EventLoc. {t("rights")}</p>
        <p>{t("whatsapp")}: +{WHATSAPP_NUMBER}</p>
      </div>
    </footer>
  );
}
