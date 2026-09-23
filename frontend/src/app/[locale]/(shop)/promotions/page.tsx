import { getTranslations } from "next-intl/server";
import { Link } from "@/i18n/navigation";
import { getPromotions } from "@/lib/api";

export default async function PromotionsPage({
  params,
}: PageProps<"/[locale]/promotions">) {
  const { locale } = await params;
  const t = await getTranslations({ locale, namespace: "promotions" });

  let promotions: Awaited<ReturnType<typeof getPromotions>> = [];
  let loadError = false;
  try {
    promotions = await getPromotions(locale);
  } catch {
    loadError = true;
  }

  return (
    <div className="mx-auto max-w-5xl px-4 py-12">
      <h1 className="mb-2 text-3xl font-bold text-brand-navy">{t("title")}</h1>
      <p className="mb-10 max-w-2xl text-slate-500">{t("intro")}</p>

      {loadError && (
        <p className="rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700">{t("loadError")}</p>
      )}

      {!loadError && promotions.length === 0 && (
        <p className="text-slate-500">{t("empty")}</p>
      )}

      <div className="grid gap-6 sm:grid-cols-2">
        {promotions.map((promo) => {
          const target = promo.item
            ? `/catalogue/${promo.item.slug}`
            : promo.category
              ? `/catalogue?category=${promo.category.slug}`
              : "/catalogue";

          return (
            <Link
              key={promo.id}
              href={target}
              className="group flex flex-col overflow-hidden rounded-2xl border border-slate-200 transition hover:shadow-md"
            >
              <div className="aspect-[2/1] w-full bg-gradient-to-br from-brand-gold/20 to-brand-gold/5">
                {promo.image_url ? (
                  // eslint-disable-next-line @next/next/no-img-element
                  <img
                    src={promo.image_url}
                    alt={promo.title}
                    className="h-full w-full object-cover transition group-hover:scale-105"
                  />
                ) : (
                  <div className="flex h-full w-full items-center justify-center px-6 text-center text-3xl font-bold text-brand-gold">
                    {Number(promo.discount_value) > 0
                      ? promo.discount_type === "percent"
                        ? `-${Number(promo.discount_value)}%`
                        : `-${Number(promo.discount_value).toLocaleString()} XOF`
                      : promo.title}
                  </div>
                )}
              </div>
              <div className="flex flex-1 flex-col gap-2 p-5">
                <h2 className="font-semibold text-brand-navy">{promo.title}</h2>
                {promo.description && (
                  <p className="text-sm text-slate-500">{promo.description}</p>
                )}
                {promo.ends_at && (
                  <p className="mt-auto text-xs text-slate-400">
                    {t("validUntil", { date: new Date(promo.ends_at).toLocaleDateString(locale) })}
                  </p>
                )}
              </div>
            </Link>
          );
        })}
      </div>
    </div>
  );
}
