import { notFound } from "next/navigation";
import { getTranslations } from "next-intl/server";
import { Link } from "@/i18n/navigation";
import { getItem, ApiError } from "@/lib/api";
import AvailabilityAndQuote from "@/components/AvailabilityAndQuote";

export default async function ProductPage({
  params,
}: PageProps<"/[locale]/catalogue/[slug]">) {
  const { locale, slug } = await params;
  const t = await getTranslations({ locale, namespace: "product" });

  let item;
  try {
    item = await getItem(locale, slug);
  } catch (err) {
    if (err instanceof ApiError && err.status === 404) {
      notFound();
    }
    throw err;
  }

  return (
    <div className="mx-auto max-w-5xl px-4 py-12">
      <Link href="/catalogue" className="text-sm text-slate-500 hover:text-slate-800">
        &larr; {t("backToCatalogue")}
      </Link>

      <div className="mt-6 grid gap-10 sm:grid-cols-2">
        <div className="aspect-square overflow-hidden rounded-2xl bg-slate-100">
          {item.image_url ? (
            // eslint-disable-next-line @next/next/no-img-element
            <img src={item.image_url} alt={item.name} className="h-full w-full object-cover" />
          ) : (
            <div className="flex h-full w-full items-center justify-center text-4xl text-slate-300">
              {item.name.charAt(0)}
            </div>
          )}
        </div>

        <div>
          <h1 className="text-3xl font-bold text-slate-900">{item.name}</h1>
          <p className="mt-2 text-xl text-amber-600">{item.rental_price_per_day} / jour</p>
          {item.deposit_amount && (
            <p className="mt-1 text-sm text-slate-500">
              {t("deposit")}: {item.deposit_amount}
            </p>
          )}
          {item.description && (
            <p className="mt-4 text-slate-600">{item.description}</p>
          )}

          <div className="mt-6">
            <AvailabilityAndQuote item={item} locale={locale} />
          </div>
        </div>
      </div>
    </div>
  );
}
