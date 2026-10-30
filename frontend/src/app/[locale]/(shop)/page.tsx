import { useTranslations } from "next-intl";
import { getTranslations } from "next-intl/server";
import { Link } from "@/i18n/navigation";
import { buildWhatsappLink } from "@/lib/config";
import { getSiteSettings } from "@/lib/api";
import CatalogueBrowser from "@/components/CatalogueBrowser";

export async function generateMetadata({
  params,
}: PageProps<"/[locale]">) {
  const { locale } = await params;
  const t = await getTranslations({ locale, namespace: "home" });
  return { title: t("heroTitle") };
}

export default async function HomePage({
  params,
  searchParams,
}: PageProps<"/[locale]">) {
  const { locale } = await params;
  const { category, search, sort, min_price, max_price } = await searchParams;
  const settings = await getSiteSettings(locale).catch(() => ({ homepage_hero_image_url: null }));

  return (
    <div>
      <Hero backgroundImage={settings.homepage_hero_image_url} />

      <WhyUs />

      <section className="mx-auto max-w-6xl px-4 py-12">
        <CatalogueBrowser
          locale={locale}
          basePath="/"
          category={typeof category === "string" ? category : undefined}
          search={typeof search === "string" ? search : undefined}
          sort={typeof sort === "string" ? sort : undefined}
          minPrice={typeof min_price === "string" ? min_price : undefined}
          maxPrice={typeof max_price === "string" ? max_price : undefined}
        />
      </section>

      <HowItWorks />

      <RentalTerms />
    </div>
  );
}

type WhyIcon = "sparkles" | "grid" | "truck" | "document" | "box";
type WhyItem = { icon: WhyIcon; title: string; text: string };
type TermItem = { title: string; text: string };

const WHY_ICON_PATHS: Record<WhyIcon, string> = {
  sparkles:
    "M9.8 3.5 11 7.6a2 2 0 0 0 1.4 1.4l4.1 1.2-4.1 1.2a2 2 0 0 0-1.4 1.4L9.8 17l-1.2-4.2a2 2 0 0 0-1.4-1.4L3 10.2 7.2 9a2 2 0 0 0 1.4-1.4L9.8 3.5ZM18 14.5l.6 1.9 1.9.6-1.9.6-.6 1.9-.6-1.9-1.9-.6 1.9-.6.6-1.9Z",
  grid: "M4 4h6.5v6.5H4V4Zm9.5 0H20v6.5h-6.5V4ZM4 13.5h6.5V20H4v-6.5Zm9.5 0H20V20h-6.5v-6.5Z",
  truck:
    "M2.5 6h11v10h-11V6Zm11 4h4l3 3v3h-7v-6ZM7 18.5a1.75 1.75 0 1 0 0-3.5 1.75 1.75 0 0 0 0 3.5Zm10 0a1.75 1.75 0 1 0 0-3.5 1.75 1.75 0 0 0 0 3.5Z",
  document: "M6 3h8l4 4v14H6V3Zm8 0v4h4M9 12h6M9 16h6",
  box: "M12 3 20 7.5v9L12 21l-8-4.5v-9L12 3ZM4 7.5 12 12l8-4.5M12 12v9",
};

function WhyUs() {
  const t = useTranslations("home");
  const items = t.raw("whyItems") as WhyItem[];

  return (
    <section className="bg-brand-navy-dark text-white">
      <div className="mx-auto max-w-6xl px-4 py-16 sm:py-20">
        <p className="text-sm font-medium uppercase tracking-widest text-brand-gold">
          {t("whyEyebrow")}
        </p>
        <h2 className="mt-3 text-4xl leading-none font-bold tracking-tight uppercase sm:text-5xl">
          {t("whyTitle")}
        </h2>
        <p className="mt-4 max-w-2xl text-lg text-slate-300">{t("whyTagline")}</p>

        <div className="-mx-4 mt-12 flex snap-x snap-mandatory gap-4 overflow-x-auto px-4 pb-2 lg:mx-0 lg:grid lg:grid-cols-5 lg:overflow-visible lg:px-0">
          {items.map((item) => (
            <div key={item.title} className="w-64 shrink-0 snap-start lg:w-auto">
              <div className="flex aspect-[4/5] items-center justify-center bg-brand-navy-light">
                <svg
                  viewBox="0 0 24 24"
                  className="h-14 w-14 text-brand-gold"
                  fill="none"
                  stroke="currentColor"
                  strokeWidth="1.5"
                  strokeLinecap="round"
                  strokeLinejoin="round"
                  aria-hidden
                >
                  <path d={WHY_ICON_PATHS[item.icon]} />
                </svg>
              </div>
              <h3 className="mt-5 text-lg font-bold">{item.title}</h3>
              <p className="mt-2 text-slate-300">{item.text}</p>
            </div>
          ))}
        </div>
      </div>
    </section>
  );
}

function RentalTerms() {
  const t = useTranslations("home");
  const items = t.raw("termsItems") as TermItem[];

  return (
    <section id="conditions-location" className="border-t border-slate-100 bg-white">
      <div className="mx-auto max-w-6xl px-4 py-16">
        <p className="text-sm font-medium uppercase tracking-widest text-brand-gold-dark">
          {t("termsEyebrow")}
        </p>
        <h2 className="mt-2 mb-10 heading-section">{t("termsTitle")}</h2>
        <dl className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
          {items.map((item) => (
            <div
              key={item.title}
              className="rounded-2xl border border-slate-200 border-l-4 border-l-brand-gold p-5"
            >
              <dt className="text-sm font-bold uppercase tracking-wider text-brand-navy">
                {item.title}
              </dt>
              <dd className="mt-2 text-slate-600">{item.text}</dd>
            </div>
          ))}
        </dl>
      </div>
    </section>
  );
}

function Hero({ backgroundImage }: { backgroundImage: string | null }) {
  const t = useTranslations("home");

  if (backgroundImage) {
    return (
      <section
        className="relative bg-cover bg-center"
        style={{ backgroundImage: `url(${backgroundImage})` }}
      >
        <div className="absolute inset-0 bg-gradient-to-r from-brand-navy/95 via-brand-navy/75 to-brand-navy/40" />
        <div className="relative mx-auto flex max-w-6xl flex-col items-start gap-6 px-4 py-24 sm:py-32">
          <h1 className="max-w-2xl text-4xl leading-[1.08] font-bold tracking-tight text-white sm:text-5xl lg:text-6xl">
            {t("heroTitle")}
          </h1>
          <p className="max-w-xl text-lg text-slate-100">{t("heroSubtitle")}</p>
          <div className="flex flex-wrap gap-3">
            <Link
              href="/catalogue"
              className="rounded-full bg-brand-gold px-6 py-3 font-medium text-brand-navy transition hover:bg-brand-gold-dark"
            >
              {t("ctaCatalogue")}
            </Link>
            <a
              href={buildWhatsappLink("Bonjour, je souhaite louer du mobilier pour un événement.")}
              target="_blank"
              rel="noopener noreferrer"
              className="rounded-full bg-brand-gold px-6 py-3 font-medium text-brand-navy transition hover:bg-brand-gold-dark"
            >
              {t("ctaWhatsapp")}
            </a>
          </div>
        </div>
      </section>
    );
  }

  return (
    <section className="bg-gradient-to-b from-brand-gold/10 to-white">
      <div className="mx-auto flex max-w-6xl flex-col items-start gap-6 px-4 py-16">
        <h1 className="max-w-2xl text-4xl leading-[1.08] font-bold tracking-tight text-brand-navy sm:text-5xl lg:text-6xl">
          {t("heroTitle")}
        </h1>
        <p className="max-w-xl text-lg text-slate-600">{t("heroSubtitle")}</p>
        <div className="flex flex-wrap gap-3">
          <Link
            href="/catalogue"
            className="rounded-full bg-brand-navy px-6 py-3 font-medium text-white transition hover:bg-brand-navy-light"
          >
            {t("ctaCatalogue")}
          </Link>
          <a
            href={buildWhatsappLink("Bonjour, je souhaite louer du mobilier pour un événement.")}
            target="_blank"
            rel="noopener noreferrer"
            className="rounded-full bg-brand-gold px-6 py-3 font-medium text-brand-navy transition hover:bg-brand-gold-dark"
          >
            {t("ctaWhatsapp")}
          </a>
        </div>
      </div>
    </section>
  );
}

function HowItWorks() {
  const t = useTranslations("home");

  return (
    <section className="border-t border-slate-100 bg-slate-50">
      <div className="mx-auto max-w-6xl px-4 py-16">
        <h2 className="mb-10 heading-section">
          {t("howItWorksTitle")}
        </h2>
        <div className="grid gap-8 sm:grid-cols-3">
          <div>
            <h3 className="heading-sub">{t("step1Title")}</h3>
            <p className="mt-2 text-slate-600">{t("step1Text")}</p>
          </div>
          <div>
            <h3 className="heading-sub">{t("step2Title")}</h3>
            <p className="mt-2 text-slate-600">{t("step2Text")}</p>
          </div>
          <div>
            <h3 className="heading-sub">{t("step3Title")}</h3>
            <p className="mt-2 text-slate-600">{t("step3Text")}</p>
          </div>
        </div>
      </div>
    </section>
  );
}
