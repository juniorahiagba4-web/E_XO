import type { Metadata } from "next";
import { routing } from "@/i18n/routing";
import { COMPANY_MAPS_URL, COMPANY_NAME, IS_STAGING } from "./config";
import type { CompanyInfo } from "./company";
import type { Item } from "./types";

/** Public address of the site, used for canonical links, the sitemap and social previews. */
export const SITE_URL = (
  process.env.NEXT_PUBLIC_SITE_URL ??
  (IS_STAGING ? "https://test.laperledor-togo.com" : "https://www.laperledor-togo.com")
).replace(/\/$/, "");

const OG_LOCALES: Record<string, string> = { fr: "fr_FR", en: "en_US" };

/** Full URL of a page in one language, e.g. absoluteUrl("fr", "/contact"). */
export function absoluteUrl(locale: string, path = "/") {
  const clean = path === "/" ? "" : path;
  return `${SITE_URL}/${locale}${clean}`;
}

/**
 * Canonical link of the page and its versions in the other languages, so
 * Google treats /fr/... and /en/... as one page in two languages.
 */
export function alternates(locale: string, path = "/"): Metadata["alternates"] {
  const languages: Record<string, string> = {};
  for (const l of routing.locales) languages[l] = absoluteUrl(l, path);
  languages["x-default"] = absoluteUrl(routing.defaultLocale, path);
  return { canonical: absoluteUrl(locale, path), languages };
}

/**
 * Title, description, canonical/language links and social preview (WhatsApp,
 * Facebook...) of a page, in one call.
 */
export function pageMetadata({
  locale,
  path,
  title,
  description,
  image,
  absoluteTitle = false,
}: {
  locale: string;
  path: string;
  title: string;
  description: string;
  image?: string | null;
  absoluteTitle?: boolean;
}): Metadata {
  // Without a photo of its own, the page uses the brand preview image (/fr/og).
  const images = [image ? { url: image, alt: title } : { url: `${SITE_URL}/${locale}/og`, width: 1200, height: 630, alt: title }];
  return {
    title: absoluteTitle ? { absolute: title } : title,
    description,
    alternates: alternates(locale, path),
    openGraph: {
      type: "website",
      siteName: COMPANY_NAME,
      locale: OG_LOCALES[locale] ?? locale,
      url: absoluteUrl(locale, path),
      title,
      description,
      images,
    },
    twitter: {
      card: "summary_large_image",
      title,
      description,
      images: images.map((i) => i.url),
    },
  };
}

/** Pages that only make sense for one visitor (cart, account...): kept out of Google. */
export const PRIVATE_PAGE: Metadata = { robots: { index: false, follow: true } };

/** Cuts a text to about `max` characters on a word boundary, for meta descriptions. */
export function excerpt(text: string | null | undefined, max = 158) {
  const clean = (text ?? "").replace(/\s+/g, " ").trim();
  if (clean.length <= max) return clean;
  const cut = clean.slice(0, max - 1);
  return `${cut.slice(0, cut.lastIndexOf(" ")).replace(/[,;:.\-–]+$/, "")}…`;
}

export function formatFcfa(value: string | number) {
  return `${new Intl.NumberFormat("fr-FR").format(Number(value)).replace(/ | /g, " ")} FCFA`;
}

/** Google's description of the business: name, address, phone, area served... */
export function localBusinessJsonLd(locale: string, description: string, company: CompanyInfo) {
  const sameAs = [
    company.social.tiktok,
    company.social.facebook,
    company.social.instagram,
    COMPANY_MAPS_URL,
  ].filter(Boolean);
  return {
    "@context": "https://schema.org",
    "@type": "LocalBusiness",
    "@id": `${SITE_URL}/#business`,
    name: COMPANY_NAME,
    description,
    url: absoluteUrl(locale),
    logo: `${SITE_URL}/brand/logo-lockup-dark.svg`,
    image: `${SITE_URL}/${locale}/og`,
    telephone: company.phone.replace(/[^\d+]/g, ""),
    ...(company.email ? { email: company.email } : {}),
    priceRange: "700 – 150 000 FCFA",
    currenciesAccepted: "XOF",
    address: {
      "@type": "PostalAddress",
      streetAddress: company.address.split(", Lomé")[0],
      addressLocality: "Lomé",
      addressCountry: "TG",
    },
    areaServed: [
      { "@type": "City", name: "Lomé" },
      { "@type": "Country", name: "Togo" },
      { "@type": "Country", name: "Bénin" },
      { "@type": "Country", name: "Ghana" },
      { "@type": "Place", name: "Afrique de l'Ouest" },
    ],
    hasMap: COMPANY_MAPS_URL,
    sameAs,
  };
}

/** Product card for Google: photo, price, stock and rating stars of an item. */
export function productJsonLd(locale: string, item: Item, description: string) {
  const url = absoluteUrl(locale, `/catalogue/${item.slug}`);
  const offers: Record<string, unknown>[] = [];
  const seller = { "@id": `${SITE_URL}/#business` };
  const availability =
    item.total_stock > 0 ? "https://schema.org/InStock" : "https://schema.org/OutOfStock";

  if (Number(item.rental_price_per_day) > 0) {
    offers.push({
      "@type": "Offer",
      name: locale === "fr" ? "Location (prix par jour)" : "Rental (price per day)",
      url,
      price: Number(item.rental_price_per_day),
      priceCurrency: "XOF",
      priceSpecification: {
        "@type": "UnitPriceSpecification",
        price: Number(item.rental_price_per_day),
        priceCurrency: "XOF",
        unitCode: "DAY",
        referenceQuantity: { "@type": "QuantitativeValue", value: 1, unitCode: "DAY" },
      },
      businessFunction: "http://purl.org/goodrelations/v1#LeaseOut",
      availability,
      seller,
    });
  }
  if (item.sale_price && !item.sale_price_on_request && Number(item.sale_price) > 0) {
    offers.push({
      "@type": "Offer",
      name: locale === "fr" ? "Achat" : "Purchase",
      url,
      price: Number(item.sale_price),
      priceCurrency: "XOF",
      businessFunction: "http://purl.org/goodrelations/v1#Sell",
      availability: (item.purchasable_quantity ?? 0) > 0 ? "https://schema.org/InStock" : availability,
      seller,
    });
  }

  const data: Record<string, unknown> = {
    "@context": "https://schema.org",
    "@type": "Product",
    name: item.name,
    description,
    sku: item.sku,
    url,
    image: [item.image_url, ...(item.gallery ?? [])].filter(Boolean),
    brand: { "@type": "Brand", name: COMPANY_NAME },
    ...(item.category ? { category: item.category.name } : {}),
    ...(offers.length ? { offers: offers.length === 1 ? offers[0] : offers } : {}),
  };

  if (item.rating && item.rating_count > 0) {
    data.aggregateRating = {
      "@type": "AggregateRating",
      ratingValue: Number(item.rating),
      reviewCount: item.rating_count,
      bestRating: 5,
      worstRating: 1,
    };
    data.review = item.reviews.slice(0, 5).map((r) => ({
      "@type": "Review",
      author: { "@type": "Person", name: r.author_name },
      reviewRating: { "@type": "Rating", ratingValue: Number(r.rating), bestRating: 5, worstRating: 1 },
      ...(r.comment ? { reviewBody: r.comment } : {}),
      ...(r.created_at ? { datePublished: r.created_at.slice(0, 10) } : {}),
    }));
  }

  return data;
}

/** "Accueil > Catalogue > Chaises > ..." trail shown under the result in Google. */
export function breadcrumbJsonLd(trail: { name: string; url: string }[]) {
  return {
    "@context": "https://schema.org",
    "@type": "BreadcrumbList",
    itemListElement: trail.map((step, index) => ({
      "@type": "ListItem",
      position: index + 1,
      name: step.name,
      item: step.url,
    })),
  };
}
