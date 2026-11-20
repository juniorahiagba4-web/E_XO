import type { Metadata, Viewport } from "next";
import { NextIntlClientProvider, hasLocale } from "next-intl";
import { notFound } from "next/navigation";
import { routing } from "@/i18n/routing";
import { CartProvider } from "@/lib/cart-context";
import { AuthProvider } from "@/lib/auth-context";
import { AuthGateProvider } from "@/lib/auth-gate-context";
import { FavoritesProvider } from "@/lib/favorites-context";
import { COMPANY_NAME, IS_STAGING } from "@/lib/config";
import { Analytics } from "@vercel/analytics/next";
import { getTranslations } from "next-intl/server";
import JsonLd from "@/components/JsonLd";
import { SITE_URL, localBusinessJsonLd } from "@/lib/seo";
import { heros } from "../fonts/heros";
import "../globals.css";

export async function generateMetadata({ params }: LayoutProps<"/[locale]">): Promise<Metadata> {
  const { locale } = await params;
  const t = await getTranslations({ locale, namespace: "seo" });

  return {
    metadataBase: new URL(SITE_URL),
    // Each page gives its own title; "| La Perle d'Or" is added after it.
    title: { default: `${t("siteTitle")} | ${COMPANY_NAME}`, template: `%s | ${COMPANY_NAME}` },
    description: t("siteDescription"),
    applicationName: COMPANY_NAME,
    // No canonical link here: each public page sets its own (pageMetadata),
    // otherwise every page would point Google back to the home page.
    openGraph: {
      type: "website",
      siteName: COMPANY_NAME,
      locale: locale === "fr" ? "fr_FR" : "en_US",
    },
    twitter: { card: "summary_large_image" },
    // The test copy must never show up in search results.
    ...(IS_STAGING ? { robots: { index: false, follow: false } } : {}),
    // Proof of ownership for Google Search Console (value given by Google).
    ...(process.env.NEXT_PUBLIC_GOOGLE_SITE_VERIFICATION
      ? { verification: { google: process.env.NEXT_PUBLIC_GOOGLE_SITE_VERIFICATION } }
      : {}),
  };
}

export const viewport: Viewport = {
  themeColor: "#000046",
};

export function generateStaticParams() {
  return routing.locales.map((locale) => ({ locale }));
}

export default async function LocaleLayout({
  children,
  params,
}: LayoutProps<"/[locale]">) {
  const { locale } = await params;

  if (!hasLocale(routing.locales, locale)) {
    notFound();
  }
  const tSeo = await getTranslations({ locale, namespace: "seo" });

  return (
    <html
      lang={locale}
      className={`${heros.variable} h-full antialiased`}
      suppressHydrationWarning
    >
      <body className="flex min-h-full flex-col bg-white text-brand-navy font-sans">
        {IS_STAGING && (
          <div className="sticky top-0 z-[60] bg-red-600 px-4 py-1.5 text-center text-sm font-semibold text-white">
            PRÉ-PRODUCTION · site de test, les commandes passées ici ne sont pas réelles
          </div>
        )}
        <NextIntlClientProvider>
          <AuthProvider>
            <AuthGateProvider>
              <FavoritesProvider>
                <CartProvider>{children}</CartProvider>
              </FavoritesProvider>
            </AuthGateProvider>
          </AuthProvider>
        </NextIntlClientProvider>
        {/* Visitor statistics (Vercel Web Analytics: no cookie, no personal data).
            The test copy is not counted. */}
        {!IS_STAGING && <Analytics />}
        <JsonLd data={localBusinessJsonLd(locale, tSeo("siteDescription"))} />
      </body>
    </html>
  );
}
