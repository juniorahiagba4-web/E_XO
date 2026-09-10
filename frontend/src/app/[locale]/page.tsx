import { useTranslations } from "next-intl";
import { getTranslations } from "next-intl/server";
import { Link } from "@/i18n/navigation";
import { getCategories } from "@/lib/api";
import { buildWhatsappLink } from "@/lib/config";

const FALLBACK_CATEGORIES = [
  { id: 1, slug: "chaises", name: "Chaises" },
  { id: 2, slug: "tables", name: "Tables" },
  { id: 3, slug: "nappes", name: "Nappes & décoration" },
  { id: 4, slug: "glacieres", name: "Glacières" },
];

export async function generateMetadata({
  params,
}: PageProps<"/[locale]">) {
  const { locale } = await params;
  const t = await getTranslations({ locale, namespace: "home" });
  return { title: t("heroTitle") };
}

export default async function HomePage({ params }: PageProps<"/[locale]">) {
  const { locale } = await params;
  const categories = await getCategories(locale).catch(() => null);
  const list = categories && categories.length ? categories : FALLBACK_CATEGORIES;

  return <HomeView categories={list} />;
}

function HomeView({
  categories,
}: {
  categories: { id: number; slug: string; name: string }[];
}) {
  const t = useTranslations("home");

  return (
    <div>
      <section className="bg-gradient-to-b from-amber-50 to-white">
        <div className="mx-auto flex max-w-6xl flex-col items-start gap-6 px-4 py-20">
          <h1 className="max-w-2xl text-4xl font-bold tracking-tight text-slate-900 sm:text-5xl">
            {t("heroTitle")}
          </h1>
          <p className="max-w-xl text-lg text-slate-600">{t("heroSubtitle")}</p>
          <div className="flex flex-wrap gap-3">
            <Link
              href="/catalogue"
              className="rounded-full bg-slate-900 px-6 py-3 font-medium text-white transition hover:bg-slate-700"
            >
              {t("ctaCatalogue")}
            </Link>
            <a
              href={buildWhatsappLink("Bonjour, je souhaite louer du mobilier pour un événement.")}
              target="_blank"
              rel="noopener noreferrer"
              className="rounded-full border border-slate-300 px-6 py-3 font-medium text-slate-700 transition hover:bg-slate-50"
            >
              {t("ctaWhatsapp")}
            </a>
          </div>
        </div>
      </section>

      <section className="mx-auto max-w-6xl px-4 py-16">
        <h2 className="mb-8 text-2xl font-semibold text-slate-900">
          {t("featuredTitle")}
        </h2>
        <div className="grid grid-cols-2 gap-4 sm:grid-cols-4">
          {categories.map((cat) => (
            <Link
              key={cat.id}
              href={{ pathname: "/catalogue", query: { category: cat.slug } }}
              className="group flex flex-col items-center gap-3 rounded-2xl border border-slate-200 p-6 text-center transition hover:border-amber-400 hover:shadow-sm"
            >
              <span className="flex h-14 w-14 items-center justify-center rounded-full bg-amber-100 text-xl font-semibold text-amber-700 group-hover:bg-amber-200">
                {cat.name.charAt(0)}
              </span>
              <span className="font-medium text-slate-800">{cat.name}</span>
            </Link>
          ))}
        </div>
      </section>

      <section className="border-t border-slate-100 bg-slate-50">
        <div className="mx-auto max-w-6xl px-4 py-16">
          <h2 className="mb-10 text-2xl font-semibold text-slate-900">
            {t("howItWorksTitle")}
          </h2>
          <div className="grid gap-8 sm:grid-cols-3">
            <div>
              <h3 className="font-semibold text-slate-900">{t("step1Title")}</h3>
              <p className="mt-2 text-slate-600">{t("step1Text")}</p>
            </div>
            <div>
              <h3 className="font-semibold text-slate-900">{t("step2Title")}</h3>
              <p className="mt-2 text-slate-600">{t("step2Text")}</p>
            </div>
            <div>
              <h3 className="font-semibold text-slate-900">{t("step3Title")}</h3>
              <p className="mt-2 text-slate-600">{t("step3Text")}</p>
            </div>
          </div>
        </div>
      </section>
    </div>
  );
}
