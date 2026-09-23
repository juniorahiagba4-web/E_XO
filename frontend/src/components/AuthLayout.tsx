import type { ReactNode } from "react";
import { useTranslations } from "next-intl";

function BoxIcon() {
  return (
    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" strokeWidth="1.8" aria-hidden>
      <path strokeLinecap="round" strokeLinejoin="round" d="M21 8l-9-5-9 5 9 5 9-5Z" />
      <path strokeLinecap="round" strokeLinejoin="round" d="M3 8v8l9 5 9-5V8" />
      <path strokeLinecap="round" strokeLinejoin="round" d="M12 13v8" />
    </svg>
  );
}

function HeartIcon() {
  return (
    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" strokeWidth="1.8" aria-hidden>
      <path
        strokeLinecap="round"
        strokeLinejoin="round"
        d="M12 20.5s-7.5-4.6-9.6-9.1C1 8.1 2.4 4.9 5.6 4.1c2-.5 3.9.3 5 1.9l1.4 2 1.4-2c1.1-1.6 3-2.4 5-1.9 3.2.8 4.6 4 3.2 7.3-2.1 4.5-9.6 9.1-9.6 9.1Z"
      />
    </svg>
  );
}

function TagIcon() {
  return (
    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" strokeWidth="1.8" aria-hidden>
      <path strokeLinecap="round" strokeLinejoin="round" d="M20.6 12.6 12.7 20.5a2 2 0 0 1-2.8 0l-6.4-6.4a2 2 0 0 1 0-2.8L11.4 3.4a2 2 0 0 1 1.4-.6H19a2 2 0 0 1 2 2v6.6a2 2 0 0 1-.4 1.2Z" />
      <circle cx="16" cy="8" r="1.2" fill="currentColor" stroke="none" />
    </svg>
  );
}

function TruckIcon() {
  return (
    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" strokeWidth="1.8" aria-hidden>
      <path strokeLinecap="round" strokeLinejoin="round" d="M3 16V6a1 1 0 0 1 1-1h9v11" />
      <path strokeLinecap="round" strokeLinejoin="round" d="M13 9h4l4 4v3h-8" />
      <circle cx="7.5" cy="17.5" r="1.8" />
      <circle cx="17.5" cy="17.5" r="1.8" />
    </svg>
  );
}

export default function AuthLayout({
  badge,
  title,
  subtitle,
  children,
}: {
  badge: string;
  title: string;
  subtitle: string;
  children: ReactNode;
}) {
  const t = useTranslations("auth");

  const features = [
    { Icon: BoxIcon, label: t("featureOrders") },
    { Icon: HeartIcon, label: t("featureFavorites") },
    { Icon: TagIcon, label: t("featureOffers") },
    { Icon: TruckIcon, label: t("featureDelivery") },
  ];

  return (
    <div className="grid flex-1 lg:grid-cols-2">
      <div className="relative hidden flex-col justify-center gap-10 overflow-hidden bg-brand-navy px-12 py-16 text-white lg:flex">
        <div className="absolute -right-24 -top-24 h-72 w-72 rounded-full bg-brand-gold/10" />
        <div className="absolute -bottom-32 -left-16 h-72 w-72 rounded-full bg-brand-gold/10" />

        <div className="relative flex flex-col gap-5">
          <span className="w-fit rounded-full bg-white/10 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-brand-gold">
            {badge}
          </span>
          <h1
            className="max-w-md text-4xl leading-tight text-white"
            style={{ fontFamily: "var(--font-archivo-black)" }}
          >
            {title}
          </h1>
          <p className="max-w-sm text-slate-300">{subtitle}</p>
        </div>

        <div className="relative flex flex-col gap-4">
          {features.map(({ Icon, label }) => (
            <div key={label} className="flex items-center gap-3">
              <span className="flex h-9 w-9 items-center justify-center rounded-lg bg-white/10 text-brand-gold">
                <Icon />
              </span>
              <span className="text-sm font-medium text-slate-100">{label}</span>
            </div>
          ))}
        </div>
      </div>

      <div className="flex items-center justify-center bg-white px-4 py-16">
        <div className="w-full max-w-sm">{children}</div>
      </div>
    </div>
  );
}
