import type { Metadata, Viewport } from "next";
import { NextIntlClientProvider, hasLocale } from "next-intl";
import { notFound } from "next/navigation";
import { routing } from "@/i18n/routing";
import { CartProvider } from "@/lib/cart-context";
import { AuthProvider } from "@/lib/auth-context";
import { AuthGateProvider } from "@/lib/auth-gate-context";
import { FavoritesProvider } from "@/lib/favorites-context";
import { COMPANY_NAME, IS_STAGING } from "@/lib/config";
import { heros } from "../fonts/heros";
import "../globals.css";

export const metadata: Metadata = {
  title: `${COMPANY_NAME} - Location de mobilier événementiel`,
  description:
    "Location et vente de chaises, tables, nappes, glacières pour vos événements au Togo et dans la sous-région.",
  // The test copy must never show up in search results.
  ...(IS_STAGING ? { robots: { index: false, follow: false } } : {}),
};

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
      </body>
    </html>
  );
}
