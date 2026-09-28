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
    </div>
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
          <h1
            className="max-w-2xl text-4xl font-black text-white sm:text-5xl"
            style={{ fontFamily: "var(--font-display)" }}
          >
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
        <h1 className="max-w-2xl text-4xl font-bold tracking-tight text-brand-navy sm:text-5xl">
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
        <h2 className="mb-10 text-2xl font-semibold text-brand-navy">
          {t("howItWorksTitle")}
        </h2>
        <div className="grid gap-8 sm:grid-cols-3">
          <div>
            <h3 className="font-semibold text-brand-navy">{t("step1Title")}</h3>
            <p className="mt-2 text-slate-600">{t("step1Text")}</p>
          </div>
          <div>
            <h3 className="font-semibold text-brand-navy">{t("step2Title")}</h3>
            <p className="mt-2 text-slate-600">{t("step2Text")}</p>
          </div>
          <div>
            <h3 className="font-semibold text-brand-navy">{t("step3Title")}</h3>
            <p className="mt-2 text-slate-600">{t("step3Text")}</p>
          </div>
        </div>
      </div>
    </section>
  );
}
