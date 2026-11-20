import { ImageResponse } from "next/og";
import { getTranslations } from "next-intl/server";
import { hasLocale } from "next-intl";
import { routing } from "@/i18n/routing";

// Preview image shown when a page of the site is shared (WhatsApp, Facebook,
// Google...) and the page has no photo of its own: 1200×630, brand colours.
export async function GET(request: Request, { params }: { params: Promise<{ locale: string }> }) {
  const { locale: raw } = await params;
  const locale = hasLocale(routing.locales, raw) ? raw : routing.defaultLocale;
  const t = await getTranslations({ locale, namespace: "seo" });

  const logo = await fetch(new URL("/brand/logo-lockup-dark.svg", request.url))
    .then((r) => r.text())
    .then((svg) => `data:image/svg+xml;base64,${Buffer.from(svg).toString("base64")}`)
    .catch(() => null);

  return new ImageResponse(
    (
      <div
        style={{
          width: "100%",
          height: "100%",
          display: "flex",
          flexDirection: "column",
          alignItems: "center",
          justifyContent: "center",
          background: "#000046",
          padding: 60,
        }}
      >
        {/* eslint-disable-next-line @next/next/no-img-element */}
        {logo && <img src={logo} width={340} height={260} alt="" />}
        <div
          style={{
            marginTop: 40,
            fontSize: 44,
            fontWeight: 700,
            color: "#ffffff",
            textAlign: "center",
            maxWidth: 1000,
            lineHeight: 1.2,
          }}
        >
          {t("ogTagline")}
        </div>
        <div style={{ marginTop: 24, fontSize: 30, color: "#ffbd00" }}>laperledor-togo.com</div>
      </div>
    ),
    {
      width: 1200,
      height: 630,
      headers: { "Cache-Control": "public, max-age=86400, s-maxage=604800" },
    },
  );
}
