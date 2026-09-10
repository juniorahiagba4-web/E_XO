import { getTranslations } from "next-intl/server";
import { Link } from "@/i18n/navigation";
import { getItems } from "@/lib/api";

export default async function CataloguePage({
  params,
  searchParams,
}: PageProps<"/[locale]/catalogue">) {
  const { locale } = await params;
  const { category, search } = await searchParams;
  const t = await getTranslations({ locale, namespace: "catalogue" });

  let items: Awaited<ReturnType<typeof getItems>> = [];
  let loadError = false;
  try {
    items = await getItems(locale, {
      category: typeof category === "string" ? category : undefined,
      search: typeof search === "string" ? search : undefined,
    });
  } catch {
    loadError = true;
  }

  return (
    <div className="mx-auto max-w-6xl px-4 py-12">
      <h1 className="mb-8 text-3xl font-bold text-slate-900">{t("title")}</h1>

      <form className="mb-8 flex max-w-md gap-2" action="">
        <input type="hidden" name="category" value={typeof category === "string" ? category : ""} />
        <input
          type="search"
          name="search"
          defaultValue={typeof search === "string" ? search : ""}
          placeholder={t("searchPlaceholder")}
          className="w-full rounded-lg border border-slate-300 px-4 py-2 text-sm focus:border-amber-500 focus:outline-none"
        />
      </form>

      {loadError && (
        <p className="rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700">
          {t("loadError")}
        </p>
      )}

      {!loadError && items.length === 0 && (
        <p className="text-slate-500">{t("empty")}</p>
      )}

      <div className="grid grid-cols-2 gap-6 sm:grid-cols-3 lg:grid-cols-4">
        {items.map((item) => (
          <Link
            key={item.id}
            href={`/catalogue/${item.slug}`}
            className="group flex flex-col overflow-hidden rounded-2xl border border-slate-200 transition hover:shadow-md"
          >
            <div className="aspect-square w-full bg-slate-100">
              {item.image_url ? (
                // eslint-disable-next-line @next/next/no-img-element
                <img
                  src={item.image_url}
                  alt={item.name}
                  className="h-full w-full object-cover transition group-hover:scale-105"
                />
              ) : (
                <div className="flex h-full w-full items-center justify-center text-slate-300">
                  {item.name.charAt(0)}
                </div>
              )}
            </div>
            <div className="flex flex-1 flex-col gap-1 p-4">
              <span className="font-medium text-slate-900">{item.name}</span>
              <span className="text-sm text-slate-500">
                {item.rental_price_per_day} {t("perDay")}
              </span>
            </div>
          </Link>
        ))}
      </div>
    </div>
  );
}
