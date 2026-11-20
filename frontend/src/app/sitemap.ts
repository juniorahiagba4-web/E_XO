import type { MetadataRoute } from "next";
import { routing } from "@/i18n/routing";
import { getAllItems, getCategories } from "@/lib/api";
import { absoluteUrl } from "@/lib/seo";

// Rebuilt at most once an hour, so a new item added in the admin shows up on
// its own.
export const revalidate = 3600;

function entry(
  path: string,
  priority: number,
  changeFrequency: MetadataRoute.Sitemap[number]["changeFrequency"],
  images?: string[],
): MetadataRoute.Sitemap {
  const languages = Object.fromEntries(routing.locales.map((l) => [l, absoluteUrl(l, path)]));
  return routing.locales.map((locale) => ({
    url: absoluteUrl(locale, path),
    lastModified: new Date(),
    changeFrequency,
    priority: locale === routing.defaultLocale ? priority : Math.max(0.1, priority - 0.2),
    alternates: { languages },
    ...(images?.length ? { images } : {}),
  }));
}

export default async function sitemap(): Promise<MetadataRoute.Sitemap> {
  const pages: MetadataRoute.Sitemap = [
    ...entry("/", 1, "weekly"),
    ...entry("/catalogue", 0.9, "weekly"),
    ...entry("/contact", 0.6, "monthly"),
    ...entry("/faq", 0.5, "monthly"),
    ...entry("/conditions", 0.2, "yearly"),
    ...entry("/confidentialite", 0.2, "yearly"),
  ];

  const [categories, items] = await Promise.all([
    getCategories(routing.defaultLocale).catch(() => []),
    getAllItems(routing.defaultLocale).catch(() => []),
  ]);

  for (const category of categories) {
    pages.push(...entry(`/catalogue?category=${encodeURIComponent(category.slug)}`, 0.8, "weekly"));
  }
  for (const item of items) {
    const images = [item.image_url, ...(item.gallery ?? [])].filter((u): u is string => Boolean(u));
    pages.push(...entry(`/catalogue/${item.slug}`, 0.8, "weekly", images));
  }

  return pages;
}
